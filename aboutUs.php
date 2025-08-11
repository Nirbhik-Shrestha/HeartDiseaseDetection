<?php ?>


<html>
    <head>
        <link rel="stylesheet" href="css/index.css">
        <style>
            body{
                
            background-color: #f4f7f6;
            }
            .banner {
            background-image: url('images/bg2.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            backdrop-filter: 10%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .banner-text {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px 0;
        }
        .aboutus {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: -100px;
            position: relative;
            z-index: 1;
        }
        .aboutus h2, .aboutus h3 {
            color: #00a99d;
        }
        .aboutus p, .aboutus ul {
            color: #333;
            line-height: 1.6;
        }
        .aboutus ul {
            list-style: none;
            padding: 0;
        }
        .aboutus ul li {
            padding-left: 1.5em;
            position: relative;
            margin: 10px 0;
        }
        .aboutus ul li:before {
            content: "•";
            color: #00a99d;
            font-size: 1.2em;
            position: absolute;
            left: 0;
            top: 0;
        }
        .aboutus h2 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .aboutus h3 {
            font-size: 1.5em;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        </style>
    </head>

<?php include('mainHeader.html') ?>

<div class="banner">
    <div class="banner-text">
        <h1>About DaaktarSahab</h1>
        <p>DaaktarSahab is a Online Healthcare Service Provider from Nepal where patients can consult certified medical personnel </p>
        <p>and get additional health related services along with other information related to health online.</p>
    </div>
</div>

<div class="container">
    
     <div class="aboutus">
        <h2><b>DaaktarSahab</b></h2>
        <h3><b>About Us</b></h3>
        <p><strong>DaaktarSahab</strong> is a premier online healthcare service provider based in Nepal, dedicated to connecting patients with certified medical professionals and delivering comprehensive health-related services and information. Our platform bridges the gap between doctors and patients, making healthcare more accessible and straightforward in our country.</p>
        <p>At DaaktarSahab, we believe that accessing reliable medical information and professional health advice should be simple and efficient. Our goal is to revolutionize healthcare in Nepal, providing a user-friendly and widely available service.</p>
        <h3><b>Our Mission</b></h3>
        <p>Our mission is to simplify healthcare, making it accessible to everyone, everywhere. We strive to develop a trustworthy, personalized, and less complicated healthcare service that caters to the needs of our community.</p>
        <h3><b>What We Offer</b>  </h3>
        <ul>
            <li><strong>Expert Consultations:</strong> Patients can consult with certified doctors to receive detailed, research-based, and personalized medical advice. Our platform is used to understand medical conditions, diagnoses, treatment options, and future recommendations.</li>
            <li><strong>Virtual Community:</strong> DaaktarSahab serves as a virtual community where doctors and patients can engage in live chats and discussions, enhancing convenience and fostering better understanding of health matters.</li>
            <li><strong>Health Management:</strong> Patients can maintain a record of their medical history and profiles online, eliminating the need to carry physical medical files and reports.</li>
            <li><strong>Reputation Building for Doctors:</strong> Our services help doctors attract new patients, improve their reputation, and expand their reach to a larger audience.</li>
            <li><strong>Additional Services:</strong> We offer a range of other services including blood donation coordination, online health discussions, and a health feed to keep you informed with the latest health news and tips.</li>
        </ul>
        <h3><b>Our Vision</b> </h3>
        <p>Founded with the vision of making healthcare and medical consultation less complicated and widely accessible, DaaktarSahab aims to provide a reliable and personalized healthcare experience. We are committed to improving healthcare in Nepal through technology and innovation, ensuring that everyone has access to the best medical care available.</p>
        </div>
</div>




<?php include('footer.html') ?>




</html>