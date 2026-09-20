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
            $message = "Password updated successfully! <a href='usersLogin.php'>Sign in here.</a>";
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
    <title>Patient – Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Platin:wght@400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Platin', 'Inter', serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: linear-gradient(rgba(0,0,50,0.82), rgba(0,0,50,0.82)), url('../images/bg3.jpg');
            background-size: cover;
            background-position: center;
        }

        .card {
            background: #fff;
            border-radius: 4px;
            width: 420px;
            max-width: 95vw;
            padding: 50px 60px 60px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: fadeIn 0.4s ease both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        h1 {
            font-size: 28px;
            color: #3c00a0;
            margin-bottom: 8px;
            position: relative;
        }

        h1::after {
            content: '';
            width: 30px;
            height: 4px;
            border-radius: 3px;
            background: #3c00a0;
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        .subtitle {
            color: #888;
            font-size: 13px;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ddd;
            transition: background 0.3s;
        }
        .step-dot.active { background: #3c00a0; }

        label {
            display: block;
            text-align: left;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 5px;
            font-family: 'Inter', sans-serif;
        }

        .input-field {
            background: #eaeaea;
            border-radius: 3px;
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .input-field input {
            width: 100%;
            background: transparent;
            border: 0;
            outline: 0;
            padding: 18px 15px;
            font-size: 14px;
            font-family: inherit;
        }

        .btn-submit {
            width: 100%;
            background: #3c00a0;
            color: #fff;
            height: 45px;
            border-radius: 22px;
            border: 0;
            cursor: pointer;
            font-size: 15px;
            font-family: inherit;
            transition: background 0.3s;
            margin-top: 8px;
        }
        .btn-submit:hover { background: #5000d6; }

        .msg {
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 18px;
            font-size: 13px;
            line-height: 1.5;
            font-family: 'Inter', sans-serif;
            text-align: left;
        }
        .msg.success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; }
        .msg.error   { background: #ffebee; border: 1px solid #ef9a9a; color: #c62828; }

        .back-link {
            display: block;
            text-align: left;
            margin-top: 20px;
            color: #3c00a0;
            font-size: 13px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }
        .back-link:hover { text-decoration: underline; }
        .reset-link { color: #3c00a0; word-break: break-all; }
        .success-icon { font-size: 48px; margin: 10px 0 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Forgot Password</h1>
    <p class="subtitle">Patient Account Recovery</p>

    <div class="steps">
        <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
        <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>"></div>
    </div>

    <?php if ($message): ?>
        <div class="msg <?= $msgType ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <form method="POST" action="forgotPassword.php">
            <label for="pemail">Your Email Address</label>
            <div class="input-field">
                <input type="email" id="pemail" name="pemail" placeholder="patient@example.com" required>
            </div>
            <button type="submit" name="send_token" class="btn-submit">Generate Reset Link</button>
        </form>

    <?php elseif ($step === 2): ?>
        <form method="POST" action="forgotPassword.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <label for="new_password">New Password</label>
            <div class="input-field">
                <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required minlength="6">
            </div>
            <label for="confirm_password">Confirm Password</label>
            <div class="input-field">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your new password" required minlength="6">
            </div>
            <button type="submit" name="reset_password" class="btn-submit">Reset Password</button>
        </form>

    <?php elseif ($step === 3): ?>
        <div class="success-icon">✅</div>
    <?php endif; ?>

    <a href="usersLogin.php" class="back-link">← Back to Sign In</a>
</div>
</body>
</html>
