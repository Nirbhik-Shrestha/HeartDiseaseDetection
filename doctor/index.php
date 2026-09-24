<?php
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'doctor');
    $useremail = $userfetch["demail"];
    $userid    = (int)$userfetch["did"];
    $username  = $userfetch["dname"];

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    /** Run a prepared query for this doctor and return all rows. */
    function doctorRows($con, $sql, $types, ...$params)
    {
        $stmt = $con->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // Every query is matched on the doctor's id, never their name: two
    // doctors can share a name.
    $appointmentSelect = "SELECT a.apid, a.adate, t.start_time, t.end_time, p.pname,
                                 (SELECT pd.pdid FROM patient_data pd WHERE pd.apid = a.apid LIMIT 1) AS shared_pdid
                            FROM appointment a
                            JOIN timeslot t ON t.tid = a.tid
                            JOIN schedule s ON s.scid = t.scid
                            JOIN patients p ON p.pid = a.pid";

    // Today's visits still to be seen, in time order.
    $todayVisits = doctorRows($con,
        "$appointmentSelect WHERE s.did = ? AND a.adate = ? AND a.status = 'booked' ORDER BY t.start_time",
        "is", $userid, $today);

    // Next five booked visits from today on, plus how many there are in all.
    $nextVisits = doctorRows($con,
        "$appointmentSelect WHERE s.did = ? AND a.adate >= ? AND a.status = 'booked'
         ORDER BY a.adate, t.start_time LIMIT 5",
        "is", $userid, $today);
    $upcomingCount = (int)doctorRows($con,
        "SELECT COUNT(*) AS n FROM appointment a JOIN timeslot t ON t.tid = a.tid JOIN schedule s ON s.scid = t.scid
          WHERE s.did = ? AND a.adate >= ? AND a.status = 'booked'",
        "is", $userid, $today)[0]['n'];

    // Past visits the doctor has not yet marked completed or no-show.
    $needsUpdate = (int)doctorRows($con,
        "SELECT COUNT(*) AS n FROM appointment a JOIN timeslot t ON t.tid = a.tid JOIN schedule s ON s.scid = t.scid
          WHERE s.did = ? AND a.adate < ? AND a.status = 'booked'",
        "is", $userid, $today)[0]['n'];

    // Upcoming sessions and how many of their slots are still free.
    $sessions = doctorRows($con,
        "SELECT COUNT(DISTINCT s.scid) AS sessions,
                SUM(CASE WHEN t.tid IS NOT NULL AND a.apid IS NULL THEN 1 ELSE 0 END) AS free_slots
           FROM schedule s
           LEFT JOIN timeslot t ON t.scid = s.scid
           LEFT JOIN appointment a ON a.tid = t.tid
          WHERE s.did = ? AND s.sdate >= ?",
        "is", $userid, $today)[0];
    $sessionCount = (int)$sessions['sessions'];
    $freeSlots    = (int)$sessions['free_slots'];

    // Patients who have booked with this doctor, most recent visit first.
    $patients = doctorRows($con,
        "SELECT p.pname, MAX(a.adate) AS last_visit
           FROM appointment a
           JOIN timeslot t ON t.tid = a.tid
           JOIN schedule s ON s.scid = t.scid
           JOIN patients p ON p.pid = a.pid
          WHERE s.did = ?
          GROUP BY p.pid, p.pname
          ORDER BY last_visit DESC",
        "i", $userid);
    $con->close();

    function timeRange($row)
    {
        return date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time']));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        /* ---- Banner (drbanner.jpg: plain teal on the left, doctor on the right) ---- */
        .doctor-hero {
            flex: 0 0 auto;
            position: relative;
            background: #9fd9d8 url("../images/drbanner.jpg") right top / cover no-repeat;
        }

        .doctor-hero__inner {
            position: relative;
            max-width: 1120px;
            margin: 0 auto;
            padding: 56px 32px 112px;
        }

        .doctor-hero h1 {
            max-width: 560px;
            margin: 0 0 10px;
            color: #12304a;
            font-size: clamp(30px, 3.8vw, 46px);
            line-height: 1.1;
        }

        .doctor-hero h1 span {
            color: #00786f;
        }

        .doctor-hero p {
            max-width: 480px;
            margin: 0;
            color: #1f3d52;
            font-size: 18px;
            line-height: 1.55;
        }

        .doctor-hero .page-hero__eyebrow {
            background: rgba(255, 255, 255, 0.6);
        }

        .doctor-hero .btn-row {
            margin-top: 24px;
        }

        /* ---- Summary cards floating over the banner ---- */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 0 0 24px;
        }

        .stat-card {
            display: flex;
            flex-direction: column;
            padding: 22px 24px 20px;
            background: #fff;
            border: 1px solid #e3ebf0;
            border-top: 3px solid #00a99d;
            border-radius: 16px;
            box-shadow: 0 1px 2px rgba(18, 48, 74, 0.06), 0 12px 32px -12px rgba(18, 48, 74, 0.18);
        }

        .stat-card.is-attention {
            border-top-color: #e0a100;
        }

        .stat-number {
            margin: 0 0 4px;
            color: #12304a;
            font-size: 38px;
            font-weight: 800;
            line-height: 1;
        }

        .stat-text {
            margin: 0;
            color: #3d566b;
            font-size: 15.5px;
            line-height: 1.5;
        }

        .stat-link {
            margin-top: auto;
            padding-top: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .stat-link::after {
            content: " \2192";
        }

        .site-page main .stat-link:hover {
            text-decoration: underline;
        }

        .patient-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .patient-list li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #eef3f6;
            font-size: 16px;
        }

        .patient-list li:last-child {
            border-bottom: none;
        }

        .patient-list strong {
            color: #12304a;
        }

        .patient-list span {
            color: #6b8193;
            font-size: 14.5px;
            white-space: nowrap;
        }

        @media (max-width: 900px) {
            .doctor-hero {
                background-position: 70% top;
            }

            /* On phones the text sits over the doctor, so soften the photo
               behind it (the same wash the other banners use on phones). */
            .doctor-hero::before {
                content: "";
                position: absolute;
                top: 0;
                right: 0;
                bottom: 0;
                left: 0;
                background: linear-gradient(180deg, rgba(234, 247, 251, 0.9), rgba(234, 247, 251, 0.6));
            }

            .doctor-hero__inner {
                padding: 36px 16px 88px;
            }
        }
    </style>
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="doctor-hero">
    <div class="doctor-hero__inner">
        <span class="page-hero__eyebrow">Doctor dashboard</span>
        <h1>Welcome, <span>Dr. <?= htmlspecialchars($username) ?></span></h1>
        <p>Today is <?= date('l j F Y') ?>. Here is your overview.</p>
        <div class="btn-row">
            <a href="appointment.php" class="btn btn-primary">My appointments</a>
            <a href="schedule.php" class="btn btn-light">Add a session</a>
        </div>
    </div>
</section>

<main class="page-body">
    <div class="stat-grid">
        <div class="stat-card">
            <p class="panel__label">Today</p>
            <p class="stat-number"><?= count($todayVisits) ?></p>
            <p class="stat-text">
                <?php if ($todayVisits): ?>
                    <?= count($todayVisits) === 1 ? 'appointment' : 'appointments' ?> today. Next:
                    <strong><?= htmlspecialchars($todayVisits[0]['pname']) ?></strong>
                    at <?= date('g:i A', strtotime($todayVisits[0]['start_time'])) ?>.
                <?php else: ?>
                    No appointments today.
                <?php endif; ?>
            </p>
            <a class="stat-link" href="appointment.php">View appointments</a>
        </div>

        <div class="stat-card <?= $needsUpdate > 0 ? 'is-attention' : '' ?>">
            <p class="panel__label">Needs your update</p>
            <p class="stat-number"><?= $needsUpdate ?></p>
            <p class="stat-text">
                <?= $needsUpdate > 0
                    ? ($needsUpdate === 1 ? 'past visit is' : 'past visits are') . ' not yet marked completed or no-show.'
                    : 'All your past visits are recorded.' ?>
            </p>
            <a class="stat-link" href="appointment.php#past">Record visits</a>
        </div>

        <div class="stat-card">
            <p class="panel__label">Your sessions</p>
            <p class="stat-number"><?= $sessionCount ?></p>
            <p class="stat-text">
                <?php if ($sessionCount > 0): ?>
                    upcoming <?= $sessionCount === 1 ? 'session' : 'sessions' ?>, with <?= $freeSlots ?> open <?= $freeSlots === 1 ? 'slot' : 'slots' ?> for patients to book.
                <?php else: ?>
                    No upcoming sessions. Add one so patients can book you.
                <?php endif; ?>
            </p>
            <a class="stat-link" href="schedule.php">Manage sessions</a>
        </div>
    </div>

    <div class="page-grid">
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2 class="panel__title">Next appointments</h2>
                    <p class="panel__sub"><?= $upcomingCount ?> booked from today onwards</p>
                </div>
                <a href="appointment.php" class="btn btn-light btn-sm">View all</a>
            </div>

            <?php if ($nextVisits): ?>
                <div class="table-scroll">
                    <table class="data-table stack">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Date &amp; time</th>
                                <th>Heart check</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($nextVisits as $row): ?>
                                <tr>
                                    <td class="cell-strong" data-label="Patient"><?= htmlspecialchars($row['pname']) ?></td>
                                    <td class="nowrap" data-label="Date &amp; time">
                                        <div>
                                            <span class="cell-strong"><?= $row['adate'] === $today ? 'Today' : date('D j M', strtotime($row['adate'])) ?></span><br>
                                            <span class="cell-muted"><?= timeRange($row) ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Heart check">
                                        <?php if ($row['shared_pdid']): ?>
                                            <a class="btn btn-light btn-sm" href="viewAssessment.php?apid=<?= (int)$row['apid'] ?>">View shared check</a>
                                        <?php else: ?>
                                            <span class="badge badge-muted">Not shared</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>No upcoming appointments yet.</p>
                    <a href="schedule.php" class="btn btn-light">Add a session</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="panel">
            <p class="panel__label">Your patients</p>
            <p class="stat-number"><?= count($patients) ?></p>
            <p class="stat-text" style="margin-bottom: 12px"><?= count($patients) === 1 ? 'patient has' : 'patients have' ?> booked with you.</p>
            <?php if ($patients): ?>
                <ul class="patient-list">
                    <?php foreach (array_slice($patients, 0, 5) as $p): ?>
                        <li>
                            <strong><?= htmlspecialchars($p['pname']) ?></strong>
                            <span><?= date('j M Y', strtotime($p['last_visit'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="btn btn-light btn-sm btn-block" style="margin-top: 14px" href="patients.php">View all patients</a>
        </aside>
    </div>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
