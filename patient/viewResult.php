<?php
include("../connection.php");
include_once("../auth.php");
include_once("../prediction.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
$userid = $userfetch ? $userfetch["pid"] : 0;
$username = isset($userfetch["pname"]) ? $userfetch["pname"] : "Patient";

// Validate ID
if (!isset($_GET["id"])) {
    // Nothing to show; the history page lists the readings that do exist.
    header("Location: viewHistory.php");
    exit;
}

$record_id = intval($_GET["id"]);

// Fetch prediction data
$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: viewHistory.php");
    exit;
}

$data = $result->fetch_assoc();

$assessment = assessReading($con, $data);

// Fetch Recommended Cardiologists safely
date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');
$cardio_res = false;

try {
    $cardio_sql = "SELECT d.*, s.sname, 
                   (SELECT COUNT(*) FROM schedule WHERE schedule.did = d.did AND schedule.sdate >= '$today') as upcoming_schedules
                   FROM doctors d 
                   JOIN specialties s ON d.spid = s.spid 
                   WHERE s.sname LIKE '%Cardiologist%' 
                   ORDER BY d.dname ASC";
    $cardio_res = $con->query($cardio_sql);
} catch (Exception $e) {
    $cardio_res = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heart Check Result - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <link rel="stylesheet" href="../css/risk.css">
    <style>
        .answers-list li {
            font-size: 15px;
            padding: 9px 0;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Heart check result</span>
        <h1>Your heart check result</h1>
        <p>Taken on <?= date('j F Y, g:i A', strtotime($data['timestamp'])) ?>.</p>
    </div>
</section>

<main class="page-body">
    <div class="page-grid">
        <div>
            <section class="panel">
                <?php if ($assessment): ?>
                    <?php renderRiskCard($assessment); ?>
                <?php else: ?>
                    <div class="notice notice-bad">The prediction model could not be run. Please try again later.</div>
                <?php endif; ?>

                <div class="notice notice-info">
                    <strong>Medical disclaimer:</strong> this result comes from a statistical model and is not a diagnosis.
                    It is not a substitute for professional medical advice. If you have concerns about your heart health,
                    please see a qualified cardiologist.
                </div>

                <div class="btn-row">
                    <a href="downloadAssessment.php?id=<?= (int)$data['pdid'] ?>" class="btn btn-primary">Download PDF</a>
                    <a href="form.php" class="btn btn-light">Take another check</a>
                    <a href="viewHistory.php" class="btn btn-light">View history</a>
                </div>
            </section>

            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2 class="panel__title">Recommended cardiologists</h2>
                        <p class="panel__sub">Book a consultation to go through this result with a specialist.</p>
                    </div>
                    <a href="doctors.php?search=Cardiologist" class="btn btn-light btn-sm">View all doctors</a>
                </div>

                <?php if ($cardio_res && $cardio_res->num_rows > 0): ?>
                    <div class="doctor-grid">
                        <?php while ($doc = $cardio_res->fetch_assoc()): ?>
                            <?php $upcoming = (int)$doc['upcoming_schedules']; ?>
                            <article class="doctor-card">
                                <img src="../images/user.png" alt="">
                                <h3>Dr. <?= htmlspecialchars($doc['dname']) ?></h3>
                                <span class="badge badge-info"><?= htmlspecialchars($doc['sname']) ?></span>
                                <?php if (!empty($doc['daddress'])): ?>
                                    <p><?= htmlspecialchars($doc['daddress']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($doc['dcontact'])): ?>
                                    <p><?= htmlspecialchars($doc['dcontact']) ?></p>
                                <?php endif; ?>
                                <div class="doctor-card__actions">
                                    <?php if ($upcoming > 0): ?>
                                        <a class="btn btn-primary" href="schedule.php?did=<?= (int)$doc['did'] ?>">Book appointment</a>
                                    <?php else: ?>
                                        <span class="btn is-disabled">No sessions scheduled</span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No cardiologists are registered right now.</p>
                        <a href="doctors.php" class="btn btn-light">Browse all doctors</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <aside class="panel">
            <p class="panel__label">Your answers</p>
            <ul class="detail-list answers-list">
                <?php foreach (ASSESSMENT_FIELDS as $name => $field): ?>
                    <li>
                        <span><?= htmlspecialchars($field['label']) ?></span>
                        <strong><?= htmlspecialchars(describeAssessmentValue($name, $data[$name])) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="btn-row" style="margin-top: 16px">
                <a href="editData.php?id=<?= (int)$data['pdid'] ?>" class="btn btn-light btn-sm">Edit answers</a>
            </div>
        </aside>
    </div>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
