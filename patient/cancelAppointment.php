<?php
/**
 * Cancel one of the logged-in patient's own appointments.
 *
 * POST only, so a cancellation cannot be triggered by following a link.
 * The appointment is matched on both apid AND the session's pid, so a patient
 * can never cancel someone else's booking by changing the id.
 *
 * Freeing the timeslot is the whole point: the DELETE removes the row that
 * marks the slot as taken, so booking.php offers it again immediately.
 */
include("../connection.php");
include_once("../auth.php");

$patient = requireRole($con, 'patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: appointment.php");
    exit();
}

date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');

$userid = (int)$patient['pid'];
$apid   = isset($_POST['apid']) ? (int)$_POST['apid'] : 0;

// Confirm the appointment belongs to this patient and has not already passed.
$stmt = $con->prepare("SELECT apid, adate, status FROM appointment WHERE apid = ? AND pid = ?");
$stmt->bind_param("ii", $apid, $userid);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $con->close();
    header("location: appointment.php?msg=notfound");
    exit();
}

if ($appointment['adate'] < $today) {
    // A visit that already happened is history, not something to cancel.
    $con->close();
    header("location: appointment.php?msg=past");
    exit();
}

if ($appointment['status'] !== 'booked') {
    // Deleting it would also delete the doctor's record and notes.
    $con->close();
    header("location: appointment.php?msg=recorded");
    exit();
}

$stmt = $con->prepare("DELETE FROM appointment WHERE apid = ? AND pid = ? AND status = 'booked'");
$stmt->bind_param("ii", $apid, $userid);
$ok = $stmt->execute() && $stmt->affected_rows > 0;
$stmt->close();
$con->close();

// Any prediction shared with this appointment survives: patient_data.apid is
// ON DELETE SET NULL, so the reading is kept and simply unlinked.
header("location: appointment.php?msg=" . ($ok ? "cancelled" : "error"));
exit();
