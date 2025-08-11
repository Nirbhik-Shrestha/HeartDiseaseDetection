<?php
    session_start();

    // Check if user is logged in
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

    $today = date('Y-m-d');

    // Fetch sessions from the database
    $sql = "SELECT schedule.*, doctors.dname 
            FROM schedule 
            INNER JOIN doctors ON schedule.did = doctors.did 
            WHERE schedule.sdate >= '$today'
            ORDER BY schedule.sdate ASC
            ";
    $result = $con->query($sql);


    if(isset($_GET['remove']))
    {
        $remove_id=$_GET['remove'];
        // echo $remove_id;
        $deleteSql = "
        
        DELETE FROM appointment WHERE scid = '$remove_id';
        DELETE FROM schedule WHERE scid='$remove_id';
        
        ";

        if(mysqli_multi_query($con, $deleteSql))
        {
            echo "<script>
                        alert('Deleted successfully.');
                    </script>";
            header('location: schedule.php');
        }else{
            echo "Error: " . mysqli_error($con);
        }
    }

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
                
                <h1>Schedule</h1>
                <a href="addsession.php">Add New Session</a><br><br>
            <table border="1">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){

                                echo "<tr>";
                                echo "<td>".$row['dname']."</td>";
                                echo "<td>".$row['sdate']."</td>";
                                echo "<td>" ?> <a href="schedule.php?remove=<?php echo $row['scid']?>" 
                                class="delete-btn" onclick="return confirm('Remove this schedule?');">Remove</a><?php "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No sessions available</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</body>
</html>
