<?php
session_start();
include("../connection.php");

// Check if the user is logged in
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("Location: usersLogin.php");
    exit();
}

$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];

// Check if ID is passed
if (!isset($_GET["id"])) {
    echo "<p class='error'>No record ID specified.</p>";
    exit();
}

$record_id = intval($_GET["id"]);

// Ensure the record belongs to this user before deleting
$stmt = $con->prepare("DELETE FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);

if ($stmt->execute()) {
    // Redirect with success message
    header("Location: viewHistory.php?msg=deleted");
} else {
    echo "<p class='error'>Error deleting record: " . $stmt->error . "</p>";
}

$stmt->close();
$con->close();
?>
