<?php
session_start();
include("../connection.php");

// Check login
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("location: usersLogin.php");
    exit;
}

$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];

// Validate ID
if (!isset($_GET["id"])) {
    echo "<p class='error'>Invalid request. No prediction ID given.</p>";
    exit;
}

$record_id = intval($_GET["id"]);

// Fetch prediction data
$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='error'>No record found or you do not have permission to view it.</p>";
    exit;
}

$data = $result->fetch_assoc();

// Prepare JSON input for Python
$input_data = [
    "age" => (int)$data['age'],
    "sex" => (int)$data['sex'],
    "cp" => (int)$data['cp'],
    "trestbps" => (int)$data['trestbps'],
    "chol" => (int)$data['chol'],
    "fbs" => (int)$data['fbs'],
    "restecg" => (int)$data['restecg'],
    "thalach" => (int)$data['thalach'],
    "exang" => (int)$data['exang'],
    "oldpeak" => (float)$data['oldpeak'],
    "slope" => (int)$data['slope'],
    "ca" => (int)$data['ca'],
    "thal" => (int)$data['thal']
];

$json = json_encode($input_data);
$tmpfile = tempnam(sys_get_temp_dir(), 'json_');
file_put_contents($tmpfile, $json);

// Run Python script
$python = 'C:\\Users\\nirbh\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
$script = 'C:\\xampp\\htdocs\\myCodes\\DoctorProjectTest\\patient\\predict.py';
$cmd = "\"$python\" \"$script\" \"$tmpfile\" 2>&1";
$output = shell_exec($cmd);
unlink($tmpfile);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prediction Result</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>Prediction Result</h1>
    <pre><?= $output ?></pre>
    <a href="viewHistory.php" class="button">Back to History</a>
</div>
</body>
</html>
