<?php
include("../connection.php");
include_once("../auth.php");

requireRole($con, 'admin');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email       = $con->real_escape_string($_POST["email"]);
    $oldPassword = $_POST["old-password"];
    $newPassword = $_POST["new-password"];

    if (!empty($email) && !empty($oldPassword) && !empty($newPassword)) {
        // Fetch stored hash
        $result = $con->query("SELECT apassword FROM admin WHERE aemail='$email'");
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($oldPassword, $row["apassword"])) {
                // Hash the new password before saving
                $hashedNew = password_hash($newPassword, PASSWORD_BCRYPT);
                $sql = "UPDATE admin SET apassword='$hashedNew' WHERE aemail='$email'";
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
