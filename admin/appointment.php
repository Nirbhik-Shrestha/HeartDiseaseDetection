<?php
    session_start();

    if(isset($_SESSION['user'])){
        if(($_SESSION['user'])==''){
            header("location: adminLogin.php");
        }else{
            $useremail = $_SESSION['user'];
        }
    }else{
        header("location: adminLogin.php");
    }

    // Database connection
    include("../connection.php");

    // Todays date
    $today = date('Y-m-d');

    // Fetch sessions from the database
    $sql = "SELECT appointment.*, patients.pname AS pname, doctors.dname AS dname, timeslot.start_time AS time
    FROM appointment 
    INNER JOIN patients ON appointment.pid = patients.pid 
    INNER JOIN timeslot ON appointment.tid = timeslot.tid 
    INNER JOIN schedule ON timeslot.scid = schedule.scid 
    INNER JOIN doctors ON schedule.did = doctors.did 
    WHERE appointment.adate >= '$today'
    ORDER BY appointment.adate ASC
    ";
    $result = $con->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Appointment</title>
    <link rel="stylesheet" href="../css/adminIndex.css">
</head>
<body>
    <div class="container">
    <?php include 'sidebar.php' ?>
        <div class="main-content">
            <div class="table-container">
            <h1>Appointment</h1>
            <table border="1">
                <thead>
                    <tr>
                        <!-- <th>Appointment Title</th> -->
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                $stime = $row["time"];
                                $stime_12hr = date("h:i A", strtotime($stime));
                                echo "<tr>";
                                echo "<td>".$row['pname']."</td>";
                                echo "<td>".$row['dname']."</td>";
                                echo "<td>".$row['adate']."</td>";
                                echo "<td>".$stime_12hr."</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No appointments available</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
        </div>
        </div>
</body>
</html>
