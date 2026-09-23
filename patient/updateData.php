<?php
include("../connection.php");
include_once("../auth.php");
include_once("../assessment.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
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

    list($values, $errors) = validateAssessment($_POST);
    if ($errors) {
        $_SESSION['assessment_errors'] = $errors;
        $_SESSION['assessment_old'] = array_intersect_key($_POST, ASSESSMENT_FIELDS);
        header("Location: editData.php?id=$id");
        exit();
    }

    // risk_score is cleared because it belonged to the old values; the
    // reading is re-scored the next time it is viewed (prediction.php).
    $stmt = $con->prepare("UPDATE patient_data SET 
        age=?, sex=?, cp=?, trestbps=?, chol=?, fbs=?, restecg=?, thalach=?, 
        exang=?, oldpeak=?, slope=?, ca=?, thal=?, risk_score=NULL 
        WHERE pdid=? AND pid=?");

    $stmt->bind_param("iiiiiiiiidiiiii",
        $values['age'], $values['sex'], $values['cp'], $values['trestbps'],
        $values['chol'], $values['fbs'], $values['restecg'], $values['thalach'],
        $values['exang'], $values['oldpeak'], $values['slope'],
        $values['ca'], $values['thal'], $id, $userid);

    if ($stmt->execute()) {
        header("Location: viewHistory.php?msg=updated");
    } else {
        echo "<p>Error: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
}

$con->close();
?>
