<?php
/**
 * Show the doctor the heart-risk assessment a patient shared with a specific
 * appointment.
 *
 * Access is deliberately narrow: the reading is only readable if the patient
 * attached it to an appointment that sits in THIS doctor's own schedule.
 * A doctor cannot browse readings belonging to patients who have not chosen
 * to share with them.
 */
include("../connection.php");
include_once("../auth.php");
include_once("../prediction.php");
date_default_timezone_set('Asia/Kathmandu');

$userfetch = requireRole($con, 'doctor');
$useremail = $userfetch["demail"];
$userid   = (int)$userfetch["did"];
$username = $userfetch["dname"];

$apid = isset($_GET['apid']) ? (int)$_GET['apid'] : 0;

// Join all the way from the reading up to the doctor, so the WHERE clause
// itself enforces "this reading was shared with an appointment of mine".
$sql = "SELECT pd.*, p.pname, p.pdob, a.adate,
               t.start_time, t.end_time
          FROM patient_data pd
          INNER JOIN appointment a ON pd.apid = a.apid
          INNER JOIN patients p    ON pd.pid = p.pid
          INNER JOIN timeslot t    ON a.tid = t.tid
          INNER JOIN schedule s    ON t.scid = s.scid
         WHERE a.apid = ? AND s.did = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ii", $apid, $userid);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$assessment = $data ? assessReading($con, $data) : null;
$con->close();

/** Turn the coded clinical values back into words for the doctor. */
function describe($field, $value)
{
    return htmlspecialchars(describeAssessmentValue($field, $value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Heart Check - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <link rel="stylesheet" href="../css/risk.css">
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Shared heart check</span>
        <h1><?= $data ? htmlspecialchars($data['pname']) . "'s heart check" : 'Heart check' ?></h1>
        <p>Shared by the patient with their appointment so you can review it before the visit.</p>
    </div>
</section>

<main class="page-body">
<?php if (!$data): ?>
    <section class="panel">
        <div class="empty-state">
            <p>No shared heart check was found for this appointment. A patient's reading is only visible here once they share it with their booking.</p>
            <a href="appointment.php" class="btn btn-light">Back to appointments</a>
        </div>
    </section>
<?php else: ?>
    <div class="page-grid">
        <div>
            <section class="panel">
                <div class="panel__head">
                    <div>
                        <h2 class="panel__title">Model assessment</h2>
                        <p class="panel__sub">Reading taken <?= date('j F Y, g:i A', strtotime($data['timestamp'])) ?></p>
                    </div>
                </div>
                <?php if ($assessment): ?>
                    <?php renderRiskCard($assessment); ?>
                <?php else: ?>
                    <div class="notice notice-bad">The prediction model could not be run.</div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <div class="panel__head">
                    <h2 class="panel__title">Submitted values</h2>
                </div>
                <ul class="detail-list">
                    <?php foreach (ASSESSMENT_FIELDS as $name => $field): ?>
                        <li><span><?= htmlspecialchars($field['label']) ?></span><strong><?= describe($name, $data[$name]) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <aside class="panel">
            <p class="panel__label">Patient and appointment</p>
            <ul class="detail-list">
                <li><span>Patient</span><strong><?= htmlspecialchars($data['pname']) ?></strong></li>
                <li><span>Date of birth</span><strong><?= date('j M Y', strtotime($data['pdob'])) ?></strong></li>
                <li><span>Appointment</span><strong><?= date('D j M Y', strtotime($data['adate'])) ?></strong></li>
                <li><span>Time</span><strong><?= date("g:i A", strtotime($data['start_time'])) ?> - <?= date("g:i A", strtotime($data['end_time'])) ?></strong></li>
            </ul>
            <div class="btn-row" style="margin-top: 16px">
                <a class="btn btn-primary btn-block" href="consultation.php?apid=<?= (int)$data['apid'] ?>">Record the visit</a>
                <a class="btn btn-light btn-block" href="appointment.php">Back to appointments</a>
            </div>
        </aside>
    </div>
<?php endif; ?>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
