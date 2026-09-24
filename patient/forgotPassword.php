<?php
session_start();
include_once '../connection.php';

// Add reset columns if they don't exist (compatible with all MySQL versions)
$cols = $con->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients' AND COLUMN_NAME IN ('reset_token','token_expiry')");
$existing = [];
while ($c = $cols->fetch_row()) $existing[] = $c[0];
if (!in_array('reset_token',  $existing)) $con->query("ALTER TABLE `patients` ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL");
if (!in_array('token_expiry', $existing)) $con->query("ALTER TABLE `patients` ADD COLUMN `token_expiry` DATETIME NULL DEFAULT NULL");

$step    = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$token   = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$msgType = '';

// ── STEP 1: User submits email ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_token'])) {
    $email = trim($_POST['pemail']);
    $stmt  = $con->prepare("SELECT pid FROM patients WHERE pemail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $rawToken = bin2hex(random_bytes(32));
        $expiry   = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $upd = $con->prepare("UPDATE patients SET reset_token = ?, token_expiry = ? WHERE pemail = ?");
        $upd->bind_param("sss", $rawToken, $expiry, $email);
        $upd->execute();
        $upd->close();

        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?step=2&token=" . $rawToken;
        $message   = "A password-reset link has been generated.<br><br>
                      <strong>Reset Link (valid 30 min):</strong><br>
                      <a href=\"$resetLink\" class=\"reset-link\">$resetLink</a>
                      <br><br><em>In a production system this link would be e-mailed to you.</em>";
        $msgType   = 'success';
    } else {
        $message = "No patient account found with that email address.";
        $msgType = 'error';
    }
    $stmt->close();
}

// ── STEP 2: User submits new password ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token   = trim($_POST['token']);
    $newPass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($newPass !== $confirm) {
        $message = "Passwords do not match.";
        $msgType = 'error';
        $step    = 2;
    } elseif (strlen($newPass) < 6) {
        $message = "Password must be at least 6 characters.";
        $msgType = 'error';
        $step    = 2;
    } else {
        $now  = date('Y-m-d H:i:s');
        $stmt = $con->prepare("SELECT pid FROM patients WHERE reset_token = ? AND token_expiry > ?");
        $stmt->bind_param("ss", $token, $now);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $con->prepare("UPDATE patients SET ppassword = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
            $upd->bind_param("ss", $hashed, $token);
            $upd->execute();
            $upd->close();
            $message = "Your password was updated. You can sign in with it now.";
            $msgType = 'success';
            $step    = 3;
        } else {
            $message = "This reset link is invalid or has expired.";
            $msgType = 'error';
        }
        $stmt->close();
    }
}

// Validate token when arriving at step 2 via GET
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $now  = date('Y-m-d H:i:s');
    $stmt = $con->prepare("SELECT pid FROM patients WHERE reset_token = ? AND token_expiry > ?");
    $stmt->bind_param("ss", $token, $now);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows !== 1) {
        $message = "This reset link is invalid or has expired. Please request a new one.";
        $msgType = 'error';
        $step    = 1;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .reset-steps {
            display: flex;
            gap: 8px;
            margin: 0 0 22px;
            padding: 0;
            list-style: none;
        }

        .reset-steps li {
            flex: 1;
            padding-top: 10px;
            border-top: 3px solid #e3ebf0;
            color: #8aa0b2;
            font-size: 14px;
            font-weight: 600;
        }

        .reset-steps li.is-done,
        .reset-steps li.is-current {
            border-top-color: #00a99d;
            color: #00786f;
        }

        .reset-link {
            word-break: break-all;
        }

        .done-icon {
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            margin: 0 0 16px;
            border-radius: 50%;
            background: #e7f7ee;
            box-shadow: 0 0 0 6px #f3fbf6;
            color: #1f7a4a;
            font-size: 26px;
            font-weight: 700;
        }
    </style>
</head>
<body class="site-page">

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-visual">
            <div>
                <a class="auth-visual__logo" href="../index.php"><img src="../images/logoo5.png" alt="DaaktarSahab home"></a>
                <h2>Locked out?</h2>
                <p>Reset your password in two quick steps and get back to your heart checks and appointments.</p>
            </div>
        </div>

        <div class="auth-form">
            <h1>Reset your password</h1>
            <p class="auth-sub">Patient account recovery</p>

            <ol class="reset-steps">
                <?php foreach ([1 => 'Your email', 2 => 'New password', 3 => 'Done'] as $n => $label): ?>
                    <li class="<?= $step > $n ? 'is-done' : ($step === $n ? 'is-current' : '') ?>"><?= $label ?></li>
                <?php endforeach; ?>
            </ol>

            <?php if ($message): ?>
                <div class="notice <?= $msgType === 'success' ? 'notice-ok' : 'notice-bad' ?>"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <form method="POST" action="forgotPassword.php">
                    <div class="field">
                        <label for="pemail">Your email address</label>
                        <input type="email" id="pemail" name="pemail" placeholder="patient@example.com" required autocomplete="email">
                    </div>
                    <button type="submit" name="send_token" class="btn btn-primary btn-block">Send reset link</button>
                </form>

            <?php elseif ($step === 2): ?>
                <form method="POST" action="forgotPassword.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your new password" required minlength="6" autocomplete="new-password">
                    </div>
                    <button type="submit" name="reset_password" class="btn btn-primary btn-block">Reset password</button>
                </form>

            <?php elseif ($step === 3): ?>
                <div class="done-icon" aria-hidden="true">&#10003;</div>
                <a href="usersLogin.php" class="btn btn-primary btn-block">Sign in</a>
            <?php endif; ?>

            <a href="usersLogin.php" class="auth-back">&larr; Back to sign in</a>
        </div>
    </div>
</main>
</body>
</html>
