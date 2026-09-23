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
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heart Assessment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/risk.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        .container { display: flex; flex: 1; }
        .main-content {
            flex-grow: 1;
            background-color: #ffffff;
            padding: 30px;
            box-sizing: border-box;
        }
        .main-content h1 { margin-top: 0; font-size: 24px; color: #333333; }
        .breadcrumb { margin-bottom: 20px; color: #777777; }
        .breadcrumb a { text-decoration: none; color: #00a99d; }

        .panel {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }
        .panel h2 { margin-top: 0; font-size: 18px; }

        .meta p { margin: 4px 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table, th, td { border: 1px solid #cccccc; }
        th, td { padding: 9px; text-align: left; }
        th { background-color: #00a99d; color: #ffffff; }

        .back { text-decoration: none; color: #00a99d; display: inline-block; margin-top: 10px; }
        .back:hover { text-decoration: underline; }
        .empty { color: #8b2c22; }
    </style>
</head>
<body>
<div class="container">
<?php include("sidebar.php"); ?>
    <div class="main-content">
        <h1>Heart Assessment</h1>
        <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt;
            <a href="appointment.php">My Appointments</a> &gt;
            <span>Assessment</span>
        </div>

        <?php if (!$data): ?>
            <div class="panel">
                <p class="empty">No shared assessment was found for this appointment.</p>
                <p>A patient's reading is only visible here once they choose to share it with their booking.</p>
                <a class="back" href="appointment.php">Back to appointments</a>
            </div>
        <?php else: ?>
            <div class="panel meta">
                <h2>Patient</h2>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($data['pname']); ?></p>
                <p><strong>Date of birth:</strong> <?php echo htmlspecialchars($data['pdob']); ?></p>
                <p><strong>Appointment:</strong>
                    <?php
                        echo htmlspecialchars($data['adate']) . " at "
                           . date("h:i A", strtotime($data['start_time'])) . " - "
                           . date("h:i A", strtotime($data['end_time']));
                    ?>
                </p>
                <p><strong>Reading submitted:</strong> <?php echo htmlspecialchars($data['timestamp']); ?></p>
            </div>

            <div class="panel">
                <h2>Model assessment</h2>
                <?php if ($assessment): ?>
                    <?php renderRiskCard($assessment); ?>
                <?php else: ?>
                    <p class='empty'>The prediction model could not be run.</p>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2>Submitted values</h2>
                <table>
                    <thead>
                        <tr><th>Measure</th><th>Value</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Age</td><td><?php echo (int)$data['age']; ?></td></tr>
                        <tr><td>Sex</td><td><?php echo describe('sex', (int)$data['sex']); ?></td></tr>
                        <tr><td>Chest pain type</td><td><?php echo describe('cp', (int)$data['cp']); ?></td></tr>
                        <tr><td>Resting blood pressure</td><td><?php echo (int)$data['trestbps']; ?> mm Hg</td></tr>
                        <tr><td>Serum cholesterol</td><td><?php echo (int)$data['chol']; ?> mg/dl</td></tr>
                        <tr><td>Fasting blood sugar</td><td><?php echo describe('fbs', (int)$data['fbs']); ?></td></tr>
                        <tr><td>Resting ECG</td><td><?php echo describe('restecg', (int)$data['restecg']); ?></td></tr>
                        <tr><td>Max heart rate achieved</td><td><?php echo (int)$data['thalach']; ?> bpm</td></tr>
                        <tr><td>Exercise induced angina</td><td><?php echo describe('exang', (int)$data['exang']); ?></td></tr>
                        <tr><td>ST depression (oldpeak)</td><td><?php echo htmlspecialchars($data['oldpeak']); ?></td></tr>
                        <tr><td>ST segment slope</td><td><?php echo describe('slope', (int)$data['slope']); ?></td></tr>
                        <tr><td>Major vessels coloured</td><td><?php echo (int)$data['ca']; ?></td></tr>
                        <tr><td>Thallium stress scan (thal)</td><td><?php echo describe('thal', (int)$data['thal']); ?></td></tr>
                    </tbody>
                </table>
                <a class="back" href="appointment.php">Back to appointments</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
