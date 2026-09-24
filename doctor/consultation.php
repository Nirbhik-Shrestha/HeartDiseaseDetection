<?php
/**
 * Record what happened at an appointment: its status (completed / no-show)
 * and consultation notes, which the patient sees on their appointments page.
 *
 * Only the doctor whose schedule holds the appointment can open it, and only
 * from the appointment's own date onwards: a visit cannot be completed before
 * it happens.
 */
include("../connection.php");
include_once("../auth.php");
date_default_timezone_set('Asia/Kathmandu');

$userfetch = requireRole($con, 'doctor');
$useremail = $userfetch["demail"];
$userid   = (int)$userfetch["did"];
$username = $userfetch["dname"];

$today = date('Y-m-d');
$apid = isset($_REQUEST['apid']) ? (int)$_REQUEST['apid'] : 0;

const CONSULTATION_STATUSES = [
    'booked'    => 'Not recorded yet',
    'completed' => 'Completed',
    'no_show'   => 'No-show (patient did not attend)',
];
const MAX_NOTES_LENGTH = 5000;

// The join up to schedule.did makes "this appointment is mine" part of the query.
$stmt = $con->prepare(
    "SELECT a.*, p.pname, p.pdob, t.start_time, t.end_time,
            (SELECT pd.pdid FROM patient_data pd WHERE pd.apid = a.apid LIMIT 1) AS shared_pdid
       FROM appointment a
       INNER JOIN patients p ON a.pid = p.pid
       INNER JOIN timeslot t ON a.tid = t.tid
       INNER JOIN schedule s ON t.scid = s.scid
      WHERE a.apid = ? AND s.did = ?"
);
$stmt->bind_param("ii", $apid, $userid);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$errors = [];
$canRecord = $appointment && $appointment['adate'] <= $today;

if ($appointment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? (string)$_POST['status'] : '';
    $notes  = isset($_POST['doctor_notes']) ? trim((string)$_POST['doctor_notes']) : '';

    if (!$canRecord) {
        $errors[] = "This visit can be recorded from " . $appointment['adate'] . ".";
    }
    if (!array_key_exists($status, CONSULTATION_STATUSES)) {
        $errors[] = "Please choose a status.";
    }
    if (mb_strlen($notes) > MAX_NOTES_LENGTH) {
        $errors[] = "Notes can be at most " . MAX_NOTES_LENGTH . " characters.";
    }

    if (!$errors) {
        $notesOrNull = $notes === '' ? null : $notes;
        // notes_updated_at only moves when the notes themselves change.
        $stmt = $con->prepare(
            "UPDATE appointment
                SET status = ?,
                    notes_updated_at = IF(doctor_notes <=> ?, notes_updated_at, NOW()),
                    doctor_notes = ?
              WHERE apid = ?"
        );
        $stmt->bind_param("sssi", $status, $notesOrNull, $notesOrNull, $apid);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $con->close();
            header("Location: appointment.php?msg=saved");
            exit();
        }
        $errors[] = "Could not save. Please try again.";
    }

    // Keep what the doctor typed on a failed save.
    $appointment['status'] = $status;
    $appointment['doctor_notes'] = $notes;
}
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .site-page .field textarea {
            width: 100%;
            min-height: 200px;
            padding: 12px 14px;
            border: 1px solid #cfdbe3;
            border-radius: 10px;
            background: #fff;
            color: #12304a;
            font-size: 16px;
            line-height: 1.5;
            resize: vertical;
        }

        .site-page .field textarea:focus {
            outline: none;
            border-color: #00a99d;
            box-shadow: 0 0 0 4px rgba(0, 169, 157, 0.18);
        }
    </style>
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Consultation</span>
        <h1><?= $appointment ? 'Record the visit' : 'Consultation' ?></h1>
        <p>Mark whether the visit took place and write notes. The patient can read your notes on their appointments page.</p>
    </div>
</section>

<main class="page-body">
<?php if (!$appointment): ?>
    <section class="panel">
        <div class="empty-state">
            <p>No appointment was found in your schedule with that reference.</p>
            <a href="appointment.php" class="btn btn-light">Back to appointments</a>
        </div>
    </section>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="notice notice-bad" role="alert">
            <strong>Not saved:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title">Visit record</h2>
            </div>

            <?php if (!$canRecord): ?>
                <div class="empty-state">
                    <p>This appointment is on <?= date('l j F Y', strtotime($appointment['adate'])) ?>. You can record the visit from that day.</p>
                    <a href="appointment.php" class="btn btn-light">Back to appointments</a>
                </div>
            <?php else: ?>
                <form method="post" action="consultation.php">
                    <input type="hidden" name="apid" value="<?= (int)$appointment['apid'] ?>">

                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <?php foreach (CONSULTATION_STATUSES as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $appointment['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="doctor_notes">Consultation notes</label>
                        <textarea id="doctor_notes" name="doctor_notes" maxlength="<?= MAX_NOTES_LENGTH ?>"
                            placeholder="Findings, advice, prescriptions, tests to arrange, follow-up..."><?= htmlspecialchars((string)$appointment['doctor_notes']) ?></textarea>
                        <p class="hint">The patient can read these notes on their appointments page.</p>
                        <?php if ($appointment['notes_updated_at']): ?>
                            <p class="hint">Last updated <?= htmlspecialchars(date('j M Y, g:i A', strtotime($appointment['notes_updated_at']))) ?>.</p>
                        <?php endif; ?>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="appointment.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <aside class="panel">
            <p class="panel__label">Appointment</p>
            <ul class="detail-list">
                <li><span>Patient</span><strong><?= htmlspecialchars($appointment['pname']) ?></strong></li>
                <li><span>Date of birth</span><strong><?= date('j M Y', strtotime($appointment['pdob'])) ?></strong></li>
                <li><span>Date</span><strong><?= date('D j M Y', strtotime($appointment['adate'])) ?></strong></li>
                <li><span>Time</span><strong><?= date("g:i A", strtotime($appointment['start_time'])) ?> - <?= date("g:i A", strtotime($appointment['end_time'])) ?></strong></li>
            </ul>
            <div style="margin-top: 16px">
                <?php if ($appointment['shared_pdid']): ?>
                    <a class="btn btn-light btn-block" href="viewAssessment.php?apid=<?= (int)$appointment['apid'] ?>">View shared heart check</a>
                <?php else: ?>
                    <span class="badge badge-muted">Heart check not shared by the patient</span>
                <?php endif; ?>
            </div>
        </aside>
    </div>
<?php endif; ?>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
