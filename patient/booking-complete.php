<?php
include("../connection.php");
include_once("../auth.php");

$userfetch = requireRole($con, 'patient');
$userid = (int)$userfetch["pid"];
$username = $userfetch["pname"];

date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');

$tid = isset($_POST["tid"]) ? (int)$_POST["tid"] : 0;
$adate = '';
$booking_successful = false;
$error_message = "An error occurred while booking your appointment. Please try again.";

/**
 * Book $tid for $userid, or return why not.
 *
 * The checks and the insert run in one transaction that locks the session's
 * schedule row (SELECT ... FOR UPDATE), so two requests for the same session
 * are handled one after the other: two patients cannot both take one slot,
 * and one patient cannot take two slots of the same session by submitting
 * twice. UNIQUE(appointment.tid) remains the final guard underneath.
 *
 * @return string|null Error message, or null when the booking was made.
 */
function bookSlot($con, $userid, $tid, $today, &$adate)
{
    $stmt = $con->prepare(
        "SELECT t.scid, s.sdate
           FROM timeslot t
           JOIN schedule s ON s.scid = t.scid
          WHERE t.tid = ?
            FOR UPDATE"
    );
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $slot = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$slot) {
        return "That time slot no longer exists. Please choose another one.";
    }
    if ($slot['sdate'] < $today) {
        return "That session has already taken place. Please choose an upcoming session.";
    }
    // The date comes from the session itself, never from the form.
    $adate = $slot['sdate'];
    $scid = (int)$slot['scid'];

    $stmt = $con->prepare(
        "SELECT COUNT(*) AS n
           FROM appointment a
           JOIN timeslot t ON t.tid = a.tid
          WHERE a.pid = ? AND t.scid = ?"
    );
    $stmt->bind_param("ii", $userid, $scid);
    $stmt->execute();
    $alreadyInSession = (int)$stmt->get_result()->fetch_assoc()['n'] > 0;
    $stmt->close();

    if ($alreadyInSession) {
        return "You already have an appointment booked for this schedule. Please choose a different schedule.";
    }

    $stmt = $con->prepare("INSERT INTO appointment (pid, tid, adate) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userid, $tid, $adate);
    $inserted = $stmt->execute();
    $errno = $stmt->errno;
    $stmt->close();

    if (!$inserted) {
        // 1062 = duplicate key on UNIQUE(tid): someone else holds the slot.
        return $errno == 1062
            ? "This time slot is already booked. Please choose a different time slot."
            : "An error occurred while booking your appointment. Please try again.";
    }
    return null;
}

if ($tid > 0) {
    $con->begin_transaction();
    try {
        $problem = bookSlot($con, $userid, $tid, $today, $adate);
    } catch (mysqli_sql_exception $e) {
        // Only reached when mysqli is set to throw (the default from PHP 8.1).
        $problem = $e->getCode() == 1062
            ? "This time slot is already booked. Please choose a different time slot."
            : "An error occurred while booking your appointment. Please try again.";
    }

    if ($problem === null) {
        $con->commit();
        $booking_successful = true;
    } else {
        $con->rollback();
        $error_message = $problem;
    }
} else {
    $error_message = "Please choose a time slot.";
}

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
            <p><strong>Date:</strong> <?php echo htmlspecialchars($adate); ?></p>
        <?php else: ?>
            <h2>Booking Failed</h2>
            <!-- <p>There was an error booking your appointment. Please try again later.</p> -->
            <p><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <a href="schedule.php">Back to Schedule</a>
    </div>
</body>
</html>
