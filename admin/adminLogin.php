<?php
session_start();
	include_once '../connection.php';

	$message = '';

	// var_dump($_SESSION);


	if(($_POST)){

        $email    = $_POST['aemail'];
        $password = $_POST['apassword'];
        
        $error = '<label for="promter" class="form-label"></label>';

        // Fetch row by email only, then verify hash
        $result = $con->query("SELECT * FROM `admin` WHERE aemail='$email'");
        if ($result->num_rows == 1) {
			$row = $result->fetch_assoc();
			if (password_verify($password, $row['apassword'])) {
				$_SESSION['user'] = $email;
				header('location: index.php');
				exit();
			} else {
				$error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';
			}
		} else {
			$error = '<label for="promter" class="form-label" style="color:rgb(255, 62, 62);text-align:center;">Wrong credentials: Invalid email or password</label>';
		}
	} else {
        $error = '<label for="promter" class="form-label">&nbsp;</label>';
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f5f7 url("../images/bg.jpg") no-repeat center center/cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            position: relative;
        }

        .home-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #36A9AE;
            border: none;
            border-radius: 4px;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
        }

        .home-button:hover {
            background-color: #2A8387;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 350px;
            overflow: hidden;
        }

        button, input[type="email"], input[type="password"] {
            width: 90%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 6px;
            margin-bottom: 16px;
            display: block;
            box-sizing: border-box;
        }

        .form-container {
            padding: 20px;
            text-align: center;
        }

        .button-25 {
            background-color: #36A9AE;
            color: white;
            border: none;
            cursor: pointer;
            width: 150px;
            padding: 10px;
            border-radius: 10px;
        }

        .button-25:hover {
            background-color: #2A8387;
        }

        .overlay {
            background: linear-gradient(to right, #0f6367, #1aa6ac);
            color: white;
            padding: 15px;
            text-align: center;
        }

        .overlay p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <a href="../index.php" class="home-button">HOME</a>
    <div class="container">
        <div class="overlay">
            <h1>Welcome Admin!</h1>
            <p>Please sign in to continue.</p>
        </div>
        <div class="form-container">
            <form action="adminLogin.php" method="POST" id="loginForm">
                <?php if (!empty($error)) echo $error; ?>
                <input type="email" name="aemail" placeholder="Email" required>
                <input type="password" name="apassword" placeholder="Password" required>
                <input type="submit" name="login" class="button-25" value="Sign In">
                <p><a href="forgotPassword.php">Forgot your password?</a></p>
            </form>
        </div>
    </div>
</body>
</html>
