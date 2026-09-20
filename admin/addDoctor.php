<?php
session_start();

if (isset($_SESSION['user'])) {
    if (($_SESSION['user']) == '') {
        header("location: adminLogin.php");
    } else {
        $useremail = $_SESSION['user'];
    }
} else {
    header("location: adminLogin.php");
}

include_once '../connection.php';

$message = '';

if (isset($_POST['submit'])) {
    $name     = $_POST['dname'];
    $email    = $_POST['demail'];
    $password = password_hash($_POST['dpassword'], PASSWORD_BCRYPT);
    $contact  = $_POST['dcontact'];
    $address  = $_POST['daddress'];
    $nmc      = $_POST['nmc'];
    $spid     = $_POST['spid'];
    $myquery  = "INSERT INTO doctors(dname, demail, dpassword, dcontact, daddress, nmc, spid) VALUES ('$name', '$email', '$password', '$contact', '$address', '$nmc', '$spid')";
    if (mysqli_query($con, $myquery)) {
        $message = "Doctor Registered!!";
        header("location: doctors.php?msg='doctor registered'");
    } else {
        $message = "Data not valid!!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #f6f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 500px;
            background-color: white;
            border: 1px solid #ebebeb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        p {
            font-size: 14px;
            color: #888;
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-size: 14px;
            color: #444;
        }
        .input-text, select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
            background-color: #f9f9f9;
        }
        .input-text:focus, select:focus {
            border-color: #00a99d;
            background-color: #fff;
        }
        .login-btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 4px;
            background-color: #00a99d;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-btn:hover {
            background-color: #007f7a;
        }
        .message {
            color: red;
            font-size: 16px;
            margin-top: 15px;
        }

		.backbtn{
			display: flex;
			position: absolute;
			top: 10px;
			left: 10px;
			background-color: #00a99d;
			padding: 10px;
			width: 80px;
			border-radius: 5px;
			color: #fff;
			font-size: 14px;
			justify-content: center;
		}

		.backbtn a{
			text-decoration: none;
		}

		.backbtn:hover{
			background-color: #007f7a;
		}

		
    </style>
</head>
<body>
	<a href="doctors.php"><div class="backbtn">
		BACK
	</div></a>
    <div class="container">
        <h1>Doctor Registration Form</h1>
        <p>Please fill in all the details.</p>
        <form action="" method="POST">
            <label for="dname" class="form-label">Name:</label>
            <input type="text" name="dname" class="input-text" placeholder="Name" id="name" required>
            
            <label for="demail" class="form-label">Email:</label>
            <input type="email" name="demail" class="input-text" placeholder="Email address" id="email" required>
            
            <label for="dpassword" class="form-label">Password:</label>
            <input type="password" name="dpassword" class="input-text" placeholder="Password" required>
            
            <label for="dcontact" class="form-label">Contact:</label>
            <input type="text" name="dcontact" class="input-text" id="contactNumber" placeholder="Contact Number" required>

            <label for="daddress" class="form-label">Address:</label>
            <input type="text" name="daddress" class="input-text" placeholder="Address" required>
            
            <label for="nmc" class="form-label">NMC:</label>
            <input type="text" name="nmc" class="input-text" placeholder="NMC number" required>
            
            <label for="spid" class="form-label">Specialties:</label>
            <select name="spid" class="input-text" required>
                <?php
                $list = $con->query("SELECT * FROM specialties");
                while ($row = $list->fetch_assoc()) {
                    $sn = $row["sname"];
                    $id = $row["spid"];
                    echo "<option value='$id'>$sn</option>";
                }
                ?>
            </select>
            
            <input type="submit" name="submit" value="Register" class="login-btn">
        </form>
        <div class="message">
            <?php echo $message; ?>
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
        </script>
</body>
</html>
