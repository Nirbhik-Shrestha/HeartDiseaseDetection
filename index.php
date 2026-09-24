<?php
    include("connection.php");

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    // Real numbers for the "Doctors" card; nothing here depends on user input.
    $stats = $con->query(
        "SELECT (SELECT COUNT(*) FROM doctors) AS doctors,
                (SELECT COUNT(DISTINCT spid) FROM doctors) AS specialties,
                (SELECT COUNT(*) FROM doctors d JOIN specialties s ON s.spid = d.spid
                  WHERE s.sname LIKE '%Cardiologist%') AS cardiologists,
                (SELECT COUNT(*) FROM schedule WHERE sdate >= '$today') AS sessions"
    )->fetch_assoc();
    $con->close();

    $doctorCount    = (int)$stats['doctors'];
    $specialtyCount = (int)$stats['specialties'];
    $cardioCount    = (int)$stats['cardiologists'];
    $sessionCount   = (int)$stats['sessions'];

    /** "1 doctor" / "3 doctors"; pass $many for irregular plurals. */
    function plural($n, $one, $many = null)
    {
        return $n . ' ' . ($n === 1 ? $one : ($many !== null ? $many : $one . 's'));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DaaktarSahab - Heart checks and doctor appointments</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/home.css">
    <style>
        /* Doctors and admins sign in from here too. */
        .staff-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 48px;
            padding: 22px 24px;
            background: #fff;
            border: 1px solid #e3ebf0;
            border-radius: 16px;
        }

        .staff-strip h2 {
            margin: 0 0 4px;
            color: #12304a;
            font-size: 20px;
        }

        .staff-strip p {
            margin: 0;
            color: #3d566b;
            font-size: 16px;
        }

        .staff-strip .hero-actions {
            margin-top: 0;
        }
    </style>
</head>
<body class="home-page">

    <?php include("mainHeader.html"); ?>

    <main>
        <!-- Hero -->
        <section class="hero" aria-label="Welcome to DaaktarSahab">
            <div class="hero-content">
                <span class="hero-eyebrow">Heart health, made simple</span>
                <h1>Welcome to <span>DaaktarSahab</span></h1>
                <p>Check your heart disease risk in minutes, book a cardiologist, and keep your doctor's advice in one place.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="patient/usersLogin.php?mode=register">Create a free account</a>
                    <a class="btn btn-light" href="patient/usersLogin.php">Sign in</a>
                </div>
            </div>
        </section>

        <!-- What the site offers, floating over the bottom of the hero -->
        <section class="section">
            <div class="container">
                <h2 class="section-title">What you can do</h2>
                <div class="cards">

                    <div class="card">
                        <p class="card-label">Heart check</p>
                        <p class="card-main">Know your risk</p>
                        <p class="card-sub">Answer 13 questions from routine test reports and get a heart disease risk score from 0 to 100, with the factors behind it.</p>
                        <a class="card-link" href="patient/usersLogin.php?mode=register">Start a heart check</a>
                    </div>

                    <div class="card">
                        <p class="card-label">Doctors</p>
                        <?php if ($doctorCount > 0): ?>
                            <p class="card-main"><?= plural($doctorCount, 'doctor') ?>, <?= plural($specialtyCount, 'specialty', 'specialties') ?></p>
                            <p class="card-sub">
                                <?= $sessionCount > 0 ? plural($sessionCount, 'upcoming session') . ' open for booking' : 'New sessions are added regularly' ?><?= $cardioCount > 0 ? ', with ' . plural($cardioCount, 'cardiologist') . ' on hand.' : '.' ?>
                            </p>
                        <?php else: ?>
                            <p class="card-main">Book a specialist</p>
                            <p class="card-sub">See when doctors are available and book a one-hour slot that suits you.</p>
                        <?php endif; ?>
                        <a class="card-link" href="patient/usersLogin.php">Book an appointment</a>
                    </div>

                    <div class="card">
                        <p class="card-label">Follow-up</p>
                        <p class="card-main">Advice you can keep</p>
                        <p class="card-sub">Share a result with your doctor before the visit, read their notes afterwards, and download any result as a PDF.</p>
                        <a class="card-link" href="patient/usersLogin.php?mode=register">Create an account</a>
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
                            <h3>Create your account</h3>
                            <p>It takes a minute and only needs your contact details. <a href="patient/usersLogin.php?mode=register">Sign up</a>.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">2</span>
                        <div>
                            <h3>Take the heart check</h3>
                            <p>Enter values from your blood tests, ECG and stress test to get your risk score.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <div>
                            <h3>See a cardiologist</h3>
                            <p>Book a session, share your result with the doctor and read their advice afterwards.</p>
                        </div>
                    </div>
                </div>

                <div class="staff-strip">
                    <div>
                        <h2>Doctors and staff</h2>
                        <p>Manage your sessions, appointments and consultation notes.</p>
                    </div>
                    <div class="hero-actions">
                        <a class="btn btn-light" href="doctor/doctorLogin.php">Doctor sign in</a>
                        <a class="btn btn-light" href="admin/adminLogin.php">Admin sign in</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include('footer.html') ?>

</body>
</html>
