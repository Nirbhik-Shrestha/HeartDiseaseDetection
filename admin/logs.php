<?php
    // Database connection
    include("../connection.php");
    include_once("../auth.php");

    $admin = requireRole($con, 'admin');
    $useremail = $admin['aemail'];

    $today = date('Y-m-d');

    // Fetch sessions from the database
    $sql = "SELECT schedule.*, doctors.dname 
            FROM schedule 
            INNER JOIN doctors ON schedule.did = doctors.did 
            WHERE schedule.sdate < '$today'
            ORDER BY schedule.sdate DESC
            ";
    $result = $con->query($sql);


    // if(isset($_GET['remove']))
    // {
    //     $remove_id=$_GET['remove'];
    //     // echo $remove_id;
    //     $deleteSql = "
        
    //     DELETE FROM appointment WHERE scid = '$remove_id';
    //     DELETE FROM schedule WHERE scid='$remove_id';
        
    //     ";

    //     if(mysqli_multi_query($con, $deleteSql))
    //     {
    //         echo "<script>
    //                     alert('Deleted successfully.');
    //                 </script>";
    //         header('location: schedule.php');
    //     }else{
    //         echo "Error: " . mysqli_error($con);
    //     }
    // }

    $appsql = "SELECT appointment.*, patients.pname AS pname, doctors.dname AS dname, timeslot.start_time AS time
    FROM appointment 
    INNER JOIN patients ON appointment.pid = patients.pid 
    INNER JOIN timeslot ON appointment.tid = timeslot.tid 
    INNER JOIN schedule ON timeslot.scid = schedule.scid 
    INNER JOIN doctors ON schedule.did = doctors.did 
    WHERE appointment.adate < '$today'
    ORDER BY appointment.adate DESC
    ";
    $appresult = $con->query($appsql);


?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule</title>
    <link rel="stylesheet" href="../css/adminIndex.css">
    
</head>
<body>
    <div class="container">
    <?php include 'sidebar.php' ?>
        <div class="main-content">
            <div class="table-container">
                
                <h1>Past Schedules</h1>
                <table border="1">
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
                                $stime = $row["time"];
                                $stime_12hr = date("h:i A", strtotime($stime));

                                echo "<tr>";
                                echo "<td>".$row['dname']."</td>";
                                echo "<td>".$row['sdate']."</td>";
                                echo "<td>".$stime_12hr."</td>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No sessions available</td></tr>";
                        }
                    ?>
                </tbody>
                </table>
            </div>


            <div class="table-container">
                <h1>Past Appointments</h1>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if($result->num_rows > 0){
                                while($row = $appresult->fetch_assoc()){
                                    $stime = $row["time"];
                                    $stime_12hr = date("h:i A", strtotime($stime));
                                    echo "<tr>";
                                    echo "<td>".$row['pname']."</td>";
                                    echo "<td>".$row['dname']."</td>";
                                    echo "<td>".$row['adate']."</td>";
                                    echo "<td>".$stime_12hr."</td>";
                                    $statusText = ['booked' => 'Not recorded', 'completed' => 'Completed', 'no_show' => 'No-show'];
                                    echo "<td>".htmlspecialchars($statusText[$row['status']] ?? $row['status'])."</td>";
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
