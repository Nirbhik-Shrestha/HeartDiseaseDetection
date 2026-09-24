<?php
include_once '../connection.php';
include_once '../auth.php';
appSessionStart();

// Already signed in as a patient: nothing to do here.
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && isset($_SESSION['role']) && $_SESSION['role'] === 'patient' && !empty($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$errorText = '';
// Which tab the page opens on, and what to put back in the fields after an error.
$mode = isset($_POST['register']) || (isset($_GET['mode']) && $_GET['mode'] === 'register')
    ? 'register' : 'login';
$old = ['pname' => '', 'pemail' => '', 'pcontact' => '', 'paddress' => '', 'pdob' => ''];
foreach ($old as $key => $unused) {
    $old[$key] = isset($_POST[$key]) ? trim((string)$_POST[$key]) : '';
}

/** Count patients whose $column equals $value (column names are fixed below). */
function countPatientsWhere($con, $column, $value)
{
    $stmt = $con->prepare("SELECT COUNT(*) AS n FROM patients WHERE $column = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $n = (int)$stmt->get_result()->fetch_assoc()['n'];
    $stmt->close();
    return $n;
}

if (isset($_POST['login'])) {
    $email    = isset($_POST['pemail']) ? $_POST['pemail'] : '';
    $password = isset($_POST['ppassword']) ? $_POST['ppassword'] : '';

    if (empty($email) || empty($password)) {
        $errorText = 'Please enter your email and password.';
    } elseif (verifyLogin($con, 'patient', $email, $password)) {
        loginAs('patient', $email);
        header('Location: index.php');
        exit();
    } else {
        $errorText = 'Wrong email or password. Please try again.';
    }
}

if (isset($_POST['register'])) {
    $name     = $old['pname'];
    $email    = $old['pemail'];
    $rawPass  = isset($_POST['ppassword']) ? $_POST['ppassword'] : '';
    $contact  = $old['pcontact'];
    $address  = $old['paddress'];
    $dob      = $old['pdob'];

    // The browser checks these too, but that is trivially bypassed, so the
    // same rules are enforced here before anything touches the database.
    $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
    if ($name === '' || !preg_match("/^[a-z ,.'-]+$/i", $name)) {
        $errorText = 'Please enter a valid name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorText = 'Please enter a valid email address.';
    } elseif (strlen($rawPass) < 6) {
        $errorText = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/^9[78]\d{8}$/', $contact)) {
        $errorText = 'Phone number must be a 10-digit mobile number starting with 97 or 98.';
    } elseif ($address === '') {
        $errorText = 'Please enter your address.';
    } elseif (!$dobDate || $dobDate->format('Y-m-d') !== $dob || $dob > date('Y-m-d')) {
        $errorText = 'Please enter a valid date of birth.';
    } elseif (countPatientsWhere($con, 'pemail', $email) > 0) {
        $errorText = 'An account with this email already exists. Try signing in instead.';
    } elseif (countPatientsWhere($con, 'pcontact', $contact) > 0) {
        $errorText = 'An account with this phone number already exists.';
    } else {
        $password = password_hash($rawPass, PASSWORD_BCRYPT);
        $stmt = $con->prepare("INSERT INTO patients (pemail, ppassword, pname, pcontact, paddress, pdob) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $email, $password, $name, $contact, $address, $dob);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            loginAs('patient', $email);
            header("Location: index.php");
            exit();
        }
        $errorText = 'Could not create your account. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Sign In - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        .auth-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            margin: 0 0 24px;
            padding: 4px;
            border-radius: 12px;
            background: #eef3f6;
        }

        .auth-tabs button {
            padding: 10px 12px;
            border: 0;
            border-radius: 9px;
            background: none;
            color: #3d566b;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .auth-tabs button[aria-selected="true"] {
            background: #fff;
            color: #12304a;
            box-shadow: 0 1px 3px rgba(18, 48, 74, 0.12);
        }

        /* Sign-up-only fields are hidden while signing in. */
        #authForm:not(.is-register) .register-only {
            display: none;
        }

        #authForm.is-register .login-only {
            display: none;
        }

        .auth-links {
            margin: -6px 0 18px;
            font-size: 15px;
        }
    </style>
</head>
<body class="site-page">

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-visual">
            <div>
                <a class="auth-visual__logo" href="../index.php"><img src="../images/logoo5.png" alt="DaaktarSahab home"></a>
                <h2>Your heart, in good hands</h2>
                <p>Check your heart risk, book a cardiologist and read your doctor's advice, all in one place.</p>
            </div>
        </div>

        <div class="auth-form">
            <h1 id="authTitle"><?= $mode === 'register' ? 'Create your account' : 'Welcome back' ?></h1>
            <p class="auth-sub" id="authSub"><?= $mode === 'register' ? 'It takes a minute. You can take a heart check straight after.' : 'Sign in to your patient account.' ?></p>

            <div class="auth-tabs" role="tablist">
                <button type="button" role="tab" data-mode="login" aria-selected="<?= $mode === 'login' ? 'true' : 'false' ?>">Sign in</button>
                <button type="button" role="tab" data-mode="register" aria-selected="<?= $mode === 'register' ? 'true' : 'false' ?>">Create account</button>
            </div>

            <?php if ($errorText !== ''): ?>
                <div class="notice notice-bad" role="alert"><?= htmlspecialchars($errorText) ?></div>
            <?php endif; ?>

            <form action="usersLogin.php" method="POST" id="authForm" class="<?= $mode === 'register' ? 'is-register' : '' ?>" novalidate>
                <div class="field register-only">
                    <label for="name">Full name</label>
                    <input type="text" name="pname" id="name" value="<?= htmlspecialchars($old['pname']) ?>" autocomplete="name">
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="pemail" id="email" value="<?= htmlspecialchars($old['pemail']) ?>" required autocomplete="email">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" name="ppassword" id="password" required autocomplete="current-password">
                    <p class="hint register-only">At least 6 characters.</p>
                </div>

                <p class="auth-links login-only"><a href="forgotPassword.php">Forgot your password?</a></p>

                <div class="form-grid register-only">
                    <div class="field">
                        <label for="contactNumber">Mobile number</label>
                        <input type="text" name="pcontact" id="contactNumber" value="<?= htmlspecialchars($old['pcontact']) ?>" inputmode="numeric" placeholder="98XXXXXXXX" autocomplete="tel">
                    </div>
                    <div class="field">
                        <label for="dob">Date of birth</label>
                        <input type="date" name="pdob" id="dob" value="<?= htmlspecialchars($old['pdob']) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="field register-only">
                    <label for="address">Address</label>
                    <input type="text" name="paddress" id="address" value="<?= htmlspecialchars($old['paddress']) ?>" autocomplete="street-address">
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="submitBtn"
                        name="<?= $mode === 'register' ? 'register' : 'login' ?>">
                    <?= $mode === 'register' ? 'Create account' : 'Sign in' ?>
                </button>
            </form>

            <a class="auth-back" href="../index.php">&larr; Back to DaaktarSahab</a>
        </div>
    </div>
</main>

<script>
    (function () {
        var form = document.getElementById('authForm');
        var submit = document.getElementById('submitBtn');
        var title = document.getElementById('authTitle');
        var sub = document.getElementById('authSub');
        var tabs = document.querySelectorAll('.auth-tabs button');
        var registerFields = ['name', 'contactNumber', 'dob', 'address'].map(function (id) {
            return document.getElementById(id);
        });

        // Switch between signing in and creating an account. The submit
        // button's name tells usersLogin.php which one was sent.
        function setMode(mode) {
            var register = mode === 'register';
            form.classList.toggle('is-register', register);
            submit.name = register ? 'register' : 'login';
            submit.textContent = register ? 'Create account' : 'Sign in';
            title.textContent = register ? 'Create your account' : 'Welcome back';
            sub.textContent = register ? 'It takes a minute. You can take a heart check straight after.' : 'Sign in to your patient account.';
            document.getElementById('password').autocomplete = register ? 'new-password' : 'current-password';
            registerFields.forEach(function (f) { f.required = register; });
            tabs.forEach(function (t) { t.setAttribute('aria-selected', t.dataset.mode === mode ? 'true' : 'false'); });
        }

        tabs.forEach(function (t) {
            t.addEventListener('click', function () { setMode(t.dataset.mode); });
        });
        setMode(form.classList.contains('is-register') ? 'register' : 'login');

        // Browser-side checks, mirroring the rules usersLogin.php enforces.
        var rules = {
            contactNumber: [/^9[78]\d{8}$/, 'Phone number must be a 10-digit mobile number starting with 97 or 98.'],
            email: [/^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'Please enter a valid email address.'],
            name: [/^[a-z ,.'-]+$/i, "Please use letters, spaces and . , ' - only."]
        };
        Object.keys(rules).forEach(function (id) {
            var input = document.getElementById(id);
            input.addEventListener('input', function () {
                input.setCustomValidity(input.value === '' || rules[id][0].test(input.value) ? '' : rules[id][1]);
            });
        });

        // novalidate lets hidden sign-up fields be skipped; check the visible ones here.
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
            }
        });
    })();
</script>
</body>
</html>
