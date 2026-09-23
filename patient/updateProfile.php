<?php 
	
    //import database
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];
    // $userpassword=$userfetch["ppassword"];
    $usercontact=$userfetch["pcontact"];
    $useraddress=$userfetch["paddress"];
    $userdob=$userfetch["pdob"];



    




?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Edit</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="../css/patientIndex.css" rel="stylesheet">
</head>
<body>

<?php

if(isset($_POST['update'])){

    $pname = $_POST['pname'];
    $pemail = $_POST['pemail'];
    // $ppassword = $_POST['ppassword'];
    $pcontact = $_POST['pcontact'];
    $paddress = $_POST['paddress'];
    $pdob = $_POST['pdob'];

    $sql = "UPDATE patients SET pname = '$pname',
                                 pemail = '$pemail',
                                 pcontact = '$pcontact',
                                 paddress = '$paddress',
                                 pdob = '$pdob'
                                 WHERE  pid = '$userid'";

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



?>

    <div class="container">
        
        <?php include 'sidebar.html';?>

        <div class="main-content">
            <h1>My Profile</h1>
            <div class="breadcrumb">
                <a href="index.php">Dashboard</a> &gt; <span>My Profile</span>
            </div>
            <form class="profile-form" action="updateProfile.php" method="POST">
                <h2>Edit Your Information</h2>
                <div class="form-group inline">
                <div class="form-control">
                    <label for="name">Name</label>
                    <input type="text" id="pname" name="pname" value="<?php echo ($username)?>">
                </div>
                <div class="form-control">
                    <label for="email">Email</label>
                    <input type="email" id="pemail" name="pemail" value="<?php echo ($useremail)?>">
                </div>
                </div>
                <div class="form-group inline">
                <div class="form-control">
                    <label for="country">Country</label>
                    <select id="pcountry" name="pcountry">
                        <option value="+977" selected>Nepal (+977)</option>
                        <!-- Add more country options as needed -->
                    </select>
                </div>
                <div class="form-control">
                    <label for="phone">Phone</label>
                    <input type="text" id="pcontact" name="pcontact" value="<?php echo ($usercontact)?>">
                </div>
            </div>
            <div class="form-group inline">
                <div class="form-control">
                    <label for="address">Address</label>
                    <input type="text" id="paddress" name="paddress" value="<?php echo ($useraddress)?>">
                </div>
                <div class="form-control">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="pdob" name="pdob" value="<?php echo ($userdob)?>">
                </div>
            </div>
                <div class="form-group">
                    <label for="weight">Weight</label>
                    <input type="number" id="weight" placeholder="Kg">
                </div>
                <div class="form-group inline">
                <div class="form-control">
                    <label for="height">Height</label>
                    <div class="height-inputs">
                        <input type="number" id="height-feet" placeholder="Feet">
                        <input type="number" id="height-inches" placeholder="Inches">
                    </div>
                </div>
                <div class="form-control">
                    <label for="gender">Gender</label>
                    <select id="gender">
                        <option value="male" selected>Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                </div>
                <div class="form-group">
                    <label for="blood-group">Blood Group</label>
                    <select id="blood-group">
                        <option value="a+" selected>A+</option>
                        <option value="a-">A-</option>
                        <option value="b+">B+</option>
                        <option value="b-">B-</option>
                        <option value="ab+">AB+</option>
                        <option value="ab-">AB-</option>
                        <option value="o+">O+</option>
                        <option value="o-">O-</option>
                    </select>
                </div>
                <button type="submit" class="update-btn" name="update">Update Now</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('pcontact').addEventListener('input', function (e) 
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
        document.getElementById('pemail').addEventListener('input', function (e) 
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

        document.getElementById('pname').addEventListener('input', function (e) 
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
