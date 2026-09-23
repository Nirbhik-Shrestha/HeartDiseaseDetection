<?php
include_once '../connection.php';
include_once '../auth.php';
appSessionStart();

$message = '';
$error = '<label for="promter" class="form-label"></label>';

function loginErrorLabel($text)
{
    return '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">'
        . htmlspecialchars($text) . '</label>';
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
        echo 'Email or Password is empty!';
        exit;
    } elseif (verifyLogin($con, 'patient', $email, $password)) {
        loginAs('patient', $email);
        header('Location: index.php');
        exit();
    } else {
        $error = loginErrorLabel('Wrong credentials: Invalid email or password');
    }
}

if (isset($_POST['register'])) {
    $name     = trim(isset($_POST['pname']) ? $_POST['pname'] : '');
    $email    = trim(isset($_POST['pemail']) ? $_POST['pemail'] : '');
    $rawPass  = isset($_POST['ppassword']) ? $_POST['ppassword'] : '';
    $contact  = trim(isset($_POST['pcontact']) ? $_POST['pcontact'] : '');
    $address  = trim(isset($_POST['paddress']) ? $_POST['paddress'] : '');
    $dob      = isset($_POST['pdob']) ? $_POST['pdob'] : '';

    // The browser checks these too, but that is trivially bypassed, so the
    // same rules are enforced here before anything touches the database.
    $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
    $invalid = null;
    if ($name === '' || !preg_match("/^[a-z ,.'-]+$/i", $name)) {
        $invalid = 'Please enter a valid name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $invalid = 'Please enter a valid email address.';
    } elseif (strlen($rawPass) < 6) {
        $invalid = 'Password must be at least 6 characters.';
    } elseif (!preg_match('/^9[78]\d{8}$/', $contact)) {
        $invalid = 'Phone number not in proper format.';
    } elseif ($address === '') {
        $invalid = 'Please enter your address.';
    } elseif (!$dobDate || $dobDate->format('Y-m-d') !== $dob || $dob > date('Y-m-d')) {
        $invalid = 'Please enter a valid date of birth.';
    }

    $count_email = $count_contact = 0;
    if ($invalid !== null) {
        $error = loginErrorLabel($invalid);
    } else {
        $count_email   = countPatientsWhere($con, 'pemail', $email);
        $count_contact = countPatientsWhere($con, 'pcontact', $contact);
    }

    if ($invalid === null && $count_email == 0 && $count_contact == 0) {
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
        $error = loginErrorLabel('Could not create your account. Please try again.');
    } else {
        if ($count_email > 0) {
            echo '<script>
                alert("Email already exists!");
                window.location.href="usersLogin.php";
            </script>';
        }
        if ($count_contact > 0) {
            echo '<script>
                alert("Phone Number already exists!");
                window.location.href="usersLogin.php";
            </script>';
        }
    }
}
?>

