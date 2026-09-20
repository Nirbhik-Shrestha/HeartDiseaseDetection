<?php
session_start();
include_once '../connection.php';

// Add reset columns if they don't exist (compatible with all MySQL versions)
$cols = $con->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND COLUMN_NAME IN ('reset_token','token_expiry')");
$existing = [];
while ($c = $cols->fetch_row()) $existing[] = $c[0];
if (!in_array('reset_token',  $existing)) $con->query("ALTER TABLE `admin` ADD COLUMN `reset_token` VARCHAR(64) NULL DEFAULT NULL");
if (!in_array('token_expiry', $existing)) $con->query("ALTER TABLE `admin` ADD COLUMN `token_expiry` DATETIME NULL DEFAULT NULL");

$step    = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$token   = isset($_GET['token']) ? trim($_GET['token']) : '';
$message = '';
$msgType = '';

// ── STEP 1: User submits email ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_token'])) {
    $email = trim($_POST['aemail']);
    $stmt  = $con->prepare("SELECT aid FROM `admin` WHERE aemail = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $rawToken  = bin2hex(random_bytes(32));         // 64-char hex token
        $expiry    = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $upd = $con->prepare("UPDATE `admin` SET reset_token = ?, token_expiry = ? WHERE aemail = ?");
        $upd->bind_param("sss", $rawToken, $expiry, $email);
        $upd->execute();
        $upd->close();

        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?step=2&token=" . $rawToken;
        $message   = "A password-reset link has been generated. <br><br>
                      <strong>Reset Link (valid 30 min):</strong><br>
                      <a href=\"$resetLink\" class=\"reset-link\">$resetLink</a>
                      <br><br><em>In a production system this link would be e-mailed to you.</em>";
        $msgType   = 'success';
    } else {
        $message = "No account found with that email address.";
        $msgType = 'error';
    }
    $stmt->close();
}

// ── STEP 2: User submits new password ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token    = trim($_POST['token']);
    $newPass  = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

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
        $stmt = $con->prepare("SELECT aid FROM `admin` WHERE reset_token = ? AND token_expiry > ?");
        $stmt->bind_param("ss", $token, $now);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $hashed = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $con->prepare("UPDATE `admin` SET apassword = ?, reset_token = NULL, token_expiry = NULL WHERE reset_token = ?");
            $upd->bind_param("ss", $hashed, $token);
            $upd->execute();
            $upd->close();
            $message = "Password updated successfully! <a href='adminLogin.php'>Sign in here.</a>";
            $msgType = 'success';
            $step    = 3; // done
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
    $stmt = $con->prepare("SELECT aid FROM `admin` WHERE reset_token = ? AND token_expiry > ?");
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
    <title>Admin – Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0d1117 url("../images/bg.jpg") no-repeat center center / cover;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(2px);
        }

        .card {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            width: 420px;
            max-width: 95vw;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
            animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            background: linear-gradient(135deg, #0f6367 0%, #1aa6ac 100%);
            padding: 28px 30px 24px;
            text-align: center;
        }

        .card-header .icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 24px;
        }

        .card-header h1 {
            color: #fff;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .card-header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
            margin-top: 5px;
        }

        .card-body { padding: 28px 30px 32px; }

        /* Steps indicator */
        .steps {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .step-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            transition: background 0.3s;
        }
        .step-dot.active { background: #1aa6ac; }

        label {
            display: block;
            color: rgba(255,255,255,0.75);
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
            margin-bottom: 18px;
        }

        .input-wrap input {
            width: 100%;
            padding: 13px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 10px;
            color: #fff;
            font-size: 0.95rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.25s, background 0.25s;
        }

        .input-wrap input::placeholder { color: rgba(255,255,255,0.35); }
        .input-wrap input:focus {
            border-color: #1aa6ac;
            background: rgba(26,166,172,0.1);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0f6367, #1aa6ac);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            margin-top: 4px;
        }

        .btn-primary:hover  { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        .msg {
            border-radius: 10px;
            padding: 13px 16px;
            margin-bottom: 20px;
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .msg.success {
            background: rgba(26,166,172,0.15);
            border: 1px solid rgba(26,166,172,0.4);
            color: #6ef0f5;
        }
        .msg.error {
            background: rgba(255,62,62,0.12);
            border: 1px solid rgba(255,62,62,0.35);
            color: #ff8080;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover { color: #1aa6ac; }

        .reset-link {
            color: #1aa6ac;
            word-break: break-all;
        }

        .success-icon {
            font-size: 48px;
            text-align: center;
            margin: 10px 0 16px;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="icon">🔐</div>
        <h1>Forgot Password</h1>
        <p>Admin Account Recovery</p>
    </div>
    <div class="card-body">

        <!-- Step indicator -->
        <div class="steps">
            <div class="step-dot <?= $step >= 1 ? 'active' : '' ?>"></div>
            <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
            <div class="step-dot <?= $step >= 3 ? 'active' : '' ?>"></div>
        </div>

        <?php if ($message): ?>
            <div class="msg <?= $msgType ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Step 1: Enter email -->
            <form method="POST" action="forgotPassword.php">
                <div class="input-wrap">
                    <label for="aemail">Admin Email Address</label>
                    <input type="email" id="aemail" name="aemail" placeholder="admin@example.com" required>
                </div>
                <button type="submit" name="send_token" class="btn-primary">Generate Reset Link</button>
            </form>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Set new password -->
            <form method="POST" action="forgotPassword.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="input-wrap">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required minlength="6">
                </div>
                <div class="input-wrap">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your new password" required minlength="6">
                </div>
                <button type="submit" name="reset_password" class="btn-primary">Reset Password</button>
            </form>

        <?php elseif ($step === 3): ?>
            <!-- Step 3: Done -->
            <div class="success-icon">✅</div>
        <?php endif; ?>

        <a href="adminLogin.php" class="back-link">← Back to Sign In</a>
    </div>
</div>
</body>
</html>
