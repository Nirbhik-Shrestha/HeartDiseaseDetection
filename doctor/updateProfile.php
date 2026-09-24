<?php
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'doctor');
    $useremail = $userfetch["demail"];
    $userid    = (int)$userfetch["did"];
    $username  = $userfetch["dname"];

    $stmt = $con->prepare("SELECT sname FROM specialties WHERE spid = ?");
    $spid = (int)$userfetch['spid'];
    $stmt->bind_param("i", $spid);
    $stmt->execute();
    $spec = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $specialty = $spec ? $spec['sname'] : '';

    // Doctors can change their own password; their other details are
    // managed by the admin (admin/doctors.php).
    $errors = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current = isset($_POST['current_password']) ? (string)$_POST['current_password'] : '';
        $new     = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';
        $confirm = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';

        if (!password_verify($current, $userfetch['dpassword'])) {
            $errors[] = 'Your current password is not correct.';
        }
        if (strlen($new) < 6) {
            $errors[] = 'Your new password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        }

        if (!$errors) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $con->prepare("UPDATE doctors SET dpassword = ? WHERE did = ?");
            $stmt->bind_param("si", $hash, $userid);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                $_SESSION['password_changed'] = true;
                $con->close();
                header("Location: updateProfile.php");
                exit();
            }
            $errors[] = 'Your password could not be changed. Please try again.';
        }
    }

    $changed = !empty($_SESSION['password_changed']);
    unset($_SESSION['password_changed']);
    $con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .profile-summary {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 0 0 18px;
        }

        .profile-summary img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: 3px solid #e6f6f4;
            background: #f5f8fa;
        }

        .profile-summary h2 {
            margin: 0;
            color: #12304a;
            font-size: 20px;
        }

        .profile-summary p {
            margin: 2px 0 0;
        }
    </style>
</head>
<body class="site-page">

<?php include("../doctorHeader.html"); ?>

<section class="page-hero page-hero--doctor">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">My profile</span>
        <h1>Your details</h1>
        <p>How patients see you on DaaktarSahab, and where to change your password.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($changed): ?>
        <div class="notice notice-ok">Your password was changed. Use the new one next time you sign in.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice notice-bad" role="alert">
            <strong>Your password was not changed:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <section class="panel">
            <div class="profile-summary">
                <img src="../images/user.png" alt="">
                <div>
                    <h2>Dr. <?= htmlspecialchars($username) ?></h2>
                    <p><span class="badge badge-info"><?= htmlspecialchars($specialty) ?></span></p>
                </div>
            </div>
            <ul class="detail-list">
                <li><span>Email</span><strong><?= htmlspecialchars($useremail) ?></strong></li>
                <li><span>Phone</span><strong><?= htmlspecialchars($userfetch['dcontact']) ?></strong></li>
                <li><span>Practice address</span><strong><?= htmlspecialchars($userfetch['daddress']) ?></strong></li>
                <li><span>NMC registration</span><strong><?= htmlspecialchars($userfetch['nmc']) ?></strong></li>
            </ul>
            <div class="notice notice-info" style="margin: 18px 0 0">
                To change these details, please ask the DaaktarSahab admin team.
            </div>
        </section>

        <aside class="panel">
            <p class="panel__label">Change password</p>
            <form action="updateProfile.php" method="POST">
                <div class="field">
                    <label for="current_password">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                </div>
                <div class="field">
                    <label for="new_password">New password</label>
                    <input type="password" id="new_password" name="new_password" minlength="6" required autocomplete="new-password">
                    <p class="hint">At least 6 characters.</p>
                </div>
                <div class="field">
                    <label for="confirm_password">Confirm new password</label>
                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Change password</button>
            </form>
        </aside>
    </div>
</main>

<?php include('../footer.html'); ?>
</body>
</html>
