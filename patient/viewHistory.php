<?php
session_start();
include("../connection.php");

// User authentication
if (!isset($_SESSION["user"]) || $_SESSION["user"] == "") {
    header("location: usersLogin.php");
    exit;
}

$useremail = $_SESSION["user"];
$userrow = $con->query("SELECT * FROM patients WHERE pemail='$useremail'");
$userfetch = $userrow->fetch_assoc();
$userid = $userfetch["pid"];

// Fetch user prediction history
$query = $con->prepare("SELECT * FROM patient_data WHERE pid = ? ORDER BY pdid DESC");
$query->bind_param("i", $userid);
$query->execute();
$result = $query->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Prediction History</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        .container{
            max-width: fit-content !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .actions a {
            margin: 0 5px;
            text-decoration: none;
        }

        .btn-delete {
            color: red;
        }

        .btn-edit {
            color: orange;
        }

        .btn-view {
            color: green;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>My Prediction History</h1>
    <table>
        <thead>
            <tr>
                <th>Age</th>
                <th>BP</th>
                <th>Cholesterol</th>
                <th>Max Heart Rate</th>
                <th>ST Depression</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['age'] ?></td>
                        <td><?= $row['trestbps'] ?></td>
                        <td><?= $row['chol'] ?></td>
                        <td><?= $row['thalach'] ?></td>
                        <td><?= $row['oldpeak'] ?></td>
                        <td><?= $row['timestamp'] ?? 'N/A' ?></td>
                        <td class="actions">
                            <a class="btn-view" href="viewResult.php?id=<?= $row['pdid'] ?>">View</a>
                            <a class="btn-edit" href="editData.php?id=<?= $row['pdid'] ?>">Edit</a>
                            <a class="btn-delete" href="deleteData.php?id=<?= $row['pdid'] ?>" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No prediction records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="form.php" class="button">+ New Prediction</a>
    <a href="index.php" class="button">Back</a>
</div>
</body>
</html>
