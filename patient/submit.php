<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../connection.php");
session_start();

if (!isset($_SESSION["user"])) {
    $_SESSION["user"] = "";
}

if (isset($_SESSION["user"])) {
    if (($_SESSION["user"]) == "") {
        header("location: usersLogin.php");
        exit();
    } else {
        $useremail = $_SESSION["user"];
    }
} else {
    header("location: usersLogin.php");
    exit();
}

$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Prediction Result</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $stmt = $con->prepare("INSERT INTO patient_data (pid, age, sex, cp, trestbps, chol, fbs, restecg, thalach, exang, oldpeak, slope, ca, thal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiiiiiiidiii",
        $userid, $_POST['age'], $_POST['sex'], $_POST['cp'], $_POST['trestbps'],
        $_POST['chol'], $_POST['fbs'], $_POST['restecg'], $_POST['thalach'],
        $_POST['exang'], $_POST['oldpeak'], $_POST['slope'], $_POST['ca'], $_POST['thal']
    );

    if ($stmt->execute()) {
        $insert_id = $con->insert_id;
        $stmt->close();
        $con->close();
        
        header("Location: submit.php?result_id=$insert_id");
        exit();
    } else {
        echo "<p class='error'>Error: " . $stmt->error . "</p>";
        $stmt->close();
        $con->close();
    }

} elseif (isset($_GET['result_id'])) {
    // 3. Handle the GET after redirect
    $id = (int)$_GET['result_id'];
    $result = $con->query("SELECT * FROM patient_data WHERE pdid = $id");

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $data = [
            "age" => (int)$row['age'],
            "sex" => (int)$row['sex'],
            "cp" => (int)$row['cp'],
            "trestbps" => (int)$row['trestbps'],
            "chol" => (int)$row['chol'],
            "fbs" => (int)$row['fbs'],
            "restecg" => (int)$row['restecg'],
            "thalach" => (int)$row['thalach'],
            "exang" => (int)$row['exang'],
            "oldpeak" => (float)$row['oldpeak'],
            "slope" => (int)$row['slope'],
            "ca" => (int)$row['ca'],
            "thal" => (int)$row['thal']
        ];

        $json = json_encode($data);
        $tmpfile = tempnam(sys_get_temp_dir(), 'json_');
        file_put_contents($tmpfile, $json);

        $python = 'C:\\Users\\nirbh\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
        $script = 'C:\\xampp\\htdocs\\myCodes\\DoctorProjectTest\\patient\\predict.py';
        $cmd = "\"$python\" \"$script\" \"$tmpfile\" 2>&1";

        $output = shell_exec($cmd);
        echo "<h1>Prediction Result</h1>";
        echo "<pre>$output</pre>";

        unlink($tmpfile);
    } else {
        echo "<p class='error'>Record not found.</p>";
    }

    $con->close();
} else {
    echo "<p class='error'>Invalid access.</p>";
}
?>
    <a href="index.php" class="button">Home</a>
</div>
</body>
</html>
