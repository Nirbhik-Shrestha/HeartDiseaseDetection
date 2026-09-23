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
    <style>
/*
 * Patient homepage. The header's look lives in css/header.css
 * (.site-header--patient) and the footer's in footer.html.
 */

.home-page {
    background: #f5f8fa;
    color: #3d566b;
}

.container {
    max-width: 1200px;
    width: 90%;
    margin: 0 auto;
}

/* ---- Hero ---- */
.hero {
    position: relative;
    overflow: hidden;
    min-height: 460px;
    text-align: left;
    background: #8fcde3 url("../images/life-insurance-concept-with-stethoscope.jpg") right 38% / cover no-repeat;
}

/* Pale wash on the left keeps the text readable; the heart stays clear on the right. */
.hero::before {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: linear-gradient(90deg, rgba(234, 247, 251, 0.97) 0%, rgba(234, 247, 251, 0.86) 36%, rgba(234, 247, 251, 0) 64%);
}

.hero-content {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    max-width: 1180px;
    margin: 0 auto;
    padding: 72px 48px 120px;
    box-sizing: border-box;
}

.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    padding: 6px 12px;
    border-radius: 999px;
    background: rgba(0, 169, 157, 0.12);
    color: #00786f;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hero-eyebrow::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #00a99d;
    box-shadow: 0 0 0 4px rgba(0, 169, 157, 0.2);
}

.hero-content h1 {
    max-width: 580px;
    margin: 0 0 14px;
    color: #12304a;
    font-size: clamp(34px, 4.4vw, 52px);
    font-weight: 800;
    line-height: 1.08;
    letter-spacing: -0.02em;
}

.hero-content h1 span {
    color: #00a99d;
}

.hero-content p {
    max-width: 470px;
    margin: 0;
    color: #3d566b;
    font-size: 19px;
    line-height: 1.6;
}

.hero-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 12px;
    margin-top: 28px;
}

.btn {
    display: inline-block;
    padding: 13px 24px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: 17px;
    font-weight: 600;
    text-decoration: none;
    transition: transform 0.15s, box-shadow 0.15s, background 0.15s;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background: #00a99d;
    color: #fff;
    box-shadow: 0 10px 22px -8px rgba(0, 169, 157, 0.6);
}

.btn-primary:hover {
    background: #00786f;
}

.btn-light {
    background: #fff;
    color: #00786f;
    border-color: #bfe3e0;
}

.btn-light:hover {
    background: #f1f5f4;
}

/* ---- Sections ---- */
.section {
    padding: 40px 0 10px;
}

.section-title {
    margin: 0 0 20px;
    color: #12304a;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.01em;
}

/* The glance cards float up over the bottom of the hero. */
.hero + .section {
    padding-top: 0;
}

.hero + .section .container {
    position: relative;
    margin-top: -72px;
}

/* Title kept for screen readers; the cards speak for themselves visually. */
.hero + .section .section-title {
    position: absolute;
    left: -9999px;
}

/* ---- Health at a glance ---- */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}

.card {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 24px 24px 22px;
    background: #fff;
    border: 1px solid #e3ebf0;
    border-top: 3px solid #00a99d;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(18, 48, 74, 0.06), 0 12px 32px -12px rgba(18, 48, 74, 0.18);
}

.card-label {
    margin: 0 0 12px;
    color: #00786f;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.card-main {
    margin: 0 0 6px;
    color: #12304a;
    font-size: 21px;
    font-weight: 700;
}

.card-sub {
    margin: 0 0 4px;
    color: #3d566b;
    font-size: 15.5px;
    line-height: 1.45;
}

.card-empty {
    margin: 0;
    color: #3d566b;
    font-size: 15.5px;
    line-height: 1.5;
}

.card-link {
    margin-top: auto;
    padding-top: 16px;
    color: #00786f;
    font-weight: 600;
    text-decoration: none;
}

.card-link::after {
    content: " \2192";
}

.card-link:hover {
    text-decoration: underline;
}

.score {
    color: #12304a;
    font-size: 40px;
    font-weight: 800;
    letter-spacing: -0.02em;
}

.score small {
    color: #6b8193;
    font-size: 16px;
    font-weight: 500;
}

.pill {
    display: inline-block;
    margin-left: 8px;
    padding: 3px 10px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    vertical-align: middle;
}

.pill-high { background: #fdecec; color: #b42318; }
.pill-low  { background: #e7f7ee; color: #1f7a4a; }

.note-quote {
    margin: 0 0 8px;
    padding-left: 12px;
    border-left: 3px solid #d7e3ea;
    color: #12304a;
    font-size: 15.5px;
    line-height: 1.5;
}

/* ---- How it works ---- */
.steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    padding-bottom: 48px;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 24px;
    background: #fff;
    border: 1px solid #e3ebf0;
    border-radius: 16px;
}

.step-number {
    flex: 0 0 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #00a99d;
    color: #fff;
    font-weight: 700;
    box-shadow: 0 0 0 6px #e6f6f4;
}

.step h3 {
    margin: 4px 0 6px;
    color: #12304a;
    font-size: 18px;
    font-weight: 700;
}

.step p {
    margin: 0;
    color: #3d566b;
    font-size: 15.5px;
    line-height: 1.6;
}

.step a {
    color: #00786f;
    font-weight: 600;
}

/* ---- Narrow screens ---- */
@media (max-width: 760px) {
    .hero {
        background-position: 72% 40%;
    }

    .hero::before {
        background: linear-gradient(180deg, rgba(234, 247, 251, 0.95) 0%, rgba(234, 247, 251, 0.9) 62%, rgba(234, 247, 251, 0.55) 100%);
    }

    .hero-content {
        padding: 44px 16px 96px;
    }

    .hero + .section .container {
        margin-top: -60px;
    }
}
    </style>
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
