<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION["user"])) {
        $_SESSION["user"] = "";  // Only set to empty if it's not already set
    }

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])==""){
            header("location: doctorLogin.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: doctorLogin.php");
    }

    include("../connection.php");

    $userrow = $con->query("SELECT * from doctors where demail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["did"];
    $username=$userfetch["dname"];

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
    AND appointment.adate >= ?
    ORDER BY appointment.adate ASC, timeslot.start_time ASC
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("is", $userid, $today);
    $stmt->execute();
    $result = $stmt->get_result();
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
            <h2>All your future appointments</h2>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Heart Assessment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
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

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No appointments available</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
            <br><br>
            <a href="index.php">Back</a>
        </div>
        </div>
</div>
</body>
</html>
