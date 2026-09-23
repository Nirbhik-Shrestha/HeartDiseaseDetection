<?php
include("../connection.php");
include_once("../auth.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
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
