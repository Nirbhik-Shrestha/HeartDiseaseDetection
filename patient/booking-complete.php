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

// Details for the confirmation card.
$booked = null;
if ($booking_successful) {
    $stmt = $con->prepare(
        "SELECT t.start_time, t.end_time, s.sdate, d.dname, sp.sname
           FROM timeslot t
           JOIN schedule s     ON s.scid = t.scid
           JOIN doctors d      ON d.did = s.did
           JOIN specialties sp ON sp.spid = d.spid
          WHERE t.tid = ?"
    );
    $stmt->bind_param("i", $tid);
    $stmt->execute();
    $booked = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $booking_successful ? 'Appointment Booked' : 'Booking Not Completed' ?> - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .result-panel {
            max-width: 640px;
            margin: 0 auto 24px;
        }

        .result-icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin: 0 0 16px;
            border-radius: 50%;
            font-size: 26px;
            font-weight: 700;
        }

        .result-icon.ok  { background: #e7f7ee; color: #1f7a4a; box-shadow: 0 0 0 6px #f3fbf6; }
        .result-icon.bad { background: #fdecec; color: #b42318; box-shadow: 0 0 0 6px #fff6f6; }

        .result-panel h2 {
            margin: 0 0 6px;
            color: #12304a;
            font-size: 24px;
        }

        .result-panel > p {
            margin: 0 0 20px;
            color: #3d566b;
            font-size: 16.5px;
            line-height: 1.55;
        }

        .result-panel .detail-list {
            margin: 0 0 24px;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Booking</span>
        <h1><?= $booking_successful ? 'You are booked in' : 'Booking not completed' ?></h1>
        <p><?= $booking_successful ? 'Your appointment is confirmed. You can find it any time under My Appointments.' : 'Nothing was booked. You can pick another slot or session.' ?></p>
    </div>
</section>

<main class="page-body">
    <section class="panel result-panel">
        <?php if ($booking_successful): ?>
            <div class="result-icon ok" aria-hidden="true">&#10003;</div>
            <h2>Appointment booked</h2>
            <p>Want the doctor to see your heart check before the visit? You can share it from My Appointments.</p>
            <?php if ($booked): ?>
                <ul class="detail-list">
                    <li><span>Doctor</span><strong>Dr. <?= htmlspecialchars($booked['dname']) ?></strong></li>
                    <li><span>Specialty</span><strong><?= htmlspecialchars($booked['sname']) ?></strong></li>
                    <li><span>Date</span><strong><?= date('l j F Y', strtotime($booked['sdate'])) ?></strong></li>
                    <li><span>Time</span><strong><?= date('g:i A', strtotime($booked['start_time'])) ?> - <?= date('g:i A', strtotime($booked['end_time'])) ?></strong></li>
                </ul>
            <?php endif; ?>
            <div class="btn-row">
                <a href="appointment.php" class="btn btn-primary">View my appointments</a>
                <a href="schedule.php" class="btn btn-light">Back to sessions</a>
            </div>
        <?php else: ?>
            <div class="result-icon bad" aria-hidden="true">!</div>
            <h2>We couldn't book that slot</h2>
            <p><?= htmlspecialchars($error_message) ?></p>
            <div class="btn-row">
                <a href="schedule.php" class="btn btn-primary">Back to sessions</a>
                <a href="appointment.php" class="btn btn-light">My appointments</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
