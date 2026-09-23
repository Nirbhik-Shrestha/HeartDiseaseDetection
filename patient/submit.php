<?php
/**
 * Save a new heart assessment, score it, and show the result.
 *
 * The result itself is rendered by viewResult.php, so a fresh result and one
 * opened later from the history look identical. Invalid input goes back to
 * form.php with the answers kept and the problems listed.
 */
include("../connection.php");
include_once("../auth.php");
include_once("../prediction.php");

$userfetch = requireRole($con, 'patient');
$userid = (int)$userfetch["pid"];

// Old links to submit.php?result_id=N still land on the result page.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = isset($_GET['result_id']) ? (int)$_GET['result_id'] : 0;
    header($id > 0 ? "Location: viewResult.php?id=$id" : "Location: form.php");
    exit();
}

list($values, $errors) = validateAssessment($_POST);

if ($errors) {
    $_SESSION['assessment_errors'] = $errors;
    $_SESSION['assessment_old'] = array_intersect_key($_POST, ASSESSMENT_FIELDS);
    header("Location: form.php");
    exit();
}

$stmt = $con->prepare("INSERT INTO patient_data (pid, age, sex, cp, trestbps, chol, fbs, restecg, thalach, exang, oldpeak, slope, ca, thal)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiiiiiiiiidiii",
    $userid, $values['age'], $values['sex'], $values['cp'], $values['trestbps'],
    $values['chol'], $values['fbs'], $values['restecg'], $values['thalach'],
    $values['exang'], $values['oldpeak'], $values['slope'], $values['ca'], $values['thal']
);

if (!$stmt->execute()) {
    $stmt->close();
    $_SESSION['assessment_errors'] = ["Your assessment could not be saved. Please try again."];
    $_SESSION['assessment_old'] = $values;
    header("Location: form.php");
    exit();
}
$pdid = $con->insert_id;
$stmt->close();

// Score it now, so the trend chart has it straight away. If the model is
// unavailable the score stays NULL and is filled in on a later view.
$rows = [$values + ['pdid' => $pdid, 'risk_score' => null]];
ensureRiskScores($con, $rows);
$con->close();

header("Location: viewResult.php?id=$pdid");
exit();
