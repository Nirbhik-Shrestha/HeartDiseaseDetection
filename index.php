<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DaaktarSahab - Find Best Doctor</title>
        <link rel="stylesheet" href="css/index.css">
        <style>
     
.container {
    max-width: 1200px;
    width: 90%;
    margin: 0 auto;
}


/* Hero Section */
.hero {
    /* background: #f0f0f0; */
    padding: 50px 0;
    text-align: center;
    background-image: url(images/bg2.jpg);
    background-size: cover;
    align-content: center;
    height: 90vh;
}
.hero h1 {
    font-size: 2.5em;
    margin: 0;
}
.hero p {
    /* color: #666; */
    font-size: 1.2em;
    margin: 10px 0 0;
}
.hero-content {
    background: rgba(0, 0, 0, 0.5);
    padding: 20px;
    border-radius: 8px;
    color: #fff;
    backdrop-filter: blur(5px);
}


/* Services Section */
.services {
    padding: 50px 20px;
    
}
.services .section-title {
    text-align: center;
    font-size: 2em;
    margin-bottom: 30px;
}
.services_container {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
}
.services_content {
    flex: 1;
    min-width: 250px;
    max-width: 300px;
    background: #fff;
    padding: 20px;
    margin: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
    cursor: pointer;
    transition: 0.2s ease-in;
}
.services_title {
    margin-bottom: 10px;
}
.services_description {
    color: #666;
    font-size: 1em;
}
.services_content:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
}
        
    </style>

</head>
<body>

    <!-- Header -->
    <?php include("mainHeader.html");?>

    <!-- Main content area -->
    <main>
        <!-- Hero Section -->
        <section class="hero" aria-label="Welcome to DaaktarSahab">
            <div class="container hero-content">
                <h1>Welcome to <p style="color:#00a99d">DaaktarSahab</p></h1>
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

    <!-- Footer -->
    <?php include('footer.html') ?>

</body>
</html>
