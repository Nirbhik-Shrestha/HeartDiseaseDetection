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
session_start();

if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("location: doctorLogin.php");
    exit();
}
$useremail = $_SESSION["user"];

include("../connection.php");
date_default_timezone_set('Asia/Kathmandu');

$stmt = $con->prepare("SELECT did, dname FROM doctors WHERE demail = ?");
$stmt->bind_param("s", $useremail);
$stmt->execute();
$userfetch = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userfetch) {
    header("location: doctorLogin.php");
    exit();
}
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
$con->close();

$prediction = null;
if ($data) {
    // Same contract as patient/viewResult.php: hand predict.py a JSON file of
    // the 13 clinical features and let it render the risk card.
    $input_data = [
        "age"      => (int)$data['age'],
        "sex"      => (int)$data['sex'],
        "cp"       => (int)$data['cp'],
        "trestbps" => (int)$data['trestbps'],
        "chol"     => (int)$data['chol'],
        "fbs"      => (int)$data['fbs'],
        "restecg"  => (int)$data['restecg'],
        "thalach"  => (int)$data['thalach'],
        "exang"    => (int)$data['exang'],
        "oldpeak"  => (float)$data['oldpeak'],
        "slope"    => (int)$data['slope'],
        "ca"       => (int)$data['ca'],
        "thal"     => (int)$data['thal'],
    ];

    $tmpfile = tempnam(sys_get_temp_dir(), 'json_');
    file_put_contents($tmpfile, json_encode($input_data));

    $python = 'C:\\Users\\nirbh\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
    $script = dirname(__DIR__) . '\\patient\\predict.py';
    $prediction = shell_exec("\"$python\" \"$script\" \"$tmpfile\" 2>&1");
    unlink($tmpfile);
}

/** Turn the coded clinical values back into words for the doctor. */
function describe($field, $value)
{
    $maps = [
        'sex'     => [0 => 'Female', 1 => 'Male'],
        'cp'      => [0 => 'Typical angina', 1 => 'Atypical angina', 2 => 'Non-anginal pain', 3 => 'Asymptomatic'],
        'fbs'     => [0 => 'No (&le; 120 mg/dl)', 1 => 'Yes (&gt; 120 mg/dl)'],
        'restecg' => [0 => 'Normal', 1 => 'ST-T wave abnormality', 2 => 'Left ventricular hypertrophy'],
        'exang'   => [0 => 'No', 1 => 'Yes'],
        'slope'   => [0 => 'Upsloping', 1 => 'Flat', 2 => 'Downsloping'],
        'thal'    => [1 => 'Normal', 2 => 'Fixed defect', 3 => 'Reversible defect'],
    ];

    if (isset($maps[$field][$value])) {
        return $maps[$field][$value] . " ($value)";
    }
    return $value;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heart Assessment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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

        /* Risk card styles, mirroring the patient-facing result page. */
        .risk-card { border-radius: 8px; padding: 20px; }
        .risk-card.low-risk  { background-color: #e6f6f1; border: 1px solid #a6ddc9; }
        .risk-card.high-risk { background-color: #fdecea; border: 1px solid #f5c2bd; }
        .risk-badge-header { display: flex; gap: 12px; align-items: flex-start; }
        .risk-card h2 { margin: 0 0 4px; font-size: 20px; }
        .risk-subtitle { margin: 0; color: #555; }
        .reasons-block { margin-top: 15px; }
        .reasons-block h3 { font-size: 15px; margin-bottom: 6px; }
        .reasons-list { margin: 0; padding-left: 20px; }
        .risk-icon { font-size: 24px; }

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
                <?php
                    // predict.py emits the risk card markup itself.
                    echo $prediction !== null && trim($prediction) !== ''
                        ? $prediction
                        : "<p class='empty'>The prediction model could not be run.</p>";
                ?>
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
                        <tr><td>Thalassemia</td><td><?php echo describe('thal', (int)$data['thal']); ?></td></tr>
                    </tbody>
                </table>
                <a class="back" href="appointment.php">Back to appointments</a>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
