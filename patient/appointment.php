<?php
    session_start();

    if (!isset($_SESSION["user"])) {
        $_SESSION["user"] = "";  // Only set to empty if it's not already set
    }

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])==""){
            header("location: usersLogin.php");
        }else{
            $useremail=$_SESSION["user"];
        }
    }else{
        header("location: usersLogin.php");
    }

    include("../connection.php");

    $today = date('Y-m-d');


    $sqlPatient = "SELECT * FROM patients WHERE pemail = '$useremail'";
    $resultPatient = $con->query($sqlPatient);
    if($resultPatient->num_rows > 0){
        $rowPatient = $resultPatient->fetch_assoc();
        $userid = $rowPatient['pid'];
        $username = $rowPatient['pname'];
    }else{
        header("location: usersLogin.php");
    }

    // Fetch sessions from the database
    $sql = "SELECT appointment.*, patients.pname AS pname, doctors.dname AS dname, timeslot.start_time AS time
    FROM appointment 
    INNER JOIN patients ON appointment.pid = patients.pid 
    INNER JOIN timeslot ON appointment.tid = timeslot.tid 
    INNER JOIN schedule ON timeslot.scid = schedule.scid 
    INNER JOIN doctors ON schedule.did = doctors.did 
    WHERE patients.pid = $userid
    AND appointment.adate >= '$today'
    ORDER BY appointment.adate ASC
    ";
    $result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="../css/patientIndex.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <?php include 'sidebar.html';?>
    <div class="main-content">
        <h1>Appointment</h1>
        <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt; <span>My Appointments</span>
        </div>
        <div class="profile-form">
            <h2>All your future appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                echo "<tr>";
                                echo "<td>Dr. ".htmlspecialchars($row['dname'])."</td>";
                                echo "<td>".htmlspecialchars($row['adate'])."</td>";
                                echo "<td>".htmlspecialchars($row['time'])."</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No appointments available</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
        </div>
    </div>
</div>
</body>
</html>
