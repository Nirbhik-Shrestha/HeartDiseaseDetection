<?php
include("../connection.php");
include_once("../auth.php");
include_once("../assessment.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

$record_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

// Fetch record for editing
$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    // Missing or someone else's reading: back to the list of the patient's own.
    header("Location: viewHistory.php");
    exit();
}

$data = $result->fetch_assoc();
$takenAt = $data['timestamp'];

// Set by updateData.php when the last attempt failed validation.
$errors = isset($_SESSION['assessment_errors']) ? $_SESSION['assessment_errors'] : [];
if (isset($_SESSION['assessment_old'])) {
    $data = array_merge($data, $_SESSION['assessment_old']);
}
unset($_SESSION['assessment_errors'], $_SESSION['assessment_old']);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Heart Check - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .edit-actions {
            position: sticky;
            bottom: 16px;
            z-index: 5;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 14px 14px 14px 22px;
            border-radius: 16px;
            background: #12304a;
            color: #d6e0e8;
            box-shadow: 0 18px 40px -16px rgba(18, 48, 74, 0.55);
        }

        .edit-actions p {
            margin: 0;
            font-size: 15px;
        }

        .edit-actions .btn-row {
            margin-left: auto;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Edit heart check</span>
        <h1>Edit your answers</h1>
        <p>Correct any value from your reading of <?= date('j F Y', strtotime($takenAt)) ?>. The risk score is recalculated when you save.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($errors): ?>
        <div class="notice notice-bad" role="alert">
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

        <div class="edit-actions">
            <p>Saving replaces this reading's answers.</p>
            <div class="btn-row">
                <a href="viewHistory.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </form>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
