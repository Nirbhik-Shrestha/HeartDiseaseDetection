<?php 
    //import database
    include("../connection.php");
    include_once("../auth.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $userid= $userfetch["pid"];
    $username=$userfetch["pname"];
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Index</title>
        
        <link rel="stylesheet" href="../css/index.css">
    <style>
        
.container {
    max-width: 1200px;
    width: 90%;
    margin: 0 auto;
}   



/* Hero Section */
.hero {
    background: #f0f0f0;
    text-align: center;
    background-image: url(../images/patbg.jpg);
    background-size: cover;
    height: 60vh;
}


.hero-content {
    display: flex;
    height: -webkit-fill-available;
    flex-direction: column;
    justify-content: center;
    backdrop-filter: blur(3px);
}

.hero-content h1{
    font-size: 50px;
    color: white;
}

.hero-content p{
    font-size: 20px;
    color: white;
}



        /* Services Section */
.services {
    background: white;
    margin-top: 20px;
    padding: 20px 0;
    text-align: center;
}

.services_container {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
}

.services_content {
    display: flex;
    flex: 1;
    flex-basis: 45%;
    flex-direction: column;
    justify-content: center;
    min-width: 250px;
    max-width: 300px;
    height: 150px;
    margin: 30px;
    border-radius: 8px;
    text-align: center;
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}

.services_content:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}


    </style>
</head>
<body>

    <!-- Header Section -->
    <?php include('../patientHeader.html') ?>
    
    <!-- Main content area -->
    <main>
        <!-- Hero Section -->
        <section class="hero" aria-label="Welcome to DaaktarSahab">
            <div class="hero-content">
                <h1>Welcome, <?php echo ($username)?></h1>
                <p>Providing top quality medical care and treatment services.</p>
            </div>
        </section>

        <!-- Services Section -->
        <section class="services" id="services">
            <div class="container">
                <h2 class="section-title">Our Services</h2>
                <div class="services_container">
                    <div class="services_content">
                        <h3 class="services_title">Doctor Details</h3>
                        <p class="services_description">
                            Access comprehensive details of experienced doctors.
                        </p>
                    </div>
                    <div class="services_content">
                        <h3 class="services_title">Booking Services</h3>
                        <p class="services_description">
                            Easy booking with your preferred doctor.
                        </p>
                    </div>
                    <div class="services_content">
                        <h3 class="services_title">24/7 Customer Service</h3>
                        <p class="services_description">
                            Friendly customer support around the clock.
                        </p>
                    </div>
                    <div class="services_content">
                        <h3 class="services_title">User-Friendly Interface</h3>
                        <p class="services_description">
                            An interface that is easy to navigate and use.
                        </p>
                    </div>
                    <div class="services_content">
                        <h3 class="services_title">Safest online healthcare platform</h3>
                        <p class="services_description">
                            Your information is highly confidential.
                        </p>
                    </div>
                    <div class="services_content">
                        <h3 class="services_title">Trusted by thousands</h3>
                        <p class="services_description">
                            Thousands of users from across the country have benefitted from our services.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include('../footer.html') ?>
</body>
</html>