<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Platin', Times, serif;
    }

    .container {
        width: 100%;
        height: 100%;
        background-image: linear-gradient(rgba(0,0,50,0.8),rgba(0,0,50,0.8)), url('../images/bg3.jpg');
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .form-box {
        width: 90%;
        max-width: 450px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        padding: 50px 60px 70px;
        text-align: center;
    }

    .form-box h1 {
        font-size: 30px;
        margin-bottom: 60px;
        color: #3c00a0;
        position: relative;
    }

    .form-box h1::after {
        content: '';
        width: 30px;
        height: 4px;
        border-radius: 3px;
        background: #3c00a0;
        position: absolute;
        bottom: -12px;
        left: 50%;
        transform: translateX(-50%);
    }

    .input-field {
        background: #eaeaea;
        margin: 15px 0;
        border-radius: 3px;
        display: flex;
        align-items: center;
        max-height: 65px;
        transition: max-height 0.5s;
        overflow: hidden;
    }

    input {
        width: 100%;
        background: transparent;
        border: 0;
        outline: 0;
        padding: 18px 15px;
    }

    form p {
        text-align: left;
        font-size: 13px;
        margin-bottom: 20px;
    }

    form p a {
        text-decoration: none;
        color: #3c00a0;
    }

    .btn-field {
        width: 100%;
        display: flex;
        justify-content: space-between;
    }

    .btn-field button {
        flex-basis: 48%;
        background: #3c00a0;
        color: #fff;
        height: 40px;
        border-radius: 20px;
        border: 0;
        outline: 0;
        cursor: pointer;
        transition: background 1s;
    }

    .input-group {
        height: 280px;
        display: flex;
        flex-wrap: wrap;
        flex-direction: column;
        gap: 0px 20px;
    }

    .btn-field button.disable {
        background: #eaeaea;
        color: #555;
    }

    #hiddenField1 { display: none; }
    #hiddenField2 { display: none; }
    #hiddenField3 { display: none; }
    #hiddenField4 { display: none; }
    #forgotPass { display: block; }
</style>
</head>
<body>

<div class="container">
    <div class="form-box" id="formbox">
        <h1 id="title">Sign In</h1>
        <form action="usersLogin.php" method="POST">
            <?php if (!empty($error)) echo $error; ?>
            <div class="input-group">
                <div class="input-field" id="hiddenField1">
                    <input type="text" name="pname" placeholder="Name" id="name">
                </div>

                <div class="input-field">
                    <input type="email" name="pemail" placeholder="Email" required id="email">
                </div>

                <div class="input-field">
                    <input type="password" name="ppassword" placeholder="Password" required>
                </div>

                <div class="input-field" id="hiddenField2">
                    <input type="text" name="pcontact" placeholder="Contact" id="contactNumber">
                </div>

                <div class="input-field" id="hiddenField3">
                    <input type="text" name="paddress" placeholder="Address" id="address">
                </div>

                <div class="input-field" id="hiddenField4">
                    <input type="date" name="pdob" placeholder="Date of Birth">
                </div>
            </div>

            <p id="forgotPass">Forgot Password? <a href='forgotPassword.php'>Click Here!</a></p>

            <div class="btn-field">
                <button type="submit" id="signInBtn" name="login">Sign In</button>
                <button type="button" id="signUpBtn" class="disable">Sign Up</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('contactNumber').addEventListener('input', function (e) 
            {
                const value = e.target.value;
                if (!/^9[78]\d{8}$/.test(value)) 
                {
                    e.target.setCustomValidity('Phone number not in proper format.');
                }
                else 
                {
                    e.target.setCustomValidity('');
                }
            });
    document.getElementById('email').addEventListener('input', function (e) 
            {
                const value = e.target.value;
                if (!/^([a-zA-Z0-9._-]+)@([a-zA-Z0-9.-]+)\.([a-z]{2,20})(\.[a-z]{2,20})?$/.test(value)) 
                {
                    e.target.setCustomValidity('Your email is not in proper format.');
                } 
                else 
                {
                    e.target.setCustomValidity('');
                }
            });

    document.getElementById('name').addEventListener('input', function (e) 
            {
                const value = e.target.value;
                if (!/^[a-z ,.'-]+$/i.test(value)) 
                {
                    e.target.setCustomValidity('Your name is not in proper format.');
                } 
                else 
                {
                    e.target.setCustomValidity('');
                }
            });
    document.getElementById('address').addEventListener('input', function (e) 
            {
                const value = e.target.value;
                if (!/^[a-z ,.'-]+$/i.test(value)) 
                {
                    e.target.setCustomValidity('Your name is not in proper format.');
                } 
                else 
                {
                    e.target.setCustomValidity('');
                }
            });

    let signUpBtn = document.getElementById("signUpBtn");
    let signInBtn = document.getElementById("signInBtn");
    let hiddenField1 = document.getElementById("hiddenField1");
    let hiddenField2 = document.getElementById("hiddenField2");
    let hiddenField3 = document.getElementById("hiddenField3");
    let hiddenField4 = document.getElementById("hiddenField4");
    let title = document.getElementById("title");
    let formbox = document.getElementById("formbox");
    let forgotPass = document.getElementById("forgotPass");

    signUpBtn.onclick = function() {
        hiddenField1.style.display = 'block';
        hiddenField2.style.display = 'block';
        hiddenField3.style.display = 'block';
        hiddenField4.style.display = 'block';
        title.innerHTML = "Sign Up";
        signInBtn.classList.add("disable");
        signUpBtn.classList.remove("disable");
        signUpBtn.type = 'submit';
        signUpBtn.name = 'register';
        formbox.style.maxWidth = 'fit-content';
        forgotPass.style.display = 'none';
    }

    signInBtn.onclick = function() {
        hiddenField1.style.display = 'none';
        hiddenField2.style.display = 'none';
        hiddenField3.style.display = 'none';
        hiddenField4.style.display = 'none';
        title.innerHTML = "Sign In";
        signUpBtn.classList.add("disable");
        signInBtn.classList.remove("disable");
        signUpBtn.type = 'button';
        formbox.style.maxWidth = '450px';
        forgotPass.style.display = 'block';
    }

    // document.getElementById('contactNumber').addEventListener('input', validateContactNumber);
</script>


</body>
</html>
