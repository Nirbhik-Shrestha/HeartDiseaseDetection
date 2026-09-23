<?php
/**
 * Record what happened at an appointment: its status (completed / no-show)
 * and consultation notes, which the patient sees on their appointments page.
 *
 * Only the doctor whose schedule holds the appointment can open it, and only
 * from the appointment's own date onwards: a visit cannot be completed before
 * it happens.
 */
include("../connection.php");
include_once("../auth.php");
date_default_timezone_set('Asia/Kathmandu');

$userfetch = requireRole($con, 'doctor');
$useremail = $userfetch["demail"];
$userid   = (int)$userfetch["did"];
$username = $userfetch["dname"];

$today = date('Y-m-d');
$apid = isset($_REQUEST['apid']) ? (int)$_REQUEST['apid'] : 0;

const CONSULTATION_STATUSES = [
    'booked'    => 'Not recorded yet',
    'completed' => 'Completed',
    'no_show'   => 'No-show (patient did not attend)',
];
const MAX_NOTES_LENGTH = 5000;

// The join up to schedule.did makes "this appointment is mine" part of the query.
$stmt = $con->prepare(
    "SELECT a.*, p.pname, p.pdob, t.start_time, t.end_time,
            (SELECT pd.pdid FROM patient_data pd WHERE pd.apid = a.apid LIMIT 1) AS shared_pdid
       FROM appointment a
       INNER JOIN patients p ON a.pid = p.pid
       INNER JOIN timeslot t ON a.tid = t.tid
       INNER JOIN schedule s ON t.scid = s.scid
      WHERE a.apid = ? AND s.did = ?"
);
$stmt->bind_param("ii", $apid, $userid);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

$errors = [];
$canRecord = $appointment && $appointment['adate'] <= $today;

if ($appointment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = isset($_POST['status']) ? (string)$_POST['status'] : '';
    $notes  = isset($_POST['doctor_notes']) ? trim((string)$_POST['doctor_notes']) : '';

    if (!$canRecord) {
        $errors[] = "This visit can be recorded from " . $appointment['adate'] . ".";
    }
    if (!array_key_exists($status, CONSULTATION_STATUSES)) {
        $errors[] = "Please choose a status.";
    }
    if (mb_strlen($notes) > MAX_NOTES_LENGTH) {
        $errors[] = "Notes can be at most " . MAX_NOTES_LENGTH . " characters.";
    }

    if (!$errors) {
        $notesOrNull = $notes === '' ? null : $notes;
        // notes_updated_at only moves when the notes themselves change.
        $stmt = $con->prepare(
            "UPDATE appointment
                SET status = ?,
                    notes_updated_at = IF(doctor_notes <=> ?, notes_updated_at, NOW()),
                    doctor_notes = ?
              WHERE apid = ?"
        );
        $stmt->bind_param("sssi", $status, $notesOrNull, $notesOrNull, $apid);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $con->close();
            header("Location: appointment.php?msg=saved");
            exit();
        }
        $errors[] = "Could not save. Please try again.";
    }

    // Keep what the doctor typed on a failed save.
    $appointment['status'] = $status;
    $appointment['doctor_notes'] = $notes;
}
$con->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation</title>
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
            max-width: 760px;
        }
        .panel h2 { margin-top: 0; font-size: 18px; }
        .meta p { margin: 4px 0; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 6px; }
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 9px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            box-sizing: border-box;
            font: inherit;
        }
        .form-group textarea { min-height: 180px; resize: vertical; }
        .hint { font-size: 13px; color: #777; margin: 6px 0 0; }

        .save-btn {
            background-color: #00a99d;
            color: #ffffff;
            border: none;
            padding: 10px 22px;
            cursor: pointer;
            border-radius: 5px;
            font-size: 15px;
        }
        .save-btn:hover { background-color: #008f85; }

        .errors {
            background: #fdecea;
            border: 1px solid #f5c2bd;
            border-radius: 5px;
            padding: 10px 14px;
            color: #8b2c22;
            margin-bottom: 18px;
        }
        .errors ul { margin: 6px 0 0 18px; padding: 0; }

        .back { text-decoration: none; color: #00a99d; display: inline-block; margin-top: 6px; }
        .back:hover { text-decoration: underline; }
        .muted { color: #777; }
    </style>
</head>
<body>
<div class="container">
<?php include("sidebar.php"); ?>
    <div class="main-content">
        <h1>Consultation</h1>
        <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt;
            <a href="appointment.php">My Appointments</a> &gt;
            <span>Consultation</span>
        </div>

        <?php if (!$appointment): ?>
            <div class="panel">
                <p>No appointment was found in your schedule with that reference.</p>
                <a class="back" href="appointment.php">Back to appointments</a>
            </div>
        <?php else: ?>
            <div class="panel meta">
                <h2>Appointment</h2>
                <p><strong>Patient:</strong> <?= htmlspecialchars($appointment['pname']) ?></p>
                <p><strong>Date of birth:</strong> <?= htmlspecialchars($appointment['pdob']) ?></p>
                <p><strong>When:</strong>
                    <?= htmlspecialchars($appointment['adate']) ?> at
                    <?= date("h:i A", strtotime($appointment['start_time'])) ?> -
                    <?= date("h:i A", strtotime($appointment['end_time'])) ?>
                </p>
                <p><strong>Heart assessment:</strong>
                    <?php if ($appointment['shared_pdid']): ?>
                        <a class="back" style="margin: 0" href="viewAssessment.php?apid=<?= (int)$appointment['apid'] ?>">View shared assessment</a>
                    <?php else: ?>
                        <span class="muted">Not shared by the patient</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="panel">
                <h2>Record the visit</h2>

                <?php if ($errors): ?>
                    <div class="errors" role="alert">
                        <strong>Not saved:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!$canRecord): ?>
                    <p class="muted">This appointment is on <?= htmlspecialchars($appointment['adate']) ?>. You can record the visit from that day.</p>
                <?php else: ?>
                    <form method="post" action="consultation.php">
                        <input type="hidden" name="apid" value="<?= (int)$appointment['apid'] ?>">

                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <?php foreach (CONSULTATION_STATUSES as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $appointment['status'] === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="doctor_notes">Consultation notes</label>
                            <textarea id="doctor_notes" name="doctor_notes" maxlength="<?= MAX_NOTES_LENGTH ?>"
                                placeholder="Findings, advice, prescriptions, tests to arrange, follow-up..."><?= htmlspecialchars((string)$appointment['doctor_notes']) ?></textarea>
                            <p class="hint">The patient can read these notes on their appointments page.</p>
                            <?php if ($appointment['notes_updated_at']): ?>
                                <p class="hint">Last updated <?= htmlspecialchars(date('j M Y, g:i A', strtotime($appointment['notes_updated_at']))) ?>.</p>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="save-btn">Save</button>
                    </form>
                <?php endif; ?>

                <p><a class="back" href="appointment.php">Back to appointments</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
