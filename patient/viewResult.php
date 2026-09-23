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
    echo "<p class='error'>Invalid request. No prediction ID given.</p>";
    exit;
}

$record_id = intval($_GET["id"]);

// Fetch prediction data
$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='error'>No record found or you do not have permission to view it.</p>";
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
    <title>Prediction Result - Daaktar Sahaab</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/index.css" />
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../css/risk.css">
    <style>
        .result-container {
            max-width: 850px;
            margin: 30px auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }
        .result-header {
            text-align: center;
            margin-bottom: 25px;
            color: #2c3e50;
        }
        .result-box {
            background: transparent;
            border: none;
            padding: 0;
            margin-bottom: 25px;
        }

        .medical-disclaimer {
            background-color: #fff8e6;
            border-left: 5px solid #f39c12;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 0.95rem;
            color: #5d6d7e;
            line-height: 1.5;
        }
        .medical-disclaimer strong {
            color: #d35400;
        }
        .cardiologist-section {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #edf2f7;
        }
        .section-title-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .section-title-bar h2 {
            color: #00a99d;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cardio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .cardio-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .cardio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .cardio-card img {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            margin-bottom: 12px;
            object-fit: cover;
            border: 2px solid #00a99d;
        }
        .cardio-card h3 {
            margin: 6px 0 4px;
            color: #00a99d;
            font-size: 1.15rem;
        }
        .cardio-card .spec-tag {
            display: inline-block;
            background: #e6f7f6;
            color: #007d73;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .cardio-card .status-badge {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .cardio-card p {
            margin: 4px 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .cardio-card .card-actions {
            margin-top: auto;
            width: 100%;
            padding-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .btn-schedule {
            display: block;
            width: 100%;
            background-color: #00a99d;
            color: white;
            padding: 9px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            box-sizing: border-box;
            transition: background 0.2s;
            text-align: center;
        }
        .btn-schedule:hover {
            background-color: #007d73;
        }
        .btn-profile-secondary {
            display: block;
            width: 100%;
            background-color: #f1f5f9;
            color: #334155;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            box-sizing: border-box;
            transition: background 0.2s;
            text-align: center;
        }
        .btn-profile-secondary:hover {
            background-color: #e2e8f0;
        }
        .btn-action-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .btn-main {
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 1rem;
            display: inline-block;
            text-align: center;
        }
        .btn-main:hover {
            background-color: #2980b9;
        }
        .no-cardio {
            text-align: center;
            padding: 25px;
            color: #64748b;
            background: #f8fafc;
            border-radius: 8px;
            margin-top: 15px;
            grid-column: 1 / -1;
            border: 1px dashed #cbd5e1;
        }
    </style>
</head>
<body>

<!-- Header -->
<?php include('../patientHeader.html'); ?>

<main style="padding: 20px 10px; flex: 1;">
<div class="result-container">
    <h1 class="result-header">Prediction Result</h1>
    <div class="result-box">
        <?php if ($assessment): ?>
            <?php renderRiskCard($assessment); ?>
        <?php else: ?>
            <p class="error">The prediction model could not be run. Please try again later.</p>
        <?php endif; ?>
    </div>

    <!-- Medical Disclaimer -->
    <div class="medical-disclaimer">
        <strong>⚠️ Medical Disclaimer:</strong> Your result is not a substitute for professional medical advice, diagnosis, or treatment. If you have concerns about your heart health, consider consulting a qualified cardiologist for a thorough medical evaluation.
    </div>

    <!-- Recommended Cardiologists Section -->
    <div class="cardiologist-section" id="cardiologistSection">
        <div class="section-title-bar">
            <h2>🫀 Recommended Cardiologists</h2>
            <a href="doctors.php?search=Cardiologist" class="btn-main" style="padding: 8px 16px; font-size: 0.9rem;">View All Doctors</a>
        </div>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 15px;">
            You can consult with available cardiologist specialists registered on our platform below:
        </p>

        <div class="cardio-grid">
            <?php
            if ($cardio_res && $cardio_res->num_rows > 0) {
                while ($doc = $cardio_res->fetch_assoc()) {
                    $doctorName = htmlspecialchars($doc['dname']);
                    $specName = htmlspecialchars($doc['sname']);
                    $address = !empty($doc['daddress']) ? htmlspecialchars($doc['daddress']) : 'Location Available Upon Request';
                    $contact = !empty($doc['dcontact']) ? htmlspecialchars($doc['dcontact']) : 'Contact Available';
                    $nmc = !empty($doc['nmc']) ? htmlspecialchars($doc['nmc']) : '';
                    $hasSchedule = (int)$doc['upcoming_schedules'] > 0;
                    ?>
                    <div class="cardio-card">
                        <img src="../images/user.png" alt="Dr. <?= $doctorName ?>">
                        <h3>Dr. <?= $doctorName ?></h3>
                        <span class="spec-tag"><?= $specName ?></span>
                        
                        <?php if ($hasSchedule): ?>
                            <span class="status-badge">● Available Sessions</span>
                        <?php else: ?>
                            <span class="status-badge" style="background:#f1f5f9; color:#64748b;">● Registered Doctor</span>
                        <?php endif; ?>

                        <?php if (!empty($nmc)): ?>
                            <p><strong>NMC Reg:</strong> <?= $nmc ?></p>
                        <?php endif; ?>
                        <p>📍 <?= $address ?></p>
                        <p>📞 <?= $contact ?></p>

                        <div class="card-actions">
                            <a href="schedule.php?did=<?= $doc['did'] ?>" class="btn-schedule">Book Appointment</a>
                            <a href="schedule.php?did=<?= $doc['did'] ?>" class="btn-profile-secondary">View Profile & Schedules</a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='no-cardio'>";
                echo "<p>No cardiologists are currently available.</p>";
                echo "<a href='doctors.php?search=Cardiologist' class='btn-schedule' style='display:inline-block; width:auto; margin-top:10px;'>Browse Cardiologists Directory</a>";
                echo "</div>";
            }
            ?>
        </div>
    </div>

    <div class="btn-action-group">
        <a href="downloadAssessment.php?id=<?= (int)$data['pdid'] ?>" class="btn-main" style="background-color: #00897b;">Download PDF</a>
        <a href="viewHistory.php" class="btn-main" style="background-color: #6c757d;">Back to History</a>
        <a href="form.php" class="btn-main">Take Another Assessment</a>
        <a href="index.php" class="btn-main" style="background-color: #2c3e50;">Home Page</a>
    </div>
</div>
</main>

<!-- Footer -->
<?php include('../footer.html'); ?>

</body>
</html>
