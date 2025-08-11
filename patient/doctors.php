<?php 	session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <title>Doctors List</title>
    <!-- <link rel="stylesheet" href="../css/style.css"> -->
    <link rel="stylesheet" href="../css/index.css">

    <style>
* {
            font-family: 'Platin', Times, serif;
            margin: 0;
            padding: 0;
        }
main {
    padding: 20px;
    padding-left: 100px;
}

h2 {
    color: #333;
}

.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: calc(33.333% - 40px);
    box-sizing: border-box;
    min-width: 290px;
}

.card img {
    border-radius: 50%;
    width: 80px;
    height: 80px;
    display: block;
    margin: 0 auto 10px;
}

.card h3 {
    margin: 10px 0;
    color: #00a99d;
    text-align: center;
}

.card p {
    margin: 5px 0;
    color: #666;
    text-align: center;
}

.final-btn {
    display: block;
    background-color: #00a99d;
    color: #fff;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    margin: 10px auto 0;
    text-align: center;
}

.search-form {
    margin-bottom: 20px;
}
.search-input {
    width: 85%;
    padding: 8px;
    font-size: 16px;
}
.search-button {
    background-color: #00a99d;
    color: #fff;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    border-radius: 5px;
    margin-bottom: 5px;
}
.search-button:hover {
    background-color: #2A8387;
}

a{
    text-decoration: none;
}

    </style>
    
</head>
<body>
    <?php



	if (!isset($_SESSION["user"])) {
		$_SESSION["user"] = "";  // Only set to empty if it's not already set
	}

    if(isset($_SESSION["user"])){
        if(($_SESSION["user"])==""){
            header("location: usersLogin.php");
        }else{
            $useremail=$_SESSION["user"];
        }

    }else{
        header("location: usersLogin.php");
    }
    

    //import database
    include("../connection.php");
    $userrow = $con->query("SELECT * from patients where pemail='$useremail'");
    $userfetch=$userrow->fetch_assoc();
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];
    $keyword = '';

    if($_SERVER["REQUEST_METHOD"]=="POST" && !empty($_POST['search'])){
        $keyword = $_POST['search'];
    }


    // Fetch all doctors from the database
    $sql = "SELECT * FROM doctors JOIN specialties ON doctors.spid=specialties.spid";

    if(!empty($keyword)){
        $sql .= " WHERE specialties.sname LIKE '%$keyword%'";
    }

    $sql .= " ORDER BY doctors.dname ASC";
    $result = $con->query($sql);
    
    
    
    
    ?>

    <!-- Header -->
    <?php include('../patientHeader.html')?>

    <!-- Main-Content -->
    <main>
        <h2>Our Doctors</h2>
        <form method="POST" action="doctors.php" class="search-form">
            <input type="text" name="search" placeholder="Search by Specialty" class="search-input" value="<?php echo htmlspecialchars($keyword); ?>">
            <button type="submit" class="search-button">Search</button>
        </form>
        <div class="cards-container">
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {


                    echo "<div class='card'>
                            <img src='../images/user.png' alt='Doctor Image'>
                            <h3>".$row["dname"]."</h3>
                            <p>".$row["sname"]."</p>
                            <p>".$row["dcontact"]."</p>
                            <a href='schedule.php?did=".$row['did']."'><button class='final-btn'>View Available Schedules</button></a>
                          </div>";
                }
            } else {
                echo "0 results";
            }
            $con->close();
            ?>
        </div>
    </main>


    <!-- Footer -->
    <?php include('../footer.html')?>
</body>
</html>