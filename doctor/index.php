<?php 

    //import database
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'doctor');
    $useremail = $userfetch["demail"];
    $userid= $userfetch["did"];
    $username=$userfetch["dname"];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard</title>
    <style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    background-color: #f4f7f6;
}

.container {
    width: 90%;
    margin: 0 auto;
    padding: 20px;
}

main {
    margin: 40px 0;
}

main h2 {
    text-align: center;
    color: #333;
    font-weight: 300;
}

main p {
    text-align: center;
    color: #666;
}

.cards {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
    margin-top: 30px;
}

.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    width: 30%;
    margin: 10px;
    text-align: center;
    transition: transform 0.3s, box-shadow 0.3s;
    display: grid;
    gap: 10px;
}

.card h3 {
    margin-top: 0;
    color: #00a99d;
}

.card p {
    color: #555;
    font-size: 1em;
}

.card ul {
    list-style: none;
    padding: 0;
}

.card ul li {
    margin: 5px 0;
    color: #333;
    padding-top: 5px;
}

.card .btn {
    display: inline-block;
    background: #00a99d;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 10px;
    transition: background 0.3s;
    width: fit-content;
    height: fit-content;
    align-self: center;
    justify-self: center;
}

.card .btn:hover {
    background: #007d73;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}

/* footer {
    background: #333;
    color: white;
    text-align: center;
    padding: 10px 0;
} */

.banner{
    height: 300px;
    background-image: url(../images/drbanner.jpg);
    background-repeat: no-repeat;
    align-content: center;
}

.banner h2 {
    text-align: center;
    color: #333;
    font-weight: 300;
}

.banner p {
    text-align: center;
    color: #666;
}

    </style>

    <link rel="stylesheet" href="../css/index.css">
</head>
<body>

    <?php include("../doctorHeader.html"); ?>
    
    <div class="banner">
    <h2>Welcome, Dr. <?php echo ($username) ?></h2>
    <p>Here is your overview for today:</p>
    </div>
    <main class="container">
        <section id="overview">
            <div class="cards">
                <div class="card">
                    <h3>Upcoming Appointments</h3>
                    <p>Number of Appointments: 
                    
                    <?php
                    
                            date_default_timezone_set('Asia/Kathmandu');
                            $today = date('Y-m-d');
                            $sql = "SELECT COUNT(*) AS total, p.pname AS pname FROM appointment a
                            INNER JOIN patients p ON a.pid = p.pid 
                        INNER JOIN timeslot t ON a.tid = t.tid 
                        INNER JOIN schedule s ON t.scid = s.scid 
                        INNER JOIN doctors d ON s.did = d.did 
                            WHERE d.dname = '$username' AND a.adate >= '$today'";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo $row["total"];
                            } else {
                                echo "";
                            }
                    ?>
                    
                    </p>
                    <ul>
                        <?php 
                        $sql = "SELECT COUNT(*) AS total, p.pname AS pname, a.adate AS adate, t.start_time AS stime
                        FROM appointment a
                        INNER JOIN patients p ON a.pid = p.pid 
                        INNER JOIN timeslot t ON a.tid = t.tid 
                        INNER JOIN schedule s ON t.scid = s.scid 
                        INNER JOIN doctors d ON s.did = d.did 
                        WHERE d.dname = '$username' AND a.adate >= '$today'
                        GROUP BY p.pname
                        ORDER BY a.adate ASC
                        LIMIT 5";
                        $result = $con->query($sql);
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                $stime = $row["stime"];
                                // Convert 24-hour time format to 12-hour format with AM/PM
                                $stime_12hr = date("h:i A", strtotime($stime));
                                ?>

                                <?php echo "<li>".$row['adate'].'&emsp;'.$stime_12hr.'<br>'.$row['pname']."</li>"?>

                                <?php
                                }
                            } else {
                                echo "No appointments available";
                            } ?>

                    </ul>
                    <a href="appointment.php" class="btn">View All</a>
                </div>
                <div class="card">
                    <h3>My Patient</h3>
                    <p>Number of Patients: 

                    <?php
                    
                            $sql = "SELECT COUNT(DISTINCT p.pid) AS total, p.pname AS pname FROM patients p
                            INNER JOIN appointment a ON a.pid = p.pid 
                        INNER JOIN timeslot t ON a.tid = t.tid 
                        INNER JOIN schedule s ON t.scid = s.scid 
                        INNER JOIN doctors d ON s.did = d.did
                            WHERE d.dname = '$username'";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                $row = $result->fetch_assoc();
                                echo $row["total"];
                            } else {
                                echo "";
                            }
                    ?>

                    </p>
                    <ul>
                        <?php 
                        $sql = "SELECT COUNT(*) AS total, p.pname AS pname
                        FROM appointment a
                        INNER JOIN patients p ON a.pid = p.pid 
                        INNER JOIN timeslot t ON a.tid = t.tid 
                        INNER JOIN schedule s ON t.scid = s.scid 
                        INNER JOIN doctors d ON s.did = d.did
                        WHERE d.dname = '$username'
                        GROUP BY p.pname
                        LIMIT 5";
                        $result = $con->query($sql);
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                ?>

                                <?php echo "<li>".$row['pname']."</li>"?>

                                <?php
                                }
                            } else {
                                echo "No patients available";
                            } ?>
                    </ul>
                    <a href="patients.php" class="btn">View All</a>
                </div>
                <div class="card">
                    <h3>Notifications</h3>
                    <p>No new notifications</p>
                </div>
            </div>
        </section>
    </main>

    <!-- <footer>
        <div class="container">
            <p>&copy; 2024 DaaktarSahab. All rights reserved.</p>
        </div>
    </footer> -->

    <?php include ('../footer.html') ?>
</body>
</html>
