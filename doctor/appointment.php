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
            return "<span class='status status-completed'>&#10003; Completed</span>";
        }
        if ($row['status'] === 'no_show') {
            return "<span class='status status-no-show'>&#10007; No-show</span>";
        }
        return $row['adate'] < $today
            ? "<span class='status status-pending'>&#9679; Needs update</span>"
            : "<span class='status status-booked'>&#9679; Booked</span>";
    }

    function renderAppointmentRows(array $rows, $today, $emptyText)
    {
        if (!$rows) {
            echo "<tr><td colspan='6'>" . $emptyText . "</td></tr>";
            return;
        }
        foreach ($rows as $row) {
            $start_time = date("h:i A", strtotime($row["start_time"]));
            $end_time = date("h:i A", strtotime($row["end_time"]));
            echo "<tr>";
            echo "<td>".htmlspecialchars($row['pname'])."</td>";
            echo "<td>".htmlspecialchars($row['adate'])."</td>";
            echo "<td>".$start_time . ' - ' . $end_time."</td>";

            echo "<td>";
            if ($row['shared_pdid']) {
                echo "<a class='assessment-link' href='viewAssessment.php?apid=".(int)$row['apid']."'>View assessment</a>";
            } else {
                echo "<span class='muted'>Not shared</span>";
            }
            echo "</td>";

            echo "<td>" . statusBadge($row, $today) . "</td>";

            echo "<td>";
            if ($row['adate'] > $today) {
                echo "<span class='muted'>From " . htmlspecialchars($row['adate']) . "</span>";
            } else {
                $label = ($row['status'] === 'booked' && trim((string)$row['doctor_notes']) === '')
                    ? 'Record visit' : 'View / edit notes';
                echo "<a class='assessment-link' href='consultation.php?apid=".(int)$row['apid']."'>" . $label . "</a>";
            }
            echo "</td>";

            echo "</tr>";
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }

        .container {
            display: flex;
            flex: 1;
        }

        .main-content {
            flex-grow: 1;
            background-color: #ffffff;
            padding: 30px;
            box-sizing: border-box;
            height: -webkit-fill-available;
            display: flex;
            flex-direction: column;
        }

        .main-content h1 {
            margin-top: 0;
            font-size: 24px;
            color: #333333;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: #777777;
        }

        .breadcrumb a {
            text-decoration: none;
            color: #00a99d;
        }

        .profile-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: auto;
            flex-grow: 1;
        }

        .profile-form h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-group.inline {
            display: flex;
            justify-content: space-between;
        }

        .form-group.inline .form-control {
            width: 48%;
        }

        .height-inputs {
            display: flex;
            gap: 10px;
        }

        .height-inputs .form-control {
            width: calc(50% - 5px);
        }

        .update-btn {
            background-color: #00a99d;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            display: block;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #cccccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #00a99d;
            color: #ffffff;
        }

        .muted { color: #9aa0a6; }

        .profile-form h2.section-gap { margin-top: 36px; }

        .notice {
            background: #e6f6f1;
            border: 1px solid #a6ddc9;
            border-radius: 5px;
            padding: 10px 14px;
            color: #1f6f5c;
        }

        .status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.85em;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-booked    { background: #ebf4ff; color: #2c5282; }
        .status-pending   { background: #fffaf0; color: #9c4221; border: 1px solid #fbd38d; }
        .status-completed { background: #f0fff4; color: #276749; }
        .status-no-show   { background: #fff5f5; color: #c53030; }

        .assessment-link {
            margin-top: 0;
            font-weight: 500;
        }

        a {
            text-decoration: none;
            color: #00a99d;
            display: inline-block;
            margin-top: 20px;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <?php include("sidebar.php");?>

        <div class="main-content">
            <h1>Appointment</h1>
            <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt; <span>My Appointments</span>
        </div>
        <div class="profile-form">
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <p class="notice">Consultation saved.</p>
            <?php endif; ?>

            <h2>Today and upcoming</h2>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Heart Assessment</th>
                        <th>Status</th>
                        <th>Consultation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php renderAppointmentRows($upcoming, $today, 'No upcoming appointments'); ?>
                </tbody>
            </table>

            <h2 class="section-gap">Past appointments</h2>
            <p class="muted">Mark each visit as completed or no-show, and add notes for the patient.</p>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Heart Assessment</th>
                        <th>Status</th>
                        <th>Consultation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php renderAppointmentRows($past, $today, 'No past appointments'); ?>
                </tbody>
            </table>
            <br><br>
            <a href="index.php">Back</a>
        </div>
        </div>
</div>
</body>
</html>
