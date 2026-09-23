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
    <title>Doctors List</title>
    <link rel="stylesheet" href="../css/adminIndex.css">
    <link rel="stylesheet" href="../css/modal.css">
</head>
<body>
    <?php
    // Fetch all doctors from the database
    $sql = "SELECT * FROM doctors JOIN specialties ON doctors.spid=specialties.spid ORDER BY doctors.dname ASC";
    $result = $con->query($sql);

    if(isset($_GET['remove'])){
        $remove_id=(int)$_GET['remove'];
        $deleteSql = "
        DELETE FROM appointment WHERE scid IN (SELECT scid FROM schedule WHERE did = $remove_id);
        DELETE FROM schedule WHERE did = '$remove_id';
        DELETE FROM doctors WHERE did='$remove_id';
        ";
        if(mysqli_multi_query($con, $deleteSql)){
            echo "<script>alert('Deleted successfully.');</script>";
            header('location: doctors.php?msg="Doctor Deleted Successfully"');
        }else{
            echo "Error: " . mysqli_error($con);
        }
    }

    if(isset($_POST['update'])){
        $did = $_POST['did'];
        $dname = $_POST['dname'];
        $demail = $_POST['demail'];
        $spid = $_POST['spid'];
        $dcontact = $_POST['dcontact'];
        $nmc = $_POST['nmc'];

        $updateSql = "UPDATE doctors SET dname='$dname', demail='$demail', spid='$spid', dcontact='$dcontact', nmc='$nmc' WHERE did='$did'";
        if($con->query($updateSql) === TRUE){
            echo "<script>alert('Updated successfully.');</script>";
            header('location: doctors.php?msg="Doctor Updated Successfully"');
        }else{
            echo "Error: " . mysqli_error($con);
        }
    }
    ?>

    <div class="container">
    <?php include 'sidebar.php' ?>
        <div class="main-content">
            <div class="table-container">
            <h2>Doctors List</h2>
            <a href="addDoctor.php"><button class="addbtn">Add New Doctor</button></a><br><br>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Specialty</th>
                            <th>Contact</th>
                            <th>NMC number</th>
                            <th colspan="2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["dname"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["demail"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["sname"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["dcontact"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["nmc"]) . "</td>";
                                echo "<td><button class='edit-btn' data-id='" . htmlspecialchars($row['did']) . "' data-name='" . htmlspecialchars($row['dname']) . "' data-email='" . htmlspecialchars($row['demail']) . "' data-spid='" . htmlspecialchars($row['spid']) . "' data-contact='" . htmlspecialchars($row['dcontact']) . "' data-nmc='" . htmlspecialchars($row['nmc']) . "'>Edit</button></td>";
                                echo "<td><a href='doctors.php?remove=" . htmlspecialchars($row['did']) . "' class='delete-btn' onclick=\"return confirm('Remove this doctor?');\">Remove</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>No doctors found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Doctor</h2>
            <form id="editForm" action="doctors.php" method="POST">
                <input type="hidden" id="did" name="did">
                <div class="form-group">
                    <label for="dname">Name</label>
                    <input type="text" id="dname" name="dname" required>
                </div>
                <div class="form-group">
                    <label for="demail">Email</label>
                    <input type="email" id="demail" name="demail" required>
                </div>
                <div class="form-group">
                    <label for="spid">Specialty</label>
                    <select id="spid" name="spid" required>
                        <?php
                        // Fetch specialties from the database
                        $specialtySql = "SELECT * FROM specialties";
                        $specialtyResult = $con->query($specialtySql);
                        if ($specialtyResult->num_rows > 0) {
                            while($specialtyRow = $specialtyResult->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($specialtyRow['spid']) . "'>" . htmlspecialchars($specialtyRow['sname']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dcontact">Contact</label>
                    <input type="text" id="dcontact" name="dcontact" required>
                </div>
                <div class="form-group">
                    <label for="nmc">NMC Number</label>
                    <input type="text" id="nmc" name="nmc" required>
                </div>
                <button type="submit" name="update">Update</button>
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
                var did = this.getAttribute("data-id");
                var dname = this.getAttribute("data-name");
                var demail = this.getAttribute("data-email");
                var spid = this.getAttribute("data-spid");
                var dcontact = this.getAttribute("data-contact");
                var nmc = this.getAttribute("data-nmc");

                document.getElementById("did").value = did;
                document.getElementById("dname").value = dname;
                document.getElementById("demail").value = demail;
                document.getElementById("spid").value = spid;
                document.getElementById("dcontact").value = dcontact;
                document.getElementById("nmc").value = nmc;

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

    <?php $con->close(); ?>

</body>
</html>
