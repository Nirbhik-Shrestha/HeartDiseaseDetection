<?php
    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid    = $userfetch["pid"];
    $username  = $userfetch["pname"];

    // Optional filters: one doctor (from the Doctors page) and/or a search
    // over doctor name, specialty or date.
    $doctorId = isset($_GET['did']) ? (int)$_GET['did'] : 0;
    $keyword  = isset($_GET['search']) ? trim((string)$_GET['search']) : '';

    // Every row reports the session's capacity, so a partly booked session is
    // never mistaken for an empty one.
    $sql = "SELECT schedule.scid, schedule.sdate, doctors.dname, specialties.sname,
                   (SELECT COUNT(*) FROM timeslot t WHERE t.scid = schedule.scid) AS total_slots,
                   (SELECT COUNT(*) FROM timeslot t
                      INNER JOIN appointment a ON a.tid = t.tid
                     WHERE t.scid = schedule.scid) AS booked_slots
              FROM schedule
              INNER JOIN doctors ON schedule.did = doctors.did
              INNER JOIN specialties ON doctors.spid = specialties.spid
             WHERE schedule.sdate >= ?";
    $types = "s";
    $params = [$today];

    if ($doctorId > 0) {
        $sql .= " AND schedule.did = ?";
        $types .= "i";
        $params[] = $doctorId;
    }
    if ($keyword !== '') {
        $sql .= " AND (doctors.dname LIKE ? OR specialties.sname LIKE ? OR schedule.sdate LIKE ?)";
        $like = '%' . $keyword . '%';
        $types .= "sss";
        array_push($params, $like, $like, $like);
    }
    $sql .= " ORDER BY schedule.sdate ASC, doctors.dname ASC";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $sessions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Name for the "Sessions with Dr. ..." heading.
    $doctorName = null;
    if ($doctorId > 0) {
        $stmt = $con->prepare("SELECT dname FROM doctors WHERE did = ?");
        $stmt->bind_param("i", $doctorId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $doctorName = $row ? $row['dname'] : null;
        $stmt->close();
    }
    $con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Sessions - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Schedules</span>
        <h1><?= $doctorName ? 'Sessions with Dr. ' . htmlspecialchars($doctorName) : 'Available sessions' ?></h1>
        <p>Pick a session, then choose a one-hour time slot on the next page.</p>
    </div>
</section>

<main class="page-body">
    <section class="panel">
        <form method="GET" action="schedule.php" class="search-bar" role="search">
            <?php if ($doctorId > 0): ?>
                <input type="hidden" name="did" value="<?= $doctorId ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Search by doctor, specialty or date (YYYY-MM-DD)"
                   value="<?= htmlspecialchars($keyword) ?>" aria-label="Search sessions">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($keyword !== '' || $doctorId > 0): ?>
                <a href="schedule.php" class="btn btn-light">Show all</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">Upcoming sessions</h2>
                <p class="panel__sub"><?= count($sessions) ?> <?= count($sessions) === 1 ? 'session' : 'sessions' ?> from today onwards</p>
            </div>
        </div>

        <?php if ($sessions): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Availability</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $row): ?>
                            <?php
                                $total = (int)$row['total_slots'];
                                $free  = $total - (int)$row['booked_slots'];
                            ?>
                            <tr>
                                <td class="cell-strong" data-label="Doctor">Dr. <?= htmlspecialchars($row['dname']) ?></td>
                                <td data-label="Specialty"><?= htmlspecialchars($row['sname']) ?></td>
                                <td data-label="Date"><?= date('D j M Y', strtotime($row['sdate'])) ?></td>
                                <td data-label="Availability">
                                    <?php if ($total === 0): ?>
                                        <span class="badge badge-muted">No time slots</span>
                                    <?php elseif ($free === 0): ?>
                                        <span class="badge badge-muted">Fully booked</span>
                                    <?php elseif ($free <= 2): ?>
                                        <span class="badge badge-warning"><?= $free ?> of <?= $total ?> left</span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?= $free ?> of <?= $total ?> left</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right">
                                    <?php if ($total > 0 && $free > 0): ?>
                                        <a class="btn btn-primary btn-sm" href="booking.php?id=<?= (int)$row['scid'] ?>">Book</a>
                                    <?php else: ?>
                                        <span class="btn btn-sm is-disabled">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>
                    <?php if ($keyword !== ''): ?>
                        No upcoming sessions match "<?= htmlspecialchars($keyword) ?>".
                    <?php elseif ($doctorName): ?>
                        Dr. <?= htmlspecialchars($doctorName) ?> has no upcoming sessions.
                    <?php else: ?>
                        There are no upcoming sessions right now.
                    <?php endif; ?>
                </p>
                <a href="doctors.php" class="btn btn-light">Browse doctors</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
