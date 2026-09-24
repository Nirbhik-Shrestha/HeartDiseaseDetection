<?php
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'doctor');
    $useremail = $userfetch["demail"];
    $userid= $userfetch["did"];
    $username=$userfetch["dname"];

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    // Matched on did, not on name: two doctors may share a name, which would
    // otherwise show one doctor the other's appointments.
    // shared_pdid is set when the patient chose to share a heart reading.
    $sql = "SELECT appointment.*, patients.pname AS pname, doctors.dname AS dname,
                   timeslot.start_time, timeslot.end_time,
                   (SELECT pd.pdid FROM patient_data pd
                     WHERE pd.apid = appointment.apid LIMIT 1) AS shared_pdid
    FROM appointment
    INNER JOIN patients ON appointment.pid = patients.pid
    INNER JOIN timeslot ON appointment.tid = timeslot.tid
    INNER JOIN schedule ON timeslot.scid = schedule.scid
    INNER JOIN doctors ON schedule.did = doctors.did
    WHERE schedule.did = ?
    ORDER BY appointment.adate ASC, timeslot.start_time ASC
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $upcoming = [];
    $past = [];
    foreach ($all as $row) {
        if ($row['adate'] >= $today) {
            $upcoming[] = $row;
        } else {
            $past[] = $row;
        }
    }
    $past = array_reverse($past); // most recent first

    /** Status badge; a past visit still "booked" is waiting for the doctor. */
    function statusBadge($row, $today)
    {
        if ($row['status'] === 'completed') {
            return "<span class='badge badge-success'>&#10003; Completed</span>";
        }
        if ($row['status'] === 'no_show') {
            return "<span class='badge badge-danger'>&#10007; No-show</span>";
        }
        return $row['adate'] < $today
            ? "<span class='badge badge-warning'>Needs update</span>"
            : "<span class='badge badge-info'>Booked</span>";
    }

    /** Table rows for one list of appointments. */
    function renderAppointmentRows(array $rows, $today)
    {
        foreach ($rows as $row) {
            $range = date("g:i A", strtotime($row["start_time"])) . ' - ' . date("g:i A", strtotime($row["end_time"]));
            $day = $row['adate'] === $today ? 'Today' : date('D j M Y', strtotime($row['adate']));

            echo "<tr>";
            echo "<td class='cell-strong' data-label='Patient'>" . htmlspecialchars($row['pname']) . "</td>";
            echo "<td class='nowrap' data-label='Date &amp; time'><div><span class='cell-strong'>" . $day
               . "</span><br><span class='cell-muted'>" . $range . "</span></div></td>";

            echo "<td data-label='Heart check'>";
            if ($row['shared_pdid']) {
                echo "<a class='btn btn-light btn-sm' href='viewAssessment.php?apid=" . (int)$row['apid'] . "'>View shared check</a>";
            } else {
                echo "<span class='cell-muted'>Not shared</span>";
            }
            echo "</td>";

            echo "<td data-label='Status'>" . statusBadge($row, $today) . "</td>";

            echo "<td>";
            if ($row['adate'] > $today) {
                echo "<span class='cell-muted'>Record from " . date('j M', strtotime($row['adate'])) . "</span>";
            } else {
                $needsRecord = $row['status'] === 'booked' && trim((string)$row['doctor_notes']) === '';
                echo "<a class='btn " . ($needsRecord ? "btn-primary" : "btn-light") . " btn-sm' href='consultation.php?apid=" . (int)$row['apid'] . "'>"
                   . ($needsRecord ? 'Record visit' : 'View / edit notes') . "</a>";
            }
            echo "</td>";
            echo "</tr>";
        }
    }

    $saved = isset($_GET['msg']) && $_GET['msg'] === 'saved';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">My appointments</span>
        <h1>Your appointments</h1>
        <p>See who is booked with you, open heart checks they have shared, and record each visit afterwards.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($saved): ?>
        <div class="notice notice-ok">Consultation saved. The patient can now see your notes.</div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Today and upcoming</h2>
                <p class="panel__sub"><?= count($upcoming) ?> <?= count($upcoming) === 1 ? 'appointment' : 'appointments' ?></p>
            </div>
            <a href="schedule.php" class="btn btn-light btn-sm">Manage sessions</a>
        </div>
        <?php if ($upcoming): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr><th>Patient</th><th>Date &amp; time</th><th>Heart check</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php renderAppointmentRows($upcoming, $today); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No upcoming appointments.</p>
                <a href="schedule.php" class="btn btn-light">Add a session</a>
            </div>
        <?php endif; ?>
    </section>

    <section class="panel" id="past">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Past appointments</h2>
                <p class="panel__sub">Mark each visit as completed or no-show, and add notes for the patient.</p>
            </div>
        </div>
        <?php if ($past): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr><th>Patient</th><th>Date &amp; time</th><th>Heart check</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php renderAppointmentRows($past, $today); ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><p>No past appointments yet.</p></div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
