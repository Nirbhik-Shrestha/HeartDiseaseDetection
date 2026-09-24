<?php
    include("../connection.php");
    include_once("../auth.php");
    include_once("../scheduleFunctions.php");

    date_default_timezone_set('Asia/Kathmandu');
    $today = date("Y-m-d");

    $userfetch = requireRole($con, 'doctor');
    $useremail = $userfetch["demail"];
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

    /** "9:00 AM - 12:00 PM" for a session, or null when it has no slots. */
    function sessionRange($row)
    {
        if ((int)$row['total_slots'] === 0) {
            return null;
        }
        return date("g:i A", strtotime($row["min_start_time"])) . ' - ' . date("g:i A", strtotime($row["max_end_time"]));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .session-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 16px;
            align-items: end;
        }

        .session-form .field {
            margin: 0;
        }

        .session-form .btn {
            height: 48px;
        }

        @media (max-width: 900px) {
            .session-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Schedules</span>
        <h1>Your sessions</h1>
        <p>Add the days and hours you are available. Each session is split into one-hour slots that patients can book.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($notice): ?>
        <div class="notice notice-<?= $notice[0] ?>"><?= htmlspecialchars($notice[1]) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-bad" role="alert">
            <strong>This session could not be added:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Add a session</h2>
                <p class="panel__sub">One session per day, up to <?= MAX_SLOTS_PER_SESSION ?> one-hour slots, finishing by midnight.</p>
            </div>
        </div>
        <form method="POST" action="schedule.php" class="session-form">
            <div class="field">
                <label for="sdate">Date</label>
                <input type="date" id="sdate" name="sdate" min="<?= $today ?>" value="<?= htmlspecialchars($old['sdate']) ?>" required>
            </div>
            <div class="field">
                <label for="stime">Start time</label>
                <input type="time" id="stime" name="stime" value="<?= htmlspecialchars($old['stime']) ?>" required>
            </div>
            <div class="field">
                <label for="nop">Number of slots</label>
                <input type="number" id="nop" name="nop" min="1" max="<?= MAX_SLOTS_PER_SESSION ?>" placeholder="e.g. 4" value="<?= htmlspecialchars($old['nop']) ?>" required>
            </div>
            <button type="submit" name="add_session" class="btn btn-primary">Add session</button>
        </form>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Upcoming sessions</h2>
                <p class="panel__sub"><?= count($upcoming_sessions) ?> from today onwards</p>
            </div>
        </div>
        <?php if ($upcoming_sessions): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr><th>Date</th><th>Time</th><th>Booked</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_sessions as $row): ?>
                            <?php
                                $total  = (int)$row['total_slots'];
                                $booked = (int)$row['booked_slots'];
                                $range  = sessionRange($row);
                            ?>
                            <tr>
                                <td class="cell-strong nowrap" data-label="Date"><?= $row['sdate'] === $today ? 'Today' : date('D j M Y', strtotime($row['sdate'])) ?></td>
                                <td data-label="Time"><?= $range ?: '<span class="cell-muted">No time slots</span>' ?></td>
                                <td data-label="Booked">
                                    <?php if ($total === 0): ?>
                                        <span class="badge badge-muted">&mdash;</span>
                                    <?php elseif ($booked === $total): ?>
                                        <span class="badge badge-info">Fully booked</span>
                                    <?php elseif ($booked > 0): ?>
                                        <span class="badge badge-success"><?= $booked ?> of <?= $total ?> booked</span>
                                    <?php else: ?>
                                        <span class="badge badge-muted">0 of <?= $total ?> booked</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($booked > 0): ?>
                                        <?php /* Patients rely on this session; removing it would silently drop their bookings. */ ?>
                                        <span class="cell-muted">Has bookings</span>
                                    <?php else: ?>
                                        <form method="POST" action="schedule.php" style="margin: 0" onsubmit="return confirm('Remove this session?');">
                                            <input type="hidden" name="scid" value="<?= (int)$row['scid'] ?>">
                                            <button type="submit" name="delete_session" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><p>No upcoming sessions. Add one above so patients can book you.</p></div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Past sessions</h2>
                <p class="panel__sub">Most recent first.</p>
            </div>
        </div>
        <?php if ($past_sessions): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr><th>Date</th><th>Time</th><th>Booked</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_sessions as $row): ?>
                            <?php $range = sessionRange($row); ?>
                            <tr>
                                <td class="cell-strong nowrap" data-label="Date"><?= date('D j M Y', strtotime($row['sdate'])) ?></td>
                                <td data-label="Time"><?= $range ?: '<span class="cell-muted">No time slots</span>' ?></td>
                                <td data-label="Booked"><?= (int)$row['booked_slots'] ?> of <?= (int)$row['total_slots'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state"><p>No past sessions.</p></div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
