<?php
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid    = $userfetch["pid"];
    $username  = $userfetch["pname"];

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    // Search comes from the URL (links like doctors.php?search=Cardiologist)
    // or from the search box; it matches a doctor's name or specialty.
    $keyword = '';
    if (isset($_GET['search'])) {
        $keyword = trim((string)$_GET['search']);
    } elseif (isset($_POST['search'])) {
        $keyword = trim((string)$_POST['search']);
    }

    $sql = "SELECT d.did, d.dname, d.dcontact, d.daddress, s.sname,
                   (SELECT COUNT(*) FROM schedule sc WHERE sc.did = d.did AND sc.sdate >= ?) AS upcoming
              FROM doctors d
              JOIN specialties s ON d.spid = s.spid";
    if ($keyword !== '') {
        $sql .= " WHERE s.sname LIKE ? OR d.dname LIKE ?";
    }
    $sql .= " ORDER BY d.dname ASC";

    $stmt = $con->prepare($sql);
    if ($keyword !== '') {
        $like = '%' . $keyword . '%';
        $stmt->bind_param("sss", $today, $like, $like);
    } else {
        $stmt->bind_param("s", $today);
    }
    $stmt->execute();
    $doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Doctors</span>
        <h1>Find a doctor</h1>
        <p>Browse the doctors on DaaktarSahab and see when they are available to book.</p>
    </div>
</section>

<main class="page-body">
    <section class="panel">
        <form method="GET" action="doctors.php" class="search-bar" role="search">
            <input type="text" name="search" placeholder="Search by name or specialty, e.g. Cardiologist"
                   value="<?= htmlspecialchars($keyword) ?>" aria-label="Search doctors">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($keyword !== ''): ?>
                <a href="doctors.php" class="btn btn-light">Clear</a>
            <?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">
                    <?= $keyword !== '' ? 'Results for "' . htmlspecialchars($keyword) . '"' : 'All doctors' ?>
                </h2>
                <p class="panel__sub"><?= count($doctors) ?> <?= count($doctors) === 1 ? 'doctor' : 'doctors' ?></p>
            </div>
        </div>

        <?php if ($doctors): ?>
            <div class="doctor-grid">
                <?php foreach ($doctors as $doc): ?>
                    <?php $upcoming = (int)$doc['upcoming']; ?>
                    <article class="doctor-card">
                        <img src="../images/user.png" alt="">
                        <h3>Dr. <?= htmlspecialchars($doc['dname']) ?></h3>
                        <span class="badge badge-info"><?= htmlspecialchars($doc['sname']) ?></span>
                        <?php if (!empty($doc['daddress'])): ?>
                            <p><?= htmlspecialchars($doc['daddress']) ?></p>
                        <?php endif; ?>
                        <p><?= htmlspecialchars($doc['dcontact']) ?></p>
                        <div class="doctor-card__actions">
                            <?php if ($upcoming > 0): ?>
                                <a class="btn btn-primary" href="schedule.php?did=<?= (int)$doc['did'] ?>">
                                    View <?= $upcoming ?> upcoming <?= $upcoming === 1 ? 'session' : 'sessions' ?>
                                </a>
                            <?php else: ?>
                                <span class="btn is-disabled">No sessions scheduled</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No doctors match "<?= htmlspecialchars($keyword) ?>".</p>
                <a href="doctors.php" class="btn btn-light">Show all doctors</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
