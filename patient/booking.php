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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        /* Time-slot picker: each free slot is a card wrapping a hidden radio. */
        .slot-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
            margin: 0 0 24px;
        }

        .slot {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 14px 12px;
            border: 1px solid #cfdbe3;
            border-radius: 12px;
            background: #fff;
            text-align: center;
        }

        .slot-time {
            color: #12304a;
            font-size: 16px;
            font-weight: 600;
        }

        .slot-state {
            color: #6b8193;
            font-size: 14px;
        }

        .slot-free input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slot-free {
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
        }

        .slot-free:hover {
            border-color: #00a99d;
            background: #f2fbfa;
        }

        .slot-free:has(input[type="radio"]:checked) {
            border-color: #00a99d;
            background: #e6f6f4;
            box-shadow: 0 0 0 3px rgba(0, 169, 157, 0.18);
        }

        .slot-free:has(input[type="radio"]:checked) .slot-state {
            color: #00786f;
            font-weight: 600;
        }

        /* The radio is hidden, so keyboard focus is shown on the card. */
        .slot-free:has(input[type="radio"]:focus-visible) {
            outline: 2px solid #00a99d;
            outline-offset: 2px;
        }

        .slot-booked {
            background: #f5f8fa;
            border-style: dashed;
            cursor: not-allowed;
        }

        .slot-booked .slot-time {
            color: #9aabb8;
            text-decoration: line-through;
        }

        .slot-booked .slot-state {
            color: #9aabb8;
        }

        .fee {
            color: #00786f;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Booking</span>
        <h1>Book an appointment</h1>
        <p>Choose a free one-hour slot in this session to book your visit.</p>
    </div>
</section>

<main class="page-body">
<?php if (!$session_row): ?>
    <section class="panel">
        <div class="empty-state">
            <p><?= htmlspecialchars($page_error) ?></p>
            <a href="schedule.php" class="btn btn-light">Back to sessions</a>
        </div>
    </section>
<?php else: ?>
    <?php $isPast = $session_row['sdate'] < $today; ?>
    <div class="page-grid">
        <section class="panel">
            <div class="panel__head">
                <div>
                    <h2 class="panel__title">Choose a time slot</h2>
                    <p class="panel__sub"><?= date('l j F Y', strtotime($session_row['sdate'])) ?></p>
                </div>
                <?php if ($total_count > 0): ?>
                    <span class="badge <?= $free_count > 0 ? 'badge-success' : 'badge-muted' ?>">
                        <?= $free_count ?> of <?= $total_count ?> available
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($isPast): ?>
                <div class="empty-state">
                    <p>This session has already taken place.</p>
                    <a href="schedule.php" class="btn btn-light">See upcoming sessions</a>
                </div>
            <?php elseif ($total_count === 0): ?>
                <div class="empty-state">
                    <p>This session has no time slots yet.</p>
                    <a href="schedule.php" class="btn btn-light">Back to sessions</a>
                </div>
            <?php else: ?>
                <form action="booking-complete.php" method="POST">
                    <div class="slot-grid">
                        <?php foreach ($slots as $slot): ?>
                            <?php
                                $range = date("g:i A", strtotime($slot["start_time"])) . ' - '
                                       . date("g:i A", strtotime($slot["end_time"]));
                            ?>
                            <?php if ((int)$slot['is_booked'] > 0): ?>
                                <?php /* No input at all, so a booked slot cannot be posted back. */ ?>
                                <div class="slot slot-booked">
                                    <span class="slot-time"><?= $range ?></span>
                                    <span class="slot-state">Booked</span>
                                </div>
                            <?php else: ?>
                                <label class="slot slot-free">
                                    <input type="radio" name="tid" value="<?= (int)$slot['tid'] ?>" required>
                                    <span class="slot-time"><?= $range ?></span>
                                    <span class="slot-state">Available</span>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($free_count > 0): ?>
                        <div class="btn-row">
                            <button type="submit" class="btn btn-primary">Book this slot</button>
                            <a href="schedule.php" class="btn btn-light">Back to sessions</a>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-info">Every time slot in this session is booked. Please choose another session.</div>
                        <a href="schedule.php" class="btn btn-light">Back to sessions</a>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </section>

        <aside class="panel">
            <p class="panel__label">Session details</p>
            <ul class="detail-list">
                <li><span>Doctor</span><strong>Dr. <?= htmlspecialchars($session_row['dname']) ?></strong></li>
                <li><span>Specialty</span><strong><?= htmlspecialchars($session_row['sname']) ?></strong></li>
                <li><span>Email</span><strong><?= htmlspecialchars($session_row['demail']) ?></strong></li>
                <li><span>Date</span><strong><?= date('D j M Y', strtotime($session_row['sdate'])) ?></strong></li>
                <li><span>Channelling fee</span><strong class="fee">NRs 2,000.00</strong></li>
            </ul>
        </aside>
    </div>
<?php endif; ?>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
