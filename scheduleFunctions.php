<?php
/**
 * Shared session (schedule) rules.
 *
 * Both admin/addSession.php and doctor/schedule.php create sessions, so the
 * validation and the slot-building live here rather than being duplicated
 * and drifting apart.
 */

// A session is a single sitting; this caps how many 1-hour slots it may hold.
define('MAX_SLOTS_PER_SESSION', 12);

/**
 * Validate a proposed session.
 *
 * @return string[] Human-readable errors; empty array means the input is good.
 */
function validateSessionInput($con, $did, $sdate, $stime, $nop)
{
    $errors = [];

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    // --- Doctor ---------------------------------------------------------
    $did = (int)$did;
    if ($did <= 0) {
        $errors[] = "Please choose a doctor.";
    } else {
        $stmt = $con->prepare("SELECT did FROM doctors WHERE did = ?");
        $stmt->bind_param("i", $did);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $errors[] = "That doctor no longer exists.";
        }
        $stmt->close();
    }

    // --- Date -----------------------------------------------------------
    $d = DateTime::createFromFormat('Y-m-d', (string)$sdate);
    if (!$d || $d->format('Y-m-d') !== $sdate) {
        $errors[] = "Please enter a valid session date.";
    } elseif ($sdate < $today) {
        // A session in the past can never be booked, so reject it outright.
        $errors[] = "The session date is in the past. Please choose $today or later.";
    }

    // --- Start time -----------------------------------------------------
    $t = DateTime::createFromFormat('H:i', substr((string)$stime, 0, 5));
    if (!$t) {
        $errors[] = "Please enter a valid start time.";
    }

    // --- Slot count -----------------------------------------------------
    if (!is_numeric($nop) || (int)$nop != $nop) {
        $errors[] = "Number of patients must be a whole number.";
    } else {
        $nop = (int)$nop;
        if ($nop < 1) {
            $errors[] = "A session needs at least 1 slot.";
        } elseif ($nop > MAX_SLOTS_PER_SESSION) {
            $errors[] = "A session can hold at most " . MAX_SLOTS_PER_SESSION . " slots.";
        }
    }

    // --- The session must finish within the same day --------------------
    if ($t && empty($errors)) {
        $start_minutes = ((int)$t->format('H')) * 60 + (int)$t->format('i');
        if ($start_minutes + ((int)$nop * 60) > 24 * 60) {
            $errors[] = "That start time and slot count run past midnight. Start earlier or use fewer slots.";
        }
    }

    // --- One session per doctor per day ---------------------------------
    if ($did > 0 && $d) {
        $stmt = $con->prepare("SELECT scid FROM schedule WHERE did = ? AND sdate = ?");
        $stmt->bind_param("is", $did, $sdate);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "That doctor already has a session on $sdate. Edit or remove it first.";
        }
        $stmt->close();
    }

    return $errors;
}

/**
 * Create a session and its consecutive 1-hour timeslots.
 *
 * Assumes validateSessionInput() has already passed.
 *
 * @return int|false The new scid, or false on failure.
 */
function createSession($con, $did, $sdate, $stime, $nop)
{
    $did = (int)$did;
    $nop = (int)$nop;

    $stmt = $con->prepare("INSERT INTO schedule (did, sdate) VALUES (?, ?)");
    $stmt->bind_param("is", $did, $sdate);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $scid = $con->insert_id;
    $stmt->close();

    $start_time = strtotime($stime);
    $slot = $con->prepare("INSERT INTO timeslot (scid, start_time, end_time) VALUES (?, ?, ?)");
    for ($i = 0; $i < $nop; $i++) {
        $slot_start = date('H:i:s', $start_time + ($i * 3600));
        $slot_end   = date('H:i:s', $start_time + (($i + 1) * 3600));
        $slot->bind_param("iss", $scid, $slot_start, $slot_end);
        $slot->execute();
    }
    $slot->close();

    return $scid;
}

/**
 * How many slots a session has, and how many are already taken.
 *
 * @return array{total:int, booked:int}
 */
function getSessionCapacity($con, $scid)
{
    $scid = (int)$scid;
    $stmt = $con->prepare(
        "SELECT COUNT(*) AS total,
                SUM(CASE WHEN a.apid IS NULL THEN 0 ELSE 1 END) AS booked
           FROM timeslot t
           LEFT JOIN appointment a ON a.tid = t.tid
          WHERE t.scid = ?"
    );
    $stmt->bind_param("i", $scid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return [
        'total'  => (int)$row['total'],
        'booked' => (int)$row['booked'],
    ];
}
