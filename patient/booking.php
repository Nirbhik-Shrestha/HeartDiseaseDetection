<?php
// Auth and all data loading happen before any output, so that the login
// redirect in requireRole() actually takes effect.
date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');

// Import database
include("../connection.php");
include_once("../auth.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
$userid = $userfetch["pid"];
$username = $userfetch["pname"];

$session_row = null;
$slots = [];
$total_count = 0;
$booked_count = 0;
$free_count = 0;
$page_error = null;

if ($_GET && isset($_GET["id"])) {
    $scid = (int)$_GET["id"];

    $sqlmain = "SELECT * FROM schedule
        INNER JOIN doctors ON schedule.did = doctors.did
        INNER JOIN specialties ON doctors.spid = specialties.spid
        WHERE schedule.scid = $scid
        ORDER BY schedule.sdate DESC";

    $result = $con->query($sqlmain);
    if ($result->num_rows > 0) {
        $session_row = $result->fetch_assoc();

        // Fetch every timeslot in the session, flagging the ones already
        // taken. Booked slots stay visible but unselectable, so the
        // session's real size is obvious even when it is nearly full.
        $timeslots_sql = "SELECT timeslot.*,
                                 (SELECT COUNT(*) FROM appointment a WHERE a.tid = timeslot.tid) AS is_booked
                            FROM timeslot
                           WHERE timeslot.scid = $scid
                           ORDER BY timeslot.start_time ASC";
        $timeslots_result = $con->query($timeslots_sql);

        while ($slot = $timeslots_result->fetch_assoc()) {
            if ((int)$slot['is_booked'] > 0) {
                $booked_count++;
            }
            $slots[] = $slot;
        }
        $total_count = count($slots);
        $free_count  = $total_count - $booked_count;
    } else {
        $page_error = "No session found with the provided ID.";
    }
} else {
    $page_error = "No session ID provided.";
}

$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }
        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        h1 {
            font-size: 2em;
            margin-bottom: 20px;
            text-align: center;
            color: #00a99d;
        }
        .dashboard-items {
            background-color: #f1f1f1;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .dashboard-items h2 {
            font-size: 1.5em;
            margin-bottom: 10px;
        }
        .dashboard-items p {
            font-size: 1em;
            line-height: 1.6;
        }
        .dashboard-items b {
            color: #00a99d;
        }
        form {
            text-align: center;
        }
        select {
            width: 100%;
            padding: 10px;
            font-size: 1em;
            margin-bottom: 20px;
        }
        input[type="submit"] {
            background-color: #00a99d;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            cursor: pointer;
            border-radius: 5px;
        }
        input[type="submit"]:hover {
            background-color: #007f7a;
        }
        .table-container {
            margin-bottom: 20px;
        }
        .slot-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 10px;
            text-align: left;
        }
        .slot-header label {
            font-weight: 500;
        }
        .slot-count {
            font-size: 0.9em;
            color: #0b7d56;
            font-weight: 500;
        }
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .slot {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 12px;
            border: 1px solid #d7dbdf;
            border-radius: 8px;
            text-align: center;
            box-sizing: border-box;
        }
        .slot-time {
            font-size: 0.95em;
            font-weight: 500;
        }
        .slot-state {
            font-size: 0.8em;
            color: #6b7075;
        }
        /* The radio itself is hidden; the whole card is the click target. */
        .slot-free input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slot-free {
            cursor: pointer;
            background-color: #fff;
            transition: border-color 0.15s, background-color 0.15s;
        }
        .slot-free:hover {
            border-color: #00a99d;
            background-color: #f2fbfa;
        }
        .slot-free:has(input[type="radio"]:checked) {
            border-color: #00a99d;
            border-width: 2px;
            background-color: #e3f6f4;
        }
        .slot-free:has(input[type="radio"]:checked) .slot-state {
            color: #00a99d;
            font-weight: 500;
        }
        /* Keyboard focus still has to be visible, since the input is hidden. */
        .slot-free:has(input[type="radio"]:focus-visible) {
            outline: 2px solid #00a99d;
            outline-offset: 2px;
        }
        .slot-booked {
            background-color: #f1f3f4;
            border-style: dashed;
            color: #9aa0a6;
            cursor: not-allowed;
        }
        .slot-booked .slot-time {
            text-decoration: line-through;
        }
        .slot-booked .slot-state {
            color: #b0b5ba;
        }
        .slot-full {
            color: #b23c3c;
            font-size: 0.95em;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
            if ($session_row) {
                $scid   = $session_row["scid"];
                $dname  = $session_row["dname"];
                $demail = $session_row["demail"];
                $sname  = $session_row["sname"];
                $sdate  = $session_row["sdate"];

                echo '<h1>Booking Details</h1>';

                echo '<form action="booking-complete.php" method="POST">
                    <input type="hidden" name="scid" value="' . $scid . '" >
                    <input type="hidden" name="adate" value="' . $sdate . '" >
                    <div class="table-container">
                        <div class="dashboard-items">
                            <h2>Session Details</h2>
                            <p>
                                <strong>Doctor Name:</strong> Dr. ' . $dname . '<br>
                                <strong>Doctor Email:</strong> ' . $demail . '<br>
                                <strong>Doctor Speciality:</strong> ' . $sname . '<br>
                                <strong>Session Scheduled Date:</strong> ' . $sdate . '<br>
                                <strong>Channeling fee:</strong> <b>Nrs 2000.00</b>
                            </p>
                        </div>
                    </div>';

                if ($total_count > 0) {
                    echo '<div class="slot-header">
                            <label>Select a Timeslot:</label>
                            <span class="slot-count">' . $free_count . ' of ' . $total_count . ' available</span>
                          </div>';

                    echo '<div class="slot-grid">';
                    foreach ($slots as $slot) {
                        $tid        = $slot["tid"];
                        $start_time = date("h:i A", strtotime($slot["start_time"]));
                        $end_time   = date("h:i A", strtotime($slot["end_time"]));
                        $is_booked  = (int)$slot['is_booked'] > 0;

                        if ($is_booked) {
                            // No input at all, so a booked slot cannot be posted
                            // back even by editing the markup.
                            echo '<div class="slot slot-booked">
                                    <span class="slot-time">' . $start_time . ' - ' . $end_time . '</span>
                                    <span class="slot-state">&#10007; Booked</span>
                                  </div>';
                        } else {
                            echo '<label class="slot slot-free">
                                    <input type="radio" name="tid" value="' . $tid . '" required>
                                    <span class="slot-time">' . $start_time . ' - ' . $end_time . '</span>
                                    <span class="slot-state">Available</span>
                                  </label>';
                        }
                    }
                    echo '</div>';

                    if ($free_count > 0) {
                        echo '<input type="submit" value="Book Now">';
                    } else {
                        echo '<p class="slot-full">Every timeslot in this session is booked. Please choose another session.</p>';
                    }
                } else {
                    echo '<p>No available timeslots for this session.</p>';
                }

                echo '</form>';
            } else {
                echo "<p>" . htmlspecialchars($page_error) . "</p>";
            }
        ?>
    </div>
</body>
</html>
