<?php
    session_start();
    ob_start(); // Start output buffering


    // Check if user is logged in
    if (!isset($_SESSION["user"])) {
		$_SESSION["user"] = "";  // Only set to empty if it's not already set
	}

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])==""){
            header("location: doctorLogin.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: doctorLogin.php");
    }


    include("../connection.php");

    $userrow = $con->query("SELECT * from doctors where demail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["did"];
    $username=$userfetch["dname"];
    $useraddress =$userfetch["daddress"];
    $userpassword = $userfetch["dpassword"];
    $usercontact = $userfetch["dcontact"];
    $usernmc = $userfetch["nmc"];


    $userspecrow = $con->query("SELECT sname FROM specialties s 
                                INNER JOIN doctors d ON d.spid = s.spid
                                where did = '$userid'
                                ");
    $userspecfetch = $userspecrow->fetch_assoc();
    $userspec = $userspecfetch["sname"];


?>


<!DOCTYPE html>
<html>
<head>
    <title>Appointment</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }

        .container {
            display: flex;
            flex: 1;
        }

        .main-content {
            flex-grow: 1;
            background-color: #ffffff;
            padding: 30px;
            box-sizing: border-box;
            height: -webkit-fill-available;
            display: flex;
            flex-direction: column;
        }

        .main-content h1 {
            margin-top: 0;
            font-size: 24px;
            color: #333333;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: #777777;
        }

        .breadcrumb a {
            text-decoration: none;
            color: #00a99d;
        }

        .profile-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: auto;
            flex-grow: 1;
        }

        .profile-form h2 {
            margin-top: 0;
            font-size: 20px;
            color: #333333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #cccccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .form-group.inline {
            display: flex;
            justify-content: space-between;
        }

        .form-group.inline .form-control {
            width: 48%;
        }

        .height-inputs {
            display: flex;
            gap: 10px;
        }

        .height-inputs .form-control {
            width: calc(50% - 5px);
        }

        .update-btn {
            background-color: #00a99d;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            display: block;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #cccccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #00a99d;
            color: #ffffff;
        }

        a {
            text-decoration: none;
            color: #00a99d;
            display: inline-block;
            margin-top: 20px;
        }

        a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
<?php

if(isset($_POST['update'])){

    $dpassword = $_POST['dpassword'];

    $sql = "UPDATE doctors SET dpassword = '$dpassword' WHERE  did = '$userid'";

    if(mysqli_query($con, $sql)){
        $_SESSION['message'] = "Modified successfully !!!";
        // echo "done";
    }else{
        $_SESSION['message'] = "Please provide correct format !";
        // echo "No";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_SESSION['message'])) {
    echo "<script>alert('" . $_SESSION['message'] . "');</script>";
    unset($_SESSION['message']); // Unset the message after displaying it
}

ob_end_flush(); // End output buffering and send the output




?>

<div class="container">
<?php include("sidebar.php");?>

    <div class="main-content">
        <h1>My Profile</h1>
            <div class="breadcrumb">
                <a href="index.php">Dashboard</a> &gt; <span>My Profile</span>
            </div>
    <form class="profile-form" action="updateProfile.php" method="POST">
                <h2 style="margin-bottom:0;">Edit Your Information</h2>
                <p style="margin-top:0;color:#777777">You can only change your password</p>
                <div class="form-group inline">
                <div class="form-control">
                    <label for="name">Name</label>
                    <input type="text" id="dname" name="dname" value="<?php echo ($username)?>">
                </div>
                <div class="form-control">
                    <label for="email">Email</label>
                    <input type="email" id="demail" name="demail" value="<?php echo ($useremail)?>">
                </div>
                </div>
                <div class="form-group inline">
                <div class="form-control">
                    <label for="country">Country</label>
                    <select id="dcountry" name="dcountry">
                        <option value="+977" selected>Nepal (+977)</option>
                    </select>
                </div>
                <div class="form-control">
                    <label for="phone">Phone</label>
                    <input type="text" id="dcontact" name="dcontact" value="<?php echo ($usercontact)?>">
                </div>
            </div>
            <div class="form-group inline">
                <div class="form-control">
                    <label for="address">Address</label>
                    <input type="text" id="daddress" name="daddress" value="<?php echo ($useraddress)?>">
                </div>
                <div class="form-control">
                    <label for="spid">Specialties</label>
                    <input type="text" id="spid" name="spid" value="<?php echo ($userspec)?>">
                </div>
            </div>
                <div class="form-group">
                    <label for="nmc">NMC</label>
                    <input type="text" id="nmc" name="nmc" value="<?php echo ($usernmc) ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="dpassword" name="dpassword" placeholder="Password">
                </div>
                
                <button type="submit" class="update-btn" name="update">Update Now</button>
            </form>
    </div>
</div>
</body>
</html>