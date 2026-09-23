<?php
include("../connection.php");
include_once("../auth.php");
include_once("../assessment.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
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

// Set by updateData.php when the last attempt failed validation.
$errors = isset($_SESSION['assessment_errors']) ? $_SESSION['assessment_errors'] : [];
if (isset($_SESSION['assessment_old'])) {
    $data = array_merge($data, $_SESSION['assessment_old']);
}
unset($_SESSION['assessment_errors'], $_SESSION['assessment_old']);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Record</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        .field-group { border: 1px solid #e3e8ef; border-radius: 10px; padding: 6px 16px 16px; margin-top: 18px; }
        .field-group legend { font-weight: bold; padding: 0 6px; }
        .group-intro, .hint { font-size: 13px; color: #666; line-height: 1.45; margin-top: 6px; }
        .field label { display: block; margin-top: 14px; font-weight: bold; }
        .field select, .field input { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        .unit { font-weight: normal; color: #777; }
    </style>
</head>
<body>
<div class="container">
    <h2>Edit Prediction Record</h2>
    <?php if ($errors): ?>
        <div class="error" role="alert">
            <strong>Please check your answers:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="updateData.php">
        <input type="hidden" name="id" value="<?= $record_id ?>" />
        
        <?php renderAssessmentFields($data); ?>
        <button type="submit">Update</button>
    </form>
    <a href="viewHistory.php" class="button">Back</a>
</div>
</body>
</html>
