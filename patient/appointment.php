<?php
    include("../connection.php");
    include_once("../auth.php");

    // Match the other patient pages, so "today" cannot disagree with the
    // sessions list when the server clock is in another zone.
    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    $rowPatient = requireRole($con, 'patient');
    $useremail = $rowPatient['pemail'];
    $userid   = (int)$rowPatient['pid'];
    $username = $rowPatient['pname'];

    // One query for both tables: upcoming and past differ only by date, and
    // splitting in PHP keeps the two lists guaranteed consistent.
    // shared_pdid tells us whether a heart-risk reading is attached to the visit.
    $sql = "SELECT appointment.apid, appointment.adate, appointment.status,
                   appointment.doctor_notes, appointment.notes_updated_at,
                   doctors.dname AS dname, specialties.sname AS sname,
                   timeslot.start_time AS start_time, timeslot.end_time AS end_time,
                   (SELECT pd.pdid FROM patient_data pd
                     WHERE pd.apid = appointment.apid LIMIT 1) AS shared_pdid
    FROM appointment
    INNER JOIN timeslot ON appointment.tid = timeslot.tid
    INNER JOIN schedule ON timeslot.scid = schedule.scid
    INNER JOIN doctors ON schedule.did = doctors.did
    INNER JOIN specialties ON doctors.spid = specialties.spid
    WHERE appointment.pid = ?
    ORDER BY appointment.adate ASC, timeslot.start_time ASC";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();

    $upcoming = [];
    $past     = [];
    while ($row = $result->fetch_assoc()) {
        // A visit the doctor has already recorded counts as past even on the
        // day itself, so its notes show up straight away.
        if ($row['adate'] >= $today && $row['status'] === 'booked') {
            $upcoming[] = $row;
        } else {
            $past[] = $row;
        }
    }
    $stmt->close();

    // Most recent visit first reads better than oldest-first for history.
    $past = array_reverse($past);

    // Readings the patient could still attach to an upcoming visit.
    $unshared = [];
    $stmt = $con->prepare("SELECT pdid, timestamp FROM patient_data
                            WHERE pid = ? AND apid IS NULL
                            ORDER BY pdid DESC");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $unshared[] = $r;
    }
    $stmt->close();

    $con->close();

    $notices = [
        'cancelled' => ['ok',  'Your appointment was cancelled and the timeslot is free again.'],
        'shared'    => ['ok',  'Your heart assessment is now visible to that doctor.'],
        'unshared'  => ['ok',  'Your heart assessment is no longer shared with that doctor.'],
        'past'      => ['bad', 'That appointment has already taken place, so it cannot be cancelled.'],
        'recorded'  => ['bad', 'Your doctor has already recorded that visit, so it cannot be cancelled.'],
        'notfound'  => ['bad', 'That appointment could not be found.'],
        'error'     => ['bad', 'Something went wrong. Please try again.'],
    ];
    $msg = isset($_GET['msg']) && isset($notices[$_GET['msg']]) ? $notices[$_GET['msg']] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="../css/patientIndex.css" rel="stylesheet">
    <style>
        .notice {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .notice-ok  { background-color: #e6f6f1; border: 1px solid #a6ddc9; color: #0b7d56; }
        .notice-bad { background-color: #fdecea; border: 1px solid #f5c2bd; color: #8b2c22; }

        .cancel-btn {
            background-color: #ffffff;
            color: #b23c3c;
            border: 1px solid #e0b4b4;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .cancel-btn:hover { background-color: #fdecea; }

        .share-form { display: flex; gap: 6px; align-items: center; }
        .share-form select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.85em;
            max-width: 150px;
        }
        .share-btn {
            background-color: #00a99d;
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85em;
        }
        .shared-tag { color: #0b7d56; font-size: 0.9em; }
        .muted { color: #9aa0a6; font-size: 0.9em; }

        .section-gap { margin-top: 35px; }

        .status {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 0.85em;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-completed { background: #f0fff4; color: #276749; }
        .status-no-show   { background: #fff5f5; color: #c53030; }

        .notes-cell { max-width: 360px; }
        .notes-cell summary { cursor: pointer; color: #00a99d; }
        .doctor-notes {
            margin-top: 8px;
            padding: 10px 12px;
            background: #f7fafc;
            border-left: 3px solid #00a99d;
            border-radius: 4px;
            line-height: 1.5;
            white-space: normal;
        }
        .notes-date { margin-top: 4px; }
    </style>
</head>
<body>
<div class="container">
    <?php include 'sidebar.html';?>
    <div class="main-content">
        <h1>Appointment</h1>
        <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt; <span>My Appointments</span>
        </div>

        <?php if ($msg): ?>
            <div class="notice notice-<?php echo $msg[0]; ?>"><?php echo htmlspecialchars($msg[1]); ?></div>
        <?php endif; ?>

        <div class="profile-form">
            <h2>Upcoming appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Heart Assessment</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($upcoming) > 0) {
                            foreach ($upcoming as $row) {
                                // Show the booked slot as a range, matching the
                                // labels the patient picked from on booking.php.
                                $slot = date("h:i A", strtotime($row['start_time']))
                                      . " - "
                                      . date("h:i A", strtotime($row['end_time']));

                                echo "<tr>";
                                echo "<td>Dr. ".htmlspecialchars($row['dname'])."</td>";
                                echo "<td>".htmlspecialchars($row['sname'])."</td>";
                                echo "<td>".htmlspecialchars($row['adate'])."</td>";
                                echo "<td>".htmlspecialchars($slot)."</td>";

                                // --- share / unshare a heart reading ---------
                                echo "<td>";
                                if ($row['shared_pdid']) {
                                    echo "<span class='shared-tag'>&#10003; Shared</span> ";
                                    echo "<form method='POST' action='shareAssessment.php' style='display:inline'>";
                                    echo "<input type='hidden' name='apid' value='".(int)$row['apid']."'>";
                                    echo "<input type='hidden' name='action' value='unshare'>";
                                    echo "<button type='submit' class='cancel-btn'>Stop sharing</button>";
                                    echo "</form>";
                                } elseif (count($unshared) > 0) {
                                    echo "<form method='POST' action='shareAssessment.php' class='share-form'>";
                                    echo "<input type='hidden' name='apid' value='".(int)$row['apid']."'>";
                                    echo "<input type='hidden' name='action' value='share'>";
                                    echo "<select name='pdid' required>";
                                    echo "<option value='' disabled selected hidden>Choose a reading</option>";
                                    foreach ($unshared as $u) {
                                        echo "<option value='".(int)$u['pdid']."'>"
                                           . htmlspecialchars(date("d M Y", strtotime($u['timestamp'])))
                                           . "</option>";
                                    }
                                    echo "</select>";
                                    echo "<button type='submit' class='share-btn'>Share</button>";
                                    echo "</form>";
                                } else {
                                    echo "<span class='muted'>No reading yet &mdash; <a href='form.php'>take the test</a></span>";
                                }
                                echo "</td>";

                                // --- cancel ----------------------------------
                                echo "<td>";
                                echo "<form method='POST' action='cancelAppointment.php' "
                                   . "onsubmit=\"return confirm('Cancel this appointment? The timeslot will be released for other patients.');\">";
                                echo "<input type='hidden' name='apid' value='".(int)$row['apid']."'>";
                                echo "<button type='submit' class='cancel-btn'>Cancel</button>";
                                echo "</form>";
                                echo "</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No upcoming appointments. <a href='schedule.php'>Browse available sessions</a></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

            <div class="section-gap">
                <h2>Past appointments</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Doctor's notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (count($past) > 0) {
                            foreach ($past as $row) {
                                $slot = date("h:i A", strtotime($row['start_time']))
                                      . " - "
                                      . date("h:i A", strtotime($row['end_time']));

                                echo "<tr>";
                                echo "<td>Dr. ".htmlspecialchars($row['dname'])."</td>";
                                echo "<td>".htmlspecialchars($row['sname'])."</td>";
                                echo "<td>".htmlspecialchars($row['adate'])."</td>";
                                echo "<td>".htmlspecialchars($slot)."</td>";

                                echo "<td>";
                                if ($row['status'] === 'completed') {
                                    echo "<span class='status status-completed'>&#10003; Completed</span>";
                                } elseif ($row['status'] === 'no_show') {
                                    echo "<span class='status status-no-show'>&#10007; Missed</span>";
                                } else {
                                    echo "<span class='muted'>Not recorded</span>";
                                }
                                echo "</td>";

                                echo "<td class='notes-cell'>";
                                if (trim((string)$row['doctor_notes']) !== '') {
                                    echo "<details><summary>Read notes</summary>";
                                    echo "<div class='doctor-notes'>" . nl2br(htmlspecialchars($row['doctor_notes'])) . "</div>";
                                    if ($row['notes_updated_at']) {
                                        echo "<div class='muted notes-date'>Updated "
                                           . htmlspecialchars(date("d M Y", strtotime($row['notes_updated_at'])))
                                           . "</div>";
                                    }
                                    echo "</details>";
                                } else {
                                    echo "<span class='muted'>&mdash;</span>";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No past appointments yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>
