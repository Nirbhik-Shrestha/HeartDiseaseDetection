<?php 

session_start();

if (!isset($_SESSION["user"])) {
    $_SESSION["user"] = "";  // Only set to empty if it's not already set
}

if(isset($_SESSION["user"])){
    if(($_SESSION["user"])==""){
        header("location: adminLogin.php");
    }else{
        $useremail=$_SESSION["user"];
    }

}else{
    header("location: adminLogin.php");
}


//import database
include("../connection.php");
$userrow = $con->query("SELECT * from admin where aemail='$useremail'");
$userfetch=$userrow->fetch_assoc();
// $userpassword = $userfetch["apassword"];
// $userid = $userfetch["aid"]

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/adminIndex.css">
    <link rel="stylesheet" href="../css/modal.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php' ?>
        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <input type="text" placeholder="Search">
                    <button type="button">Search</button>
                </div>
                <div class="profile-info">
                    <button><img src="../images/user.png" alt="Profile Picture"></button>
                </div>
            </div>
            <div class="stats">
                <div class="stat">
                    <h2><?php
                            $sql = "SELECT COUNT(*) AS total FROM doctors";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                // Step 3: Fetch the result
                                $row = $result->fetch_assoc();
                                echo $row["total"];
                            } else {
                                echo "";
                            }
                    ?></h2>
                    <p>Doctors</p>
                </div>
                <div class="stat">
                <h2><?php
                            $sql = "SELECT COUNT(*) AS total FROM patients";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                // Step 3: Fetch the result
                                $row = $result->fetch_assoc();
                                echo $row["total"];
                            } else {
                                echo "";
                            }
                    ?></h2>
                    <p>Patients</p>
                </div>
                <div class="stat">
                    <h2><h2><?php
                            $sql = "SELECT COUNT(*) AS total FROM appointment";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                // Step 3: Fetch the result
                                $row = $result->fetch_assoc();
                                echo "Rs. ".$row["total"]*2000;;
                            } else {
                                echo "";
                            }
                    ?></h2></h2>
                    <p>Revenue Generated</p>
                </div>
                <div class="stat">
                <h2><?php
                            $sql = "SELECT COUNT(*) AS total FROM specialties";
                            $result = $con->query($sql);
                            if ($result->num_rows > 0) {
                                // Step 3: Fetch the result
                                $row = $result->fetch_assoc();
                                echo $row["total"];
                            } else {
                                echo "";
                            }
                    ?></h2>
                    <p>Specialties</p>
                </div>
            </div>
            <div class="table-container">
                <h2>Profile</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Password</th>
                            <th>EDIT</th>
                            <!-- <th>DELETE</th> -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $result = $con->query("SELECT * FROM admin");
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<tr>';
                                echo '<td>'  . htmlspecialchars($row['aemail']) . '</td>';
                                echo '<td>' . '*****************' . '</td>';
                                echo '<td><button class="edit-btn" data-email="' . htmlspecialchars($row['aemail']) . '">EDIT</button></td>';
                                // echo '<td><button class="delete-btn" name="delete">DELETE</button></td>';
                            echo '</tr>';
                        }
                    } else {
                        echo "<tr><td colspan='4'>No account found</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="myModal" class="modal" style="top:0px">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Password</h2>
            <form id="updateForm" action="updateProfile.php" method="POST">
                <input type="hidden" id="email" name="email" value="">
                <div class="form-group">
                    <label for="old-password">Old Password</label>
                    <input type="password" id="old-password" name="old-password" required>
                </div>
                <div class="form-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new-password" required>
                </div>
                <button type="submit">Update</button>
            </form>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("myModal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // Get all edit buttons
        var editButtons = document.getElementsByClassName("edit-btn");

        for (var i = 0; i < editButtons.length; i++) {
            editButtons[i].onclick = function() {
                var email = this.getAttribute("data-email");
                document.getElementById("email").value = email;
                modal.style.display = "block";
            }
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
