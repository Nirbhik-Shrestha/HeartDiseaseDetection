<?php
    include("../connection.php");
    include_once("../auth.php");

    // Match the other patient pages, so "today" cannot disagree with the
    // sessions list when the server clock is in another zone.
    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    $rowPatient = requireRole($con, 'patient');
    $useremail = $rowPatient['pemail'];
    $userid   = (int)$rowPatient['pid'];
    $username = $rowPatient['pname'];

    // One query for both tables: upcoming and past differ only by date, and
    // splitting in PHP keeps the two lists guaranteed consistent.
    // shared_pdid tells us whether a heart-risk reading is attached to the visit.
    $sql = "SELECT appointment.apid, appointment.adate, appointment.status,
                   appointment.doctor_notes, appointment.notes_updated_at,
                   doctors.dname AS dname, specialties.sname AS sname,
                   timeslot.start_time AS start_time, timeslot.end_time AS end_time,
                   (SELECT pd.pdid FROM patient_data pd
                     WHERE pd.apid = appointment.apid LIMIT 1) AS shared_pdid
    FROM appointment
    INNER JOIN timeslot ON appointment.tid = timeslot.tid
    INNER JOIN schedule ON timeslot.scid = schedule.scid
    INNER JOIN doctors ON schedule.did = doctors.did
    INNER JOIN specialties ON doctors.spid = specialties.spid
    WHERE appointment.pid = ?
    ORDER BY appointment.adate ASC, timeslot.start_time ASC";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $upcoming = [];
    $past     = [];
    while ($row = $result->fetch_assoc()) {
        // A visit the doctor has already recorded counts as past even on the
        // day itself, so its notes show up straight away.
        if ($row['adate'] >= $today && $row['status'] === 'booked') {
            $upcoming[] = $row;
        } else {
            $past[] = $row;
        }
    }
    $stmt->close();

    // Most recent visit first reads better than oldest-first for history.
    $past = array_reverse($past);

    // Readings the patient could still attach to an upcoming visit.
    $unshared = [];
    $stmt = $con->prepare("SELECT pdid, timestamp FROM patient_data
                            WHERE pid = ? AND apid IS NULL
                            ORDER BY pdid DESC");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $unshared[] = $r;
    }
    $stmt->close();

    $con->close();

    $notices = [
        'cancelled' => ['ok',  'Your appointment was cancelled and the timeslot is free again.'],
        'shared'    => ['ok',  'Your heart assessment is now visible to that doctor.'],
        'unshared'  => ['ok',  'Your heart assessment is no longer shared with that doctor.'],
        'past'      => ['bad', 'That appointment has already taken place, so it cannot be cancelled.'],
        'recorded'  => ['bad', 'Your doctor has already recorded that visit, so it cannot be cancelled.'],
        'notfound'  => ['bad', 'That appointment could not be found.'],
        'error'     => ['bad', 'Something went wrong. Please try again.'],
    ];
    $msg = isset($_GET['msg']) && isset($notices[$_GET['msg']]) ? $notices[$_GET['msg']] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .share-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .share-form select {
            height: 38px;
            max-width: 170px;
            padding: 0 10px;
            border: 1px solid #cfdbe3;
            border-radius: 8px;
            background: #fff;
            color: #12304a;
            font-size: 14.5px;
        }

        .share-form select:focus {
            outline: none;
            border-color: #00a99d;
            box-shadow: 0 0 0 3px rgba(0, 169, 157, 0.18);
        }

        .shared {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .inline-form {
            display: inline;
            margin: 0;
        }

        .notes-cell {
            max-width: 360px;
        }

        .notes-cell summary {
            color: #00786f;
            font-weight: 600;
            cursor: pointer;
        }

        .doctor-notes {
            margin-top: 8px;
            padding: 10px 12px;
            border-left: 3px solid #00a99d;
            border-radius: 4px;
            background: #f7fafb;
            color: #12304a;
            line-height: 1.5;
        }

        .notes-date {
            margin-top: 4px;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">My appointments</span>
        <h1>Your appointments</h1>
        <p>Share a heart check with your doctor before a visit, and read their notes afterwards.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($msg): ?>
        <div class="notice notice-<?= $msg[0] ?>"><?= htmlspecialchars($msg[1]) ?></div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Upcoming</h2>
                <p class="panel__sub"><?= count($upcoming) ?> <?= count($upcoming) === 1 ? 'appointment' : 'appointments' ?></p>
            </div>
            <a href="schedule.php" class="btn btn-primary btn-sm">+ Book an appointment</a>
        </div>

        <?php if ($upcoming): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date &amp; time</th>
                            <th>Heart check</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $row): ?>
                            <tr>
                                <td data-label="Doctor">
                                    <div><span class="cell-strong">Dr. <?= htmlspecialchars($row['dname']) ?></span><br>
                                    <span class="cell-muted"><?= htmlspecialchars($row['sname']) ?></span></div>
                                </td>
                                <td class="nowrap" data-label="Date & time">
                                    <div><span class="cell-strong"><?= date('D j M Y', strtotime($row['adate'])) ?></span><br>
                                    <span class="cell-muted"><?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></span></div>
                                </td>
                                <td data-label="Heart check">
                                    <?php if ($row['shared_pdid']): ?>
                                        <div class="shared">
                                            <span class="badge badge-success">&#10003; Shared</span>
                                            <form method="POST" action="shareAssessment.php" class="inline-form">
                                                <input type="hidden" name="apid" value="<?= (int)$row['apid'] ?>">
                                                <input type="hidden" name="action" value="unshare">
                                                <button type="submit" class="btn btn-light btn-sm">Stop sharing</button>
                                            </form>
                                        </div>
                                    <?php elseif ($unshared): ?>
                                        <form method="POST" action="shareAssessment.php" class="share-form">
                                            <input type="hidden" name="apid" value="<?= (int)$row['apid'] ?>">
                                            <input type="hidden" name="action" value="share">
                                            <select name="pdid" required aria-label="Heart check to share">
                                                <option value="" disabled selected hidden>Choose a reading</option>
                                                <?php foreach ($unshared as $u): ?>
                                                    <option value="<?= (int)$u['pdid'] ?>"><?= htmlspecialchars(date("j M Y", strtotime($u['timestamp']))) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">Share</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="cell-muted">No reading yet &mdash; <a href="form.php">take the check</a></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right">
                                    <form method="POST" action="cancelAppointment.php" class="inline-form"
                                          onsubmit="return confirm('Cancel this appointment? The time slot will be released for other patients.');">
                                        <input type="hidden" name="apid" value="<?= (int)$row['apid'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You have no upcoming appointments.</p>
                <a href="schedule.php" class="btn btn-primary">Browse available sessions</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Past</h2>
                <p class="panel__sub">Visits your doctor has recorded, with their notes.</p>
            </div>
        </div>

        <?php if ($past): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date &amp; time</th>
                            <th>Status</th>
                            <th>Doctor's notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past as $row): ?>
                            <tr>
                                <td data-label="Doctor">
                                    <div><span class="cell-strong">Dr. <?= htmlspecialchars($row['dname']) ?></span><br>
                                    <span class="cell-muted"><?= htmlspecialchars($row['sname']) ?></span></div>
                                </td>
                                <td class="nowrap" data-label="Date & time">
                                    <div><span class="cell-strong"><?= date('D j M Y', strtotime($row['adate'])) ?></span><br>
                                    <span class="cell-muted"><?= date('g:i A', strtotime($row['start_time'])) ?> - <?= date('g:i A', strtotime($row['end_time'])) ?></span></div>
                                </td>
                                <td data-label="Status">
                                    <?php if ($row['status'] === 'completed'): ?>
                                        <span class="badge badge-success">&#10003; Completed</span>
                                    <?php elseif ($row['status'] === 'no_show'): ?>
                                        <span class="badge badge-danger">&#10007; Missed</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">Not recorded</span>
                                    <?php endif; ?>
                                </td>
                                <td class="notes-cell" data-label="Notes">
                                    <?php if (trim((string)$row['doctor_notes']) !== ''): ?>
                                        <details>
                                            <summary>Read notes</summary>
                                            <div class="doctor-notes"><?= nl2br(htmlspecialchars($row['doctor_notes'])) ?></div>
                                            <?php if ($row['notes_updated_at']): ?>
                                                <div class="cell-muted notes-date">Updated <?= htmlspecialchars(date("j M Y", strtotime($row['notes_updated_at']))) ?></div>
                                            <?php endif; ?>
                                        </details>
                                    <?php else: ?>
                                        <span class="cell-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No past appointments yet.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
