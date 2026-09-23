<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
        header("location: doctorLogin.php");
        exit();
    }
    $useremail = $_SESSION["user"];

    include("../connection.php");
    include_once("../scheduleFunctions.php");

    date_default_timezone_set('Asia/Kathmandu');
    $today = date("Y-m-d");

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

    $errors = [];
    $notice = null;
    $old    = ['sdate' => '', 'stime' => '', 'nop' => ''];

    // ---- Create a session -------------------------------------------------
    // The doctor id comes from the session, never from the form, so a doctor
    // can only ever add availability to their own calendar.
    if (isset($_POST['add_session'])) {
        $old = [
            'sdate' => $_POST['sdate'],
            'stime' => $_POST['stime'],
            'nop'   => $_POST['nop'],
        ];

        $errors = validateSessionInput($con, $userid, $_POST['sdate'], $_POST['stime'], $_POST['nop']);

        if (empty($errors)) {
            if (createSession($con, $userid, $_POST['sdate'], $_POST['stime'], $_POST['nop'])) {
                // Redirect after POST so a refresh cannot create a duplicate.
                header("location: schedule.php?msg=added");
                exit();
            }
            $errors[] = "Could not save the session. Please try again.";
        }
    }

    // ---- Delete a session -------------------------------------------------
    if (isset($_POST['delete_session'])) {
        $scid = (int)$_POST['scid'];

        // Ownership check: the session must belong to the logged-in doctor.
        $stmt = $con->prepare("SELECT scid FROM schedule WHERE scid = ? AND did = ?");
        $stmt->bind_param("ii", $scid, $userid);
        $stmt->execute();
        $owned = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$owned) {
            header("location: schedule.php?msg=notfound");
            exit();
        }

        // Refuse to delete a session patients have already booked into.
        // Deleting would cascade their appointments away without telling them.
        $capacity = getSessionCapacity($con, $scid);
        if ($capacity['booked'] > 0) {
            header("location: schedule.php?msg=hasbookings");
            exit();
        }

        $stmt = $con->prepare("DELETE FROM schedule WHERE scid = ? AND did = ?");
        $stmt->bind_param("ii", $scid, $userid);
        $ok = $stmt->execute();
        $stmt->close();

        header("location: schedule.php?msg=" . ($ok ? "deleted" : "error"));
        exit();
    }

    $notices = [
        'added'       => ['ok',  'Session added. Patients can book it now.'],
        'deleted'     => ['ok',  'Session removed.'],
        'hasbookings' => ['bad', 'That session already has bookings, so it cannot be removed. Contact the admin if it must be cancelled.'],
        'notfound'    => ['bad', 'That session could not be found.'],
        'error'       => ['bad', 'Something went wrong. Please try again.'],
    ];
    $notice = isset($_GET['msg']) && isset($notices[$_GET['msg']]) ? $notices[$_GET['msg']] : null;

    // ---- List this doctor's sessions --------------------------------------
    // Matched on did, not on name: two doctors may share a name.
    $sql = "SELECT schedule.scid, schedule.sdate,
                   MIN(timeslot.start_time) AS min_start_time,
                   MAX(timeslot.end_time)   AS max_end_time,
                   COUNT(timeslot.tid)      AS total_slots,
                   COUNT(appointment.apid)  AS booked_slots
            FROM schedule
            LEFT JOIN timeslot ON schedule.scid = timeslot.scid
            LEFT JOIN appointment ON appointment.tid = timeslot.tid
            WHERE schedule.did = ?
            GROUP BY schedule.scid, schedule.sdate
            ORDER BY schedule.sdate ASC";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $res = $stmt->get_result();

    $upcoming_sessions = [];
    $past_sessions     = [];
    while ($row = $res->fetch_assoc()) {
        if ($row['sdate'] >= $today) {
            $upcoming_sessions[] = $row;
        } else {
            $past_sessions[] = $row;
        }
    }
    $stmt->close();
    $past_sessions = array_reverse($past_sessions);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Schedule</title>
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

        .container {
            display: flex;
            flex: 1;
        }

        .main-content {
            flex-grow: 1;
            background-color: #ffffff;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .main-content h1 {
            margin-top: 0;
            font-size: 24px;
            color: #333333;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: #777777;
        }

        .breadcrumb a {
            text-decoration: none;
            color: #00a99d;
        }

        .profile-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: auto;
            flex-grow: 1;
        }

        .profile-form h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333333;
        }

        .add-session {
            background-color: #ffffff;
            border: 1px solid #e3e6e8;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .add-session h2 {
            margin-bottom: 15px;
        }

        .field-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            margin-bottom: 15px;
            flex: 1;
            min-width: 160px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .hint {
            font-weight: normal;
            color: #777777;
            font-size: 0.85em;
        }

        .update-btn {
            background-color: #00a99d;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }

        .delete-btn {
            background-color: #ffffff;
            color: #b23c3c;
            border: 1px solid #e0b4b4;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .delete-btn:hover { background-color: #fdecea; }

        .notice {
            padding: 12px 16px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .notice-ok  { background-color: #e6f6f1; border: 1px solid #a6ddc9; color: #0b7d56; }
        .notice-bad { background-color: #fdecea; border: 1px solid #f5c2bd; color: #8b2c22; }

        .form-errors {
            background-color: #fdecea;
            border: 1px solid #f5c2bd;
            border-radius: 5px;
            color: #8b2c22;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .form-errors ul { margin: 8px 0 0; padding-left: 20px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #cccccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #00a99d;
            color: #ffffff;
        }

        .muted { color: #9aa0a6; }
        .section-gap { margin-top: 35px; }

        a {
            text-decoration: none;
            color: #00a99d;
            display: inline-block;
            margin-top: 20px;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
<?php include("sidebar.php");?>

    <div class="main-content">
        <h1>Schedule</h1>
        <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt; <span>My Schedules</span>
        </div>

        <?php if ($notice): ?>
            <div class="notice notice-<?php echo $notice[0]; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <strong>This session could not be added:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="profile-form">
            <div class="add-session">
                <h2>Add availability</h2>
                <form method="POST" action="schedule.php">
                    <div class="field-row">
                        <div class="form-group">
                            <label for="sdate">Date</label>
                            <input type="date" id="sdate" name="sdate" min="<?php echo $today; ?>"
                                   value="<?php echo htmlspecialchars($old['sdate']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="stime">Start time</label>
                            <input type="time" id="stime" name="stime"
                                   value="<?php echo htmlspecialchars($old['stime']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nop">Slots <span class="hint">(1 hour each, max <?php echo MAX_SLOTS_PER_SESSION; ?>)</span></label>
                            <input type="number" id="nop" name="nop" min="1" max="<?php echo MAX_SLOTS_PER_SESSION; ?>"
                                   value="<?php echo htmlspecialchars($old['nop']); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="add_session" class="update-btn">Add Session</button>
                        </div>
                    </div>
                </form>
            </div>

            <h2>Upcoming sessions</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Booked</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if (count($upcoming_sessions) > 0) {
                            foreach ($upcoming_sessions as $row) {
                                $total  = (int)$row['total_slots'];
                                $booked = (int)$row['booked_slots'];

                                echo "<tr>";
                                echo "<td>".htmlspecialchars($row['sdate'])."</td>";

                                if ($total > 0) {
                                    $start_time = date("h:i A", strtotime($row["min_start_time"]));
                                    $end_time   = date("h:i A", strtotime($row["max_end_time"]));
                                    echo "<td>".$start_time.' - '.$end_time."</td>";
                                } else {
                                    echo "<td class='muted'>No timeslots</td>";
                                }

                                echo "<td>".$booked." of ".$total."</td>";

                                echo "<td>";
                                if ($booked > 0) {
                                    // Patients are relying on this session; removing
                                    // it here would silently drop their bookings.
                                    echo "<span class='muted'>Has bookings</span>";
                                } else {
                                    echo "<form method='POST' action='schedule.php' "
                                       . "onsubmit=\"return confirm('Remove this session?');\">";
                                    echo "<input type='hidden' name='scid' value='".(int)$row['scid']."'>";
                                    echo "<button type='submit' name='delete_session' class='delete-btn'>Remove</button>";
                                    echo "</form>";
                                }
                                echo "</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No upcoming sessions. Add one above.</td></tr>";
                        }
                    ?>
                </tbody>
            </table>

            <div class="section-gap">
                <h2>Past sessions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Booked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (count($past_sessions) > 0) {
                                foreach ($past_sessions as $row) {
                                    $total = (int)$row['total_slots'];
                                    echo "<tr>";
                                    echo "<td>".htmlspecialchars($row['sdate'])."</td>";
                                    if ($total > 0) {
                                        $start_time = date("h:i A", strtotime($row["min_start_time"]));
                                        $end_time   = date("h:i A", strtotime($row["max_end_time"]));
                                        echo "<td>".$start_time.' - '.$end_time."</td>";
                                    } else {
                                        echo "<td class='muted'>No timeslots</td>";
                                    }
                                    echo "<td>".(int)$row['booked_slots']." of ".$total."</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='3'>No past sessions.</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <a href="index.php">Back</a>
        </div>
    </div>
</div>
</body>
</html>
