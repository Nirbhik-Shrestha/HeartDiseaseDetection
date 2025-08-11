<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("location: usersLogin.php");
    exit(); // Stop further execution
}

// Ensure required data is received
// if (!isset($_POST["scid"]) || !isset($_POST["anum"]) || !isset($_POST["adate"])) {
//     header("location: usersLogin.php"); // Redirect if data is missing
//     exit(); // Stop further execution
// }

// Retrieve data from POST
$tid = $_POST["tid"];
// $anum = $_POST["anum"];
$adate = $_POST["adate"];

// Import database connection
include("../connection.php");
$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * from patients where pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

// Check if the patient already has an appointment for this schedule
$sql_check_existing_schedule = "SELECT * FROM appointment WHERE pid = $userid AND tid IN (
    SELECT tid FROM timeslot WHERE scid = (
        SELECT scid FROM timeslot WHERE tid = $tid
    )
)";
$result_existing_schedule = $con->query($sql_check_existing_schedule);

if ($result_existing_schedule->num_rows > 0) {
    $booking_successful = false;
    $error_message = "You already have an appointment booked for this schedule. Please choose a different schedule.";
} else {
    // Check if the selected time slot is already booked
    $sql_check_time_slot = "SELECT * FROM appointment WHERE tid = $tid";
    $result_time_slot = $con->query($sql_check_time_slot);

    if ($result_time_slot->num_rows > 0) {
        $booking_successful = false;
        $error_message = "This time slot is already booked. Please choose a different time slot.";
    } else {
            // Book the appointment
            $sql = "INSERT INTO appointment (pid, tid, adate) VALUES ($userid, $tid, '$adate')";
            $booking_successful = $con->query($sql) === TRUE;

        if ($booking_successful) {
            $success_message = "Appointment booked successfully.";
        } else {
            $error_message = "An error occurred while booking your appointment. Please try again.";
        }
    }
}

// if (isset($error_message)) {
//     echo $error_message;
// } elseif (isset($success_message)) {
//     echo $success_message;
// }

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 80%;
            max-width: 500px;
        }
        h2 {
            color: #00a99d;
        }
        p {
            color: #666;
            font-size: 1.1em;
        }
        a {
            text-decoration: none;
            color: #fff;
            background-color: #00a99d;
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        a:hover {
            background-color: #007f7a;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($booking_successful): ?>
            <h2>Booking Successful!</h2>
            <p>Your appointment has been booked successfully.</p>
            <p><strong>Date:</strong> <?php echo $adate; ?></p>
        <?php else: ?>
            <h2>Booking Failed</h2>
            <!-- <p>There was an error booking your appointment. Please try again later.</p> -->
            <p><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <a href="schedule.php">Back to Schedule</a>
    </div>
</body>
</html>
