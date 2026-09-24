<?php
include("../connection.php");
include_once("../auth.php");

$userfetch = requireRole($con, 'doctor');
$useremail = $userfetch["demail"];
$userid    = (int)$userfetch["did"];
$username  = $userfetch["dname"];

date_default_timezone_set('Asia/Kathmandu');
$today = date('Y-m-d');

// Patients who have booked with this doctor, with how many visits and when
// the most recent (or next) one is.
$stmt = $con->prepare(
    "SELECT p.pid, p.pname, p.pemail, p.pcontact, p.paddress, p.pdob,
            COUNT(a.apid) AS visits,
            MAX(a.adate)  AS latest
       FROM patients p
       JOIN appointment a ON a.pid = p.pid
       JOIN timeslot t    ON a.tid = t.tid
       JOIN schedule s    ON t.scid = s.scid
      WHERE s.did = ?
      GROUP BY p.pid, p.pname, p.pemail, p.pcontact, p.paddress, p.pdob
      ORDER BY p.pname ASC"
);
$stmt->bind_param("i", $userid);
$stmt->execute();
$patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$con->close();

/** Whole years between a date of birth and today. */
function ageFrom($dob, $today)
{
    $birth = DateTime::createFromFormat('Y-m-d', $dob);
    return $birth ? $birth->diff(new DateTime($today))->y : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Patients</span>
        <h1>Your patients</h1>
        <p>Everyone who has booked an appointment with you, with their contact details.</p>
    </div>
</section>

<main class="page-body">
    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">All patients</h2>
                <p class="panel__sub"><?= count($patients) ?> <?= count($patients) === 1 ? 'patient' : 'patients' ?></p>
            </div>
            <a href="appointment.php" class="btn btn-light btn-sm">My appointments</a>
        </div>

        <?php if ($patients): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $row): ?>
                            <?php $age = ageFrom($row['pdob'], $today); ?>
                            <tr>
                                <td data-label="Patient">
                                    <div>
                                        <span class="cell-strong"><?= htmlspecialchars($row['pname']) ?></span><br>
                                        <span class="cell-muted">
                                            Born <?= date('j M Y', strtotime($row['pdob'])) ?><?= $age !== null ? ' (' . $age . ')' : '' ?>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Contact">
                                    <div>
                                        <a href="mailto:<?= htmlspecialchars($row['pemail']) ?>"><?= htmlspecialchars($row['pemail']) ?></a><br>
                                        <span class="cell-muted"><?= htmlspecialchars($row['pcontact']) ?></span>
                                    </div>
                                </td>
                                <td data-label="Address"><?= htmlspecialchars($row['paddress']) ?></td>
                                <td data-label="Visits">
                                    <div>
                                        <span class="cell-strong"><?= (int)$row['visits'] ?></span><br>
                                        <span class="cell-muted nowrap">
                                            <?= $row['latest'] >= $today ? 'Next' : 'Last' ?>: <?= date('j M Y', strtotime($row['latest'])) ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No patients have booked with you yet.</p>
                <a href="schedule.php" class="btn btn-light">Add a session</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
