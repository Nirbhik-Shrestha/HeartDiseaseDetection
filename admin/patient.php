<?php
    include("../connection.php");
    include_once("../auth.php");

    $admin = requireRole($con, 'admin');
    $useremail = $admin['aemail'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients List</title>
    <link rel="stylesheet" href="../css/adminIndex.css">
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
        }
        .modal h2 {
            margin-top: 0;
            font-size: 24px;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input {
            width: 95%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-group select{
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .modal button:hover {
            background-color: #45a049;
        }

        a{text-decoration: none;}
    </style>
</head>
<body>
    <?php
    // Fetch all patients from the database
    $sql = "SELECT * FROM patients ORDER BY pname ASC";
    $result = $con->query($sql);

    if(isset($_GET['remove'])){
        $remove_id=(int)$_GET['remove'];
        $deleteSql = "
        DELETE FROM appointment WHERE pid = '$remove_id';
        DELETE FROM patients WHERE pid='$remove_id';
        ";
        if(mysqli_multi_query($con, $deleteSql)){
            echo "<script>alert('Deleted successfully.');</script>";
            header('location: patient.php?msg="Patient Deleted Successfully"');
        }else{
            echo "Error: " . mysqli_error($con);
        }
    }

    ?>
    <div class="container">
    <?php include 'sidebar.php' ?>
        <div class="main-content">
            <div class="table-container">
                <h2>Patients List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date of Birth</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["pname"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pemail"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pcontact"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["paddress"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["pdob"]) . "</td>";
                                echo "<td><a href='patient.php?remove=" . htmlspecialchars($row['pid']) . "' class='delete-btn' onclick=\"return confirm('Are you sure you want to remove this patient?');\">Remove</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No patients found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php $con->close(); ?>
</body>
</html>
