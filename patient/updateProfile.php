<?php
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid    = (int)$userfetch["pid"];
    $username  = $userfetch["pname"];

    /** Is $value already used in $column by a different patient? */
    function takenByOther($con, $column, $value, $userid)
    {
        $stmt = $con->prepare("SELECT COUNT(*) AS n FROM patients WHERE $column = ? AND pid <> ?");
        $stmt->bind_param("si", $value, $userid);
        $stmt->execute();
        $n = (int)$stmt->get_result()->fetch_assoc()['n'];
        $stmt->close();
        return $n > 0;
    }

    $errors = [];
    $values = [
        'pname'    => $userfetch['pname'],
        'pemail'   => $userfetch['pemail'],
        'pcontact' => $userfetch['pcontact'],
        'paddress' => $userfetch['paddress'],
        'pdob'     => $userfetch['pdob'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($values as $key => $current) {
            $values[$key] = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
        }

        // Same rules as sign-up (usersLogin.php).
        $dob = DateTime::createFromFormat('Y-m-d', $values['pdob']);
        if ($values['pname'] === '' || !preg_match("/^[a-z ,.'-]+$/i", $values['pname'])) {
            $errors[] = 'Please enter a valid name.';
        }
        if (!filter_var($values['pemail'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (takenByOther($con, 'pemail', $values['pemail'], $userid)) {
            $errors[] = 'That email address is already used by another account.';
        }
        if (!preg_match('/^9[78]\d{8}$/', $values['pcontact'])) {
            $errors[] = 'Phone number must be a 10-digit mobile number starting with 97 or 98.';
        } elseif (takenByOther($con, 'pcontact', $values['pcontact'], $userid)) {
            $errors[] = 'That phone number is already used by another account.';
        }
        if ($values['paddress'] === '') {
            $errors[] = 'Please enter your address.';
        }
        if (!$dob || $dob->format('Y-m-d') !== $values['pdob'] || $values['pdob'] > date('Y-m-d')) {
            $errors[] = 'Please enter a valid date of birth.';
        }

        if (!$errors) {
            $stmt = $con->prepare("UPDATE patients SET pname = ?, pemail = ?, pcontact = ?, paddress = ?, pdob = ? WHERE pid = ?");
            $stmt->bind_param("sssssi", $values['pname'], $values['pemail'], $values['pcontact'], $values['paddress'], $values['pdob'], $userid);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                // The session is keyed by email, so follow the change or the
                // next page would no longer find this account.
                $_SESSION['user'] = $values['pemail'];
                $_SESSION['profile_saved'] = true;
                $con->close();
                header("Location: updateProfile.php");
                exit();
            }
            $errors[] = 'Your details could not be saved. Please try again.';
        }
    }

    $saved = !empty($_SESSION['profile_saved']);
    unset($_SESSION['profile_saved']);

    $stmt = $con->prepare("SELECT COUNT(*) AS n FROM patient_data WHERE pid = ?");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $checkCount = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
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
            color: #6b8193;
            font-size: 15px;
        }

        .quick-links {
            display: grid;
            gap: 8px;
            margin-top: 18px;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">My profile</span>
        <h1>Your details</h1>
        <p>Keep your contact details up to date so doctors can reach you about your appointments.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($saved): ?>
        <div class="notice notice-ok">Your details were saved.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="notice notice-bad" role="alert">
            <strong>Your details were not saved:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="page-grid">
        <section class="panel">
            <div class="panel__head">
                <h2 class="panel__title">Personal information</h2>
            </div>

            <form action="updateProfile.php" method="POST" id="profileForm">
                <div class="form-grid">
                    <div class="field">
                        <label for="pname">Full name</label>
                        <input type="text" id="pname" name="pname" value="<?= htmlspecialchars($values['pname']) ?>" required>
                    </div>
                    <div class="field">
                        <label for="pemail">Email</label>
                        <input type="email" id="pemail" name="pemail" value="<?= htmlspecialchars($values['pemail']) ?>" required>
                        <p class="hint">You sign in with this address.</p>
                    </div>
                    <div class="field">
                        <label for="pcontact">Mobile number</label>
                        <input type="text" id="pcontact" name="pcontact" value="<?= htmlspecialchars($values['pcontact']) ?>" required inputmode="numeric" placeholder="98XXXXXXXX">
                        <p class="hint">10 digits, starting with 97 or 98.</p>
                    </div>
                    <div class="field">
                        <label for="pdob">Date of birth</label>
                        <input type="date" id="pdob" name="pdob" value="<?= htmlspecialchars($values['pdob']) ?>" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="field field--wide">
                        <label for="paddress">Address</label>
                        <input type="text" id="paddress" name="paddress" value="<?= htmlspecialchars($values['paddress']) ?>" required>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <a href="index.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </section>

        <aside class="panel">
            <div class="profile-summary">
                <img src="../images/user.png" alt="">
                <div>
                    <h2><?= htmlspecialchars($userfetch['pname']) ?></h2>
                    <p>Patient no. <?= $userid ?></p>
                </div>
            </div>
            <ul class="detail-list">
                <li><span>Heart checks taken</span><strong><?= $checkCount ?></strong></li>
            </ul>
            <div class="quick-links">
                <a href="appointment.php" class="btn btn-light btn-block">My appointments</a>
                <a href="viewHistory.php" class="btn btn-light btn-block">My prediction history</a>
            </div>
        </aside>
    </div>
</main>

<?php include('../footer.html'); ?>

<script>
    // Mirror the server-side rules so mistakes show before submitting.
    (function () {
        var rules = {
            pcontact: [/^9[78]\d{8}$/, 'Phone number must be a 10-digit mobile number starting with 97 or 98.'],
            pname: [/^[a-z ,.'-]+$/i, 'Please use letters, spaces and . , \' - only.']
        };
        Object.keys(rules).forEach(function (id) {
            var input = document.getElementById(id);
            input.addEventListener('input', function () {
                input.setCustomValidity(rules[id][0].test(input.value) ? '' : rules[id][1]);
            });
        });
    })();
</script>
</body>
</html>
