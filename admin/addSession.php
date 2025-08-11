<?php
    session_start();

    // Redirect if user is not logged in
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        header("Location: adminLogin.php");
        exit(); // Stop further execution
    }

    include("../connection.php");

    // Check if form is submitted
    if (isset($_POST['submit'])) {
        // Sanitize input
        $title = $_POST["title"];
        $did = $_POST["did"];
        $date = $_POST["sdate"];
        $time = $_POST["stime"];
        $nop = $_POST["nop"];

        // Insert schedule into database
        $schedule_sql = "INSERT INTO Schedule (did, sdate) VALUES ($did, '$date')";
        $con->query($schedule_sql);
        $scid = $con->insert_id;

        // Calculate end time (assuming each appointment is one hour long)
        $start_time = strtotime($time);
        for ($i = 0; $i < $nop; $i++) {
            $timeslot_start = date('H:i:s', $start_time + ($i * 3600));
            $timeslot_end = date('H:i:s', $start_time + (($i + 1) * 3600));
            $timeslot_sql = "INSERT INTO Timeslot (scid, start_time, end_time) VALUES ($scid, '$timeslot_start', '$timeslot_end')";
            $con->query($timeslot_sql);
        }

        // Redirect with success message
        header("Location: schedule.php?action=session-added&title=$title");
        exit(); // Stop further execution
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Session</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 60%;
            margin: 50px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #00a99d;
            text-align: center;
            margin-bottom: 20px;
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
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #00a99d;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin: 5px 0;
        }
        .btn-primary {
            background-color: #00a99d;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Session</h1>
        <form action="" method="POST">
            <div class="form-group">
                <label for="title">Session Title:</label>
                <input type="text" id="title" name="title" placeholder="Enter Session Title" required>
            </div>
            <div class="form-group">
                <label for="did">Doctors:</label>
                <select id="did" name="did" required>
                    <option value="" disabled selected hidden>Choose Doctor Name from the list</option>
                    <?php
                        $listDoctors = $con->query("SELECT * FROM Doctors ORDER BY dname ASC");
                        while($row = $listDoctors->fetch_assoc()){
                            echo "<option value=".$row["did"].">".$row["dname"]."</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nop">No of Patients:</label>
                <input type="number" id="nop" name="nop" placeholder="Enter No of Patients" required>
            </div>
            <div class="form-group">
                <label for="sdate">Session Date:</label>
                <input type="date" id="sdate" name="sdate" required>
            </div>
            <div class="form-group">
                <label for="stime">Session Time:</label>
                <input type="time" id="stime" name="stime" required>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Place this Session</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </form>
    </div>
</body>
</html>
