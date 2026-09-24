<?php
include_once '../connection.php';
include_once '../auth.php';
appSessionStart();

// Already signed in as a doctor: straight to the dashboard.
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_SESSION['role']) && $_SESSION['role'] === 'doctor' && !empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$errorText = '';
$email = '';

if (isset($_POST['login'])) {
    $email    = isset($_POST['demail']) ? trim((string)$_POST['demail']) : '';
    $password = isset($_POST['dpassword']) ? (string)$_POST['dpassword'] : '';

    if ($email === '' || $password === '') {
        $errorText = 'Please enter your email and password.';
    } elseif (verifyLogin($con, 'doctor', $email, $password)) {
        loginAs('doctor', $email);
        header('Location: index.php');
        exit();
    } else {
        $errorText = 'Wrong email or password. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Sign In - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        /* The doctor photo instead of the patients' heart image. */
        .auth-visual.is-doctor {
            background: #9fd9d8 url("../images/drbanner.jpg") 82% top / cover no-repeat;
        }

        .auth-visual.is-doctor::before {
            background: linear-gradient(180deg, rgba(234, 247, 251, 0.95) 0%, rgba(234, 247, 251, 0.7) 45%, rgba(234, 247, 251, 0) 80%);
        }
    </style>
</head>
<body class="site-page">

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-visual is-doctor">
            <div>
                <a class="auth-visual__logo" href="../index.php"><img src="../images/logoo5.png" alt="DaaktarSahab home"></a>
                <h2>For doctors</h2>
                <p>Manage your sessions, review heart checks your patients share and record each visit.</p>
            </div>
        </div>

        <div class="auth-form">
            <h1>Welcome, doctor</h1>
            <p class="auth-sub">Sign in to your doctor account.</p>

            <?php if ($errorText !== ''): ?>
                <div class="notice notice-bad" role="alert"><?= htmlspecialchars($errorText) ?></div>
            <?php endif; ?>

            <form action="doctorLogin.php" method="POST">
                <div class="field">
                    <label for="demail">Email</label>
                    <input type="email" id="demail" name="demail" value="<?= htmlspecialchars($email) ?>" required autocomplete="email">
                </div>
                <div class="field">
                    <label for="dpassword">Password</label>
                    <input type="password" id="dpassword" name="dpassword" required autocomplete="current-password">
                </div>
                <p style="margin: -6px 0 18px; font-size: 15px"><a href="forgotPassword.php">Forgot your password?</a></p>
                <button type="submit" name="login" class="btn btn-primary btn-block">Sign in</button>
            </form>

            <a class="auth-back" href="../index.php">&larr; Back to DaaktarSahab</a>
        </div>
    </div>
</main>
</body>
</html>
