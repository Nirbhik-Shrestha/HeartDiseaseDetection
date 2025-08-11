<?php
include("connection.php");

if(isset($_POST["submit"])) {
    $name = $_POST["rname"];
    $email = $_POST["remail"];
    $subject = $_POST["rsubject"];
    $message = $_POST["rmessage"];

    $sql = "INSERT INTO review (rname, remail, rsubject, rmessage) VALUES ('$name','$email','$subject','$message')";
    $result = mysqli_query($con, $sql);
    if($result) {
        echo "<script>alert('Message Submitted!');</script>";
    } else {
        echo "<script>alert('Cannot submit message right now. Please try again later.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact DaaktarSahab</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            /* font-family: Arial, sans-serif; */
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f4f7f6;
        }
        .banner {
            background-image: url('images/bg2.jpg');
            background-size: cover;
            background-repeat: no-repeat;
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
        .contactus {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: -100px;
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .contactus h2, .contactus h3 {
            color: #00a99d;
        }
        .contactus p, .contactus ul {
            color: #333;
            line-height: 1.6;
        }
        .contact-info, .contact-form {
            flex: 1 1 45%;
            padding: 20px;
        }
        .contact-info ul {
            list-style: none;
            padding: 0;
        }
        .contact-info ul li {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .contact-info ul li img {
            width: 20px;
            margin-right: 10px;
        }
        .contact-form {
            display: flex;
            flex-direction: column;
        }
        .contact-form label {
            margin: 10px 0 5px;
            color: #333;
        }
        .contact-form input, .contact-form textarea {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            font-size: 1em;
        }
        .contact-form button {
            padding: 10px 20px;
            background: #00a99d;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
        }
        .contact-form button:hover {
            background: #007d73;
        }
    </style>
</head>
<body>

<?php include('mainHeader.html'); ?>

<div class="banner">
    <div class="banner-text">
        <h1>Contact DaaktarSahab</h1>
        <p>We are here to assist you. Reach out to us through any of the methods below.</p>
    </div>
</div>

<div class="container">
    <div class="contactus">
        <div class="contact-info">
            <h2>Contact Us</h2>
            <p>If you have any questions, concerns, or feedback, please feel free to get in touch with us. We're here to help and support you.</p>
            <br><br>
            <h3>Our Contact Information</h3>
            <ul>
                <li><img src="images/mail.png" alt="Email Icon"><strong>Email:</strong> support@daaktarsahab.com</li>
                <li><img src="images/contact.png" alt="Phone Icon"><strong>Phone:</strong> +977-1-2345678</li>
                <li><img src="images/location.png" alt="Location Icon"><strong>Address:</strong> 123 Healthcare Street, Kathmandu, Nepal</li>
            </ul>
        </div>
        <div class="contact-form">
            <h3>Send Us a Message</h3>
            <form action="contactUs.php" method="POST">
                <label for="name">Name:</label>
                <input type="text" id="name" name="rname" required><br>

                <label for="email">Email:</label>
                <input type="email" id="email" name="remail" required><br>

                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="rsubject" required><br>

                <label for="message">Message:</label>
                <textarea id="message" name="rmessage" rows="5" required></textarea><br><br>

                <button type="submit" name="submit">Send Message</button>
            </form>
        </div>
    </div>
</div>

<?php include('footer.html'); ?>

</body>
</html>
