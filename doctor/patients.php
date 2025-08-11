<?php 
session_start();

if(isset($_SESSION['user'])){
    if(($_SESSION['user'])==''){
        header("location: doctorLogin.php");
    }else{
        $useremail = $_SESSION['user'];
    }
}else{
    header("location: doctorLogin.php");
}

// Database connection
include("../connection.php");
$userrow = $con->query("SELECT * from doctors where demail='$useremail'");
$userfetch=$userrow->fetch_assoc();
$userid= $userfetch["did"];
$username=$userfetch["dname"];

?>

<!DOCTYPE html>
<html>
<head>
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


// Fetch unique patients who have appointments with the logged-in doctor
$sql = "SELECT DISTINCT p.pid, p.pname, p.pemail, p.pcontact, p.paddress, p.pdob FROM patients p
        INNER JOIN appointment a ON a.pid = p.pid 
        INNER JOIN timeslot t ON a.tid = t.tid 
        INNER JOIN schedule s ON t.scid = s.scid 
        INNER JOIN doctors d ON s.did = d.did
        WHERE d.did = $userid
        ORDER BY p.pname ASC";
$result = $con->query($sql);
?>

<div class="container">
    <?php include("sidebar.php");?>
    <div class="main-content">
            <h1>Patients List</h1>
            <div class="breadcrumb">
            <a href="index.php">Dashboard</a> &gt; <span>My Patients</span>
        </div>
        <div class="profile-form">
            <h2>All your patients</h2>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Address</th>
                    <th>Date of Birth</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if($result->num_rows > 0){
                            while($row = $result->fetch_assoc()){
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["pname"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pemail"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pcontact"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["paddress"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pdob"]) . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>No patients found</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
            <br><br>
            <a href="index.php">Back</a>
        </div>
    </div>
</div>

<?php $con->close(); ?>

</body>
</html>
