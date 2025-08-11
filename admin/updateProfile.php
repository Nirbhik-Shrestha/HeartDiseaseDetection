<?php
session_start();
if (!isset($_SESSION["user"]) || empty($_SESSION["user"])) {
    header("Location: adminLogin.php");
    exit();
}

include("../connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $con->real_escape_string($_POST["email"]);
    $oldPassword = $con->real_escape_string($_POST["old-password"]);
    $newPassword = $con->real_escape_string($_POST["new-password"]);

    if (!empty($email) && !empty($oldPassword) && !empty($newPassword)) {
        // Check if the old password matches
        $result = $con->query("SELECT apassword FROM admin WHERE aemail='$email'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row["apassword"] === $oldPassword) {
                // Update the password
                $sql = "UPDATE admin SET apassword='$newPassword' WHERE aemail='$email'";
                if ($con->query($sql) === TRUE) {
                    echo "<script>alert('Password updated successfully!'); window.location.href = 'index.php';</script>";
                } else {
                    echo "<script>alert('Error updating password: " . $con->error . "'); window.location.href = 'index.php';</script>";
                }
            } else {
                echo "<script>alert('Old password does not match.'); window.location.href = 'index.php';</script>";
            }
        } else {
            echo "<script>alert('No account found with the provided email.'); window.location.href = 'index.php';</script>";
        }
    } else {
        echo "<script>alert('Please fill in all fields.'); window.location.href = 'index.php';</script>";
    }
} else {
    header("Location: index.php");
    exit();
}
?>
