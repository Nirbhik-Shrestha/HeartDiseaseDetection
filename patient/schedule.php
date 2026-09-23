<?php

        date_default_timezone_set('Asia/Kathmandu');
        $today = date('Y-m-d');

        include("../connection.php");
        include_once("../auth.php");

        $userfetch = requireRole($con, 'patient');
        $useremail = $userfetch["pemail"];
        $userid = $userfetch["pid"];
        $username = $userfetch["pname"];
        
        
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Sessions</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- <link rel="stylesheet" href="../css/style.css"> -->
    <link rel="stylesheet" href="../css/index.css">
    <style>
        * {
            font-family: 'Platin', Times, serif;
            margin: 0;
            padding: 0;
        }
       
.table-container {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.table-container h2 {
    margin-bottom: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 12px;
    border: 1px solid #dddddd;
    text-align: left;
}

table th {
    background-color: #f4f4f4;
}

.book-btn {
    background-color: #00a99d;
    color: #ffffff;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 5px;
}

.book-btn-disabled,
.book-btn-disabled:hover {
    background-color: #c9ced3;
    color: #ffffff;
    cursor: not-allowed;
}

.slots-ok   { color: #0b7d56; font-weight: 500; }
.slots-low  { color: #b26a00; font-weight: 500; }
.slots-none { color: #9aa0a6; }

        .home-button {
            /* position: absolute; */
            top: 20px;
            left: 20px;
            background-color: #36A9AE;
            border: none;
            border-radius: 4px;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .home-button:hover {
            background-color: #2A8387;
        }

        body {
    font-family: 'Roboto', sans-serif;
    margin: 0;
    padding: 0;
}

main{
    padding: 10px;
    padding-left: 60px;
    padding-right: 60px;
}

    </style>
</head>
<body>
<!-- Header -->
<?php include('../patientHeader.html')?>



<!-- Main-Content -->
    <main>
    <?php
        

        
    // Every branch reports the session's capacity alongside it, so a partly
    // booked session is never mistaken for an empty one:
    //   total_slots = timeslots in the session
    //   booked_slots = those already taken (by anyone)
    $select = "SELECT schedule.scid, schedule.sdate, doctors.dname, specialties.sname,
                      (SELECT COUNT(*) FROM timeslot t WHERE t.scid = schedule.scid) AS total_slots,
                      (SELECT COUNT(*) FROM timeslot t
                         INNER JOIN appointment a ON a.tid = t.tid
                        WHERE t.scid = schedule.scid) AS booked_slots
               FROM schedule
               INNER JOIN doctors ON schedule.did = doctors.did
               INNER JOIN specialties ON doctors.spid = specialties.spid";

    if ($_GET && isset($_GET['did'])) {
        $doctor_id = $_GET['did'];
        $sqlmain = "$select WHERE schedule.did = '$doctor_id' AND schedule.sdate >= '$today' ORDER BY schedule.sdate ASC";
    } elseif ($_POST && !empty($_POST["search"])) {
        $keyword = $_POST["search"];
        $sqlmain = "$select
        WHERE schedule.sdate >= '$today'
        AND (doctors.dname LIKE '%$keyword%'
             OR specialties.sname LIKE '%$keyword%'
             OR schedule.sdate LIKE '%$keyword%')
        ORDER BY schedule.sdate ASC";
} else {
        $sqlmain = "$select WHERE schedule.sdate >= '$today' ORDER BY schedule.sdate ASC";
    }

        $result = $con->query($sqlmain);

        if($result->num_rows > 0){
            echo "<div class='table-container'>";
            echo "<h2>Available Sessions</h2>";
            echo "<table>";
            echo "<thead><tr><th>Doctor</th><th>Specialty</th><th>Date</th><th>Availability</th><th>Action</th></tr></thead>";
            echo "<tbody>";
            while($row = $result->fetch_assoc()){
                $total = (int)$row['total_slots'];
                $free  = $total - (int)$row['booked_slots'];

                echo "<tr>";
                echo "<td>Dr. ".htmlspecialchars($row['dname'])."</td>";
                echo "<td>".htmlspecialchars($row['sname'])."</td>";
                echo "<td>".htmlspecialchars($row['sdate'])."</td>";

                if ($total === 0) {
                    echo "<td><span class='slots-none'>No timeslots</span></td>";
                    echo "<td><button class='book-btn book-btn-disabled' disabled>Unavailable</button></td>";
                } elseif ($free === 0) {
                    echo "<td><span class='slots-none'>0 of $total left</span></td>";
                    echo "<td><button class='book-btn book-btn-disabled' disabled>Fully Booked</button></td>";
                } else {
                    $cls = ($free <= 2) ? 'slots-low' : 'slots-ok';
                    echo "<td><span class='$cls'>$free of $total left</span></td>";
                    echo "<td><a href='booking.php?id=".$row['scid']."'><button class='book-btn'>Book Appointment</button></a></td>";
                }
                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p>No sessions available.</p>";
        }
        echo "</div>";

        $con->close();
    ?>
</main>    

<!-- Footer -->

<?php include('../footer.html')?>
</body>
</html>
