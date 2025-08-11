<?php
session_start();
include("../connection.php");

// Check login
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("Location: usersLogin.php");
    exit();
}

$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];

// Get record ID
if (!isset($_GET["id"])) {
    echo "<p>No record ID provided.</p>";
    exit();
}

$record_id = intval($_GET["id"]);

// Fetch record for editing
$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    echo "<p>Record not found or does not belong to you.</p>";
    exit();
}

$data = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Record</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
<div class="container">
    <h2>Edit Prediction Record</h2>
    <form method="post" action="updateData.php">
        <input type="hidden" name="id" value="<?= $record_id ?>" />
        
        <?php
        $fields = [
            "age", "sex", "cp", "trestbps", "chol", "fbs", "restecg",
            "thalach", "exang", "oldpeak", "slope", "ca", "thal"
        ];
        foreach ($fields as $field): ?>
            <label><?= ucfirst($field) ?>:</label>
            <input 
                type="<?= ($field === 'oldpeak') ? 'text' : 'number' ?>" 
                name="<?= $field ?>" 
                value="<?= htmlspecialchars($data[$field]) ?>" 
                required />
        <?php endforeach; ?>

        <button type="submit">Update</button>
    </form>
    <a href="viewHistory.php" class="button">Back</a>
</div>
</body>
</html>
