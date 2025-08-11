<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        h1 {
            font-size: 2em;
            margin-bottom: 20px;
            text-align: center;
            color: #00a99d;
        }
        .dashboard-items {
            background-color: #f1f1f1;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .dashboard-items h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .dashboard-items p {
            font-size: 1em;
            line-height: 1.6;
        }
        .dashboard-items b {
            color: #00a99d;
        }
        form {
            text-align: center;
        }
        select {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            margin-bottom: 20px;
        }
        input[type="submit"] {
            background-color: #00a99d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px;
        }
        input[type="submit"]:hover {
            background-color: #007f7a;
        }
        .table-container {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        session_start();

        if (!isset($_SESSION["user"])) {
            $_SESSION["user"] = "";  // Only set to empty if it's not already set
        }

        if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
            header("location: usersLogin.php");
            exit(); // Stop further execution
        }

        date_default_timezone_set('Asia/Kathmandu');
        $today = date('Y-m-d');

        // Import database
        include("../connection.php");
        $useremail = $_SESSION["user"];
        $userrow = $con->query("SELECT * from patients where pemail='$useremail'");
        $userfetch = $userrow->fetch_assoc();
        $userid = $userfetch["pid"];
        $username = $userfetch["pname"];

        if ($_GET && isset($_GET["id"])) {
            $scid = $_GET["id"];

            $sqlmain = "SELECT * FROM schedule 
                INNER JOIN doctors ON schedule.did = doctors.did
                INNER JOIN specialties ON doctors.spid = specialties.spid
                WHERE schedule.scid = $scid 
                ORDER BY schedule.sdate DESC";

            $result = $con->query($sqlmain);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $scid = $row["scid"];
                // $title = $row["title"];
                $dname = $row["dname"];
                $demail = $row["demail"];
                $sname = $row["sname"];
                $sdate = $row["sdate"];
                // $stime = $row["stime"];
                // Convert 24-hour time format to 12-hour format with AM/PM
                // $stime_12hr = date("h:i A", strtotime($stime));

                // Fetch available timeslots
                $timeslots_sql = "SELECT * FROM timeslot WHERE scid = $scid";
                $timeslots_result = $con->query($timeslots_sql);

                echo '<h1>Booking Details</h1>';

                echo '<form action="booking-complete.php" method="POST">
                    <input type="hidden" name="scid" value="' . $scid . '" >
                    <input type="hidden" name="adate" value="' . $sdate . '" >
                    <div class="table-container">
                        <div class="dashboard-items">
                            <h2>Session Details</h2>
                            <p>
                                <strong>Doctor Name:</strong> Dr. ' . $dname . '<br>
                                <strong>Doctor Email:</strong> ' . $demail . '<br>
                                <strong>Doctor Speciality:</strong> ' . $sname . '<br>
                                <strong>Session Scheduled Date:</strong> ' . $sdate . '<br>
                                <strong>Channeling fee:</strong> <b>Nrs 2000.00</b>
                            </p>
                        </div>
                    </div>';

                if ($timeslots_result->num_rows > 0) {
                    echo '<label for="timeslot">Select a Timeslot:</label>';
                    echo '<select name="tid" id="tid" required>';
                    echo '<option value="" disabled selected hidden>Choose a Timeslot</option>';
                    while ($timeslot_row = $timeslots_result->fetch_assoc()) {
                        $tid = $timeslot_row["tid"];
                        $start_time = date("h:i A", strtotime($timeslot_row["start_time"]));
                        $end_time = date("h:i A", strtotime($timeslot_row["end_time"]));
                        echo '<option value="' . $tid . '">' . $start_time . ' - ' . $end_time . '</option>';
                    }
                    echo '</select>';
                } else {
                    echo '<p>No available timeslots for this session.</p>';
                }

                echo '<input type="submit" value="Book Now">
                </form>';
            } else {
                echo "<p>No session found with the provided ID.</p>";
            }
        } else {
            echo "<p>No session ID provided.</p>";
        }
        $con->close();
        ?>
    </div>
</body>
</html>
