<?php
/**
 * Attach (or detach) one of the patient's heart-disease readings to one of
 * their upcoming appointments, so the doctor they are about to see can review
 * the risk assessment beforehand.
 *
 * Sharing is always the patient's choice: nothing is visible to a doctor until
 * the patient links it here, and "Stop sharing" reverses it at any time.
 *
 * Both the reading and the appointment are matched against the session's pid,
 * so a patient can only ever link their own records.
 */
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("location: usersLogin.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: appointment.php");
    exit();
}

include("../connection.php");
date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');

$useremail = $_SESSION["user"];
$stmt = $con->prepare("SELECT pid FROM patients WHERE pemail = ?");
$stmt->bind_param("s", $useremail);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    header("location: usersLogin.php");
    exit();
}

$userid = (int)$patient['pid'];
$apid   = isset($_POST['apid']) ? (int)$_POST['apid'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// The appointment must be this patient's, and still ahead of them.
$stmt = $con->prepare("SELECT apid FROM appointment WHERE apid = ? AND pid = ? AND adate >= ?");
$stmt->bind_param("iis", $apid, $userid, $today);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    $con->close();
    header("location: appointment.php?msg=notfound");
    exit();
}

if ($action === 'unshare') {
    // Detach whatever reading is linked; the reading itself is kept.
    $stmt = $con->prepare("UPDATE patient_data SET apid = NULL WHERE apid = ? AND pid = ?");
    $stmt->bind_param("ii", $apid, $userid);
    $ok = $stmt->execute();
    $stmt->close();
    $con->close();

    header("location: appointment.php?msg=" . ($ok ? "unshared" : "error"));
    exit();
}

$pdid = isset($_POST['pdid']) ? (int)$_POST['pdid'] : 0;

// The reading must belong to this patient too.
$stmt = $con->prepare("SELECT pdid FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $pdid, $userid);
$stmt->execute();
$reading = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reading) {
    $con->close();
    header("location: appointment.php?msg=notfound");
    exit();
}

// One reading per appointment: clear any previous link before setting this one.
$stmt = $con->prepare("UPDATE patient_data SET apid = NULL WHERE apid = ? AND pid = ?");
$stmt->bind_param("ii", $apid, $userid);
$stmt->execute();
$stmt->close();

$stmt = $con->prepare("UPDATE patient_data SET apid = ? WHERE pdid = ? AND pid = ?");
$stmt->bind_param("iii", $apid, $pdid, $userid);
$ok = $stmt->execute();
$stmt->close();
$con->close();

header("location: appointment.php?msg=" . ($ok ? "shared" : "error"));
exit();
