<?php
session_start();
include("../connection.php");

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("Location: usersLogin.php");
    exit();
}

$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = intval($_POST["id"]);

    // Ensure the record belongs to the user
    $check = $con->prepare("SELECT pdid FROM patient_data WHERE pdid = ? AND pid = ?");
    $check->bind_param("ii", $id, $userid);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows !== 1) {
        echo "Invalid access.";
        exit();
    }

    $check->close();

    // Update logic
    $stmt = $con->prepare("UPDATE patient_data SET 
        age=?, sex=?, cp=?, trestbps=?, chol=?, fbs=?, restecg=?, thalach=?, 
        exang=?, oldpeak=?, slope=?, ca=?, thal=? 
        WHERE pdid=? AND pid=?");

    $stmt->bind_param("iiiiiiiidiiiiii",
        $_POST['age'], $_POST['sex'], $_POST['cp'], $_POST['trestbps'],
        $_POST['chol'], $_POST['fbs'], $_POST['restecg'], $_POST['thalach'],
        $_POST['exang'], $_POST['oldpeak'], $_POST['slope'],
        $_POST['ca'], $_POST['thal'], $id, $userid);

    if ($stmt->execute()) {
        header("Location: viewHistory.php?msg=updated");
    } else {
        echo "<p>Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

$con->close();
?>
