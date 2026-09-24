<?php 
    //import database
    include("../connection.php");
    include_once("../auth.php");
    include_once("../prediction.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];
    // The hero greets by first name only.
    $firstName = strtok(trim($username), ' ') ?: $username;

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    // Next visit that is still ahead of the patient.
    $stmt = $con->prepare(
        "SELECT a.adate, t.start_time, t.end_time, d.dname, sp.sname
           FROM appointment a
           JOIN timeslot t     ON t.tid = a.tid
           JOIN schedule s     ON s.scid = t.scid
           JOIN doctors d      ON d.did = s.did
           JOIN specialties sp ON sp.spid = d.spid
          WHERE a.pid = ? AND a.adate >= ? AND a.status = 'booked'
          ORDER BY a.adate, t.start_time
          LIMIT 1");
    $stmt->bind_param("is", $userid, $today);
    $stmt->execute();
    $nextVisit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // The two most recent heart checks, for the latest score and its change.
    $stmt = $con->prepare("SELECT * FROM patient_data WHERE pid = ? ORDER BY `timestamp` DESC, pdid DESC LIMIT 2");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $checks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    ensureRiskScores($con, $checks);
    $latestCheck = $checks && $checks[0]['risk_score'] !== null ? $checks[0] : null;
    $previousCheck = isset($checks[1]) && $checks[1]['risk_score'] !== null ? $checks[1] : null;

    // Most recent consultation note from any doctor.
    $stmt = $con->prepare(
        "SELECT a.doctor_notes, a.adate, d.dname
           FROM appointment a
           JOIN timeslot t ON t.tid = a.tid
           JOIN schedule s ON s.scid = t.scid
           JOIN doctors d  ON d.did = s.did
          WHERE a.pid = ? AND a.doctor_notes IS NOT NULL AND a.doctor_notes <> ''
          ORDER BY a.notes_updated_at DESC
          LIMIT 1");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $latestNote = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $con->close();

    /** "Today", "Tomorrow" or "In 5 days" for an upcoming date. */
    function daysAway($date, $today)
    {
        $days = (int)round((strtotime($date) - strtotime($today)) / 86400);
        if ($days === 0) return 'Today';
        if ($days === 1) return 'Tomorrow';
        return "In $days days";
    }

    function shorten($text, $limit)
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '...' : $text;
    }
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Home - DaaktarSahab</title>
        
        <link rel="stylesheet" href="../css/index.css">
        <link rel="stylesheet" href="../css/home.css">
</head>
<body class="home-page">

    <!-- Header Section -->
    <?php include('../patientHeader.html') ?>
    
    <!-- Main content area -->
    <main>
        <!-- Hero Section -->
        <section class="hero" aria-label="Welcome to DaaktarSahab">
            <div class="hero-content">
                <span class="hero-eyebrow">Your heart, in good hands</span>
                <h1>Welcome back, <span><?= htmlspecialchars($firstName) ?></span></h1>
                <p>Check your heart in minutes, book a cardiologist, and get advice from your doctor, all in one place.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="form.php">Check your heart</a>
                    <a class="btn btn-light" href="schedule.php">Book a doctor</a>
                </div>
            </div>
        </section>

        <!-- The patient's own summary -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">Your health at a glance</h2>
                <div class="cards">

                    <div class="card">
                        <p class="card-label">Next appointment</p>
                        <?php if ($nextVisit): ?>
                            <p class="card-main">Dr. <?= htmlspecialchars($nextVisit['dname']) ?></p>
                            <p class="card-sub"><?= htmlspecialchars($nextVisit['sname']) ?></p>
                            <p class="card-sub">
                                <?= daysAway($nextVisit['adate'], $today) ?>,
                                <?= date('D j M', strtotime($nextVisit['adate'])) ?>
                                at <?= date('g:i A', strtotime($nextVisit['start_time'])) ?>
                            </p>
                            <a class="card-link" href="appointment.php">View appointments</a>
                        <?php else: ?>
                            <p class="card-empty">You have no upcoming appointments.</p>
                            <a class="card-link" href="schedule.php">Find an available session</a>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <p class="card-label">Latest heart check</p>
                        <?php if ($latestCheck): ?>
                            <?php $high = isHighRisk($latestCheck['risk_score']); ?>
                            <p class="card-main">
                                <span class="score"><?= riskPercent($latestCheck['risk_score']) ?><small>/100</small></span>
                                <span class="pill <?= $high ? 'pill-high' : 'pill-low' ?>"><?= $high ? 'High risk' : 'Low risk' ?></span>
                            </p>
                            <p class="card-sub">Taken <?= date('j M Y', strtotime($latestCheck['timestamp'])) ?></p>
                            <?php if ($previousCheck): ?>
                                <?php $delta = riskPercent($latestCheck['risk_score']) - riskPercent($previousCheck['risk_score']); ?>
                                <p class="card-sub">
                                    <?= $delta === 0 ? 'Same as your previous check'
                                        : ($delta < 0 ? 'Down ' : 'Up ') . abs($delta) . ' points from your previous check' ?>
                                </p>
                            <?php endif; ?>
                            <a class="card-link" href="viewHistory.php">See your history</a>
                        <?php elseif ($checks): ?>
                            <p class="card-empty">Your latest check could not be scored right now. Please try again later.</p>
                            <a class="card-link" href="viewHistory.php">See your history</a>
                        <?php else: ?>
                            <p class="card-empty">You haven't taken a heart check yet. It uses 13 values from routine tests and gives you a risk score.</p>
                            <a class="card-link" href="form.php">Take your first check</a>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <p class="card-label">From your doctor</p>
                        <?php if ($latestNote): ?>
                            <p class="note-quote"><?= htmlspecialchars(shorten($latestNote['doctor_notes'], 140)) ?></p>
                            <p class="card-sub">Dr. <?= htmlspecialchars($latestNote['dname']) ?>, visit on <?= date('j M Y', strtotime($latestNote['adate'])) ?></p>
                            <a class="card-link" href="appointment.php">Read the full notes</a>
                        <?php else: ?>
                            <p class="card-empty">After a visit, your doctor's notes and advice will appear here.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">How it works</h2>
                <div class="steps">
                    <div class="step">
                        <span class="step-number">1</span>
                        <div>
                            <h3>Take the heart check</h3>
                            <p>Enter values from your blood tests, ECG and stress test. <a href="form.php">Start the check</a>.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <div>
                            <h3>Book a cardiologist</h3>
                            <p>Pick a session and a time slot that suits you. <a href="doctors.php">Browse doctors</a>.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <div>
                            <h3>Share and get advice</h3>
                            <p>Share your result with the doctor before the visit and read their notes afterwards. <a href="appointment.php">Your appointments</a>.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include('../footer.html') ?>
</body>
</html>
