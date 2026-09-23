<?php 
    include("../connection.php");
    include_once("../auth.php");
    include_once("../assessment.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];

    // Set by submit.php when the last attempt failed validation.
    $errors = isset($_SESSION['assessment_errors']) ? $_SESSION['assessment_errors'] : [];
    $old    = isset($_SESSION['assessment_old']) ? $_SESSION['assessment_old'] : [];
    unset($_SESSION['assessment_errors'], $_SESSION['assessment_old']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Heart Disease Prediction Form</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        /* General Reset */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background: #f1f4f9;
  padding: 0px 20px;
  color: #333;
}

/* Container */
.container {
  max-width: 600px;
  margin: 40px auto;
  background: #fff;
  padding: 30px 40px;
  border-radius: 12px;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

h1 {
  text-align: center;
  margin-bottom: 30px;
  font-size: 28px;
  color: #2c3e50;
}

/* Form Styling */
form label {
  display: block;
  margin-top: 20px;
  font-weight: bold;
}

form input[type="number"],
form select {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 6px;
  background: #fff;
  font-size: 15px;
  transition: 0.2s ease;
}

form input:focus,
form select:focus {
  border-color: #3498db;
  outline: none;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

form .hint {
  font-size: 13px;
  color: #666;
  margin-top: 6px;
  line-height: 1.45;
}

.unit {
  font-weight: normal;
  color: #777;
}

.field-group {
  border: 1px solid #e3e8ef;
  border-radius: 10px;
  padding: 6px 20px 20px;
  margin-top: 24px;
}

.field-group legend {
  font-weight: bold;
  font-size: 17px;
  color: #2c3e50;
  padding: 0 8px;
}

.group-intro {
  font-size: 14px;
  color: #555;
  background: #f5f8fb;
  border-radius: 6px;
  padding: 10px 12px;
  margin-top: 8px;
  line-height: 1.45;
}

/* Button */
button {
  display: block;
  width: 100%;
  padding: 12px;
  margin-top: 30px;
  background-color: #3498db;
  color: white;
  font-size: 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.3s ease;
}

button:hover {
  background-color: #2980b9;
}

.info-box {
  background-color: #fef9f2;
  border-left: 6px solid #f39c12;
  padding: 16px 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  color: #333;
  line-height: 1.5;
}

.info-box summary {
  cursor: pointer;
  font-weight: bold;
  font-size: 1.05rem;
}

.info-box ul {
  margin: 10px 0 10px 20px;
}

.info-box p {
  margin-top: 10px;
}

.form-errors {
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-left: 6px solid #e53e3e;
  border-radius: 8px;
  padding: 14px 18px;
  margin-bottom: 20px;
  color: #742a2a;
}

.form-errors ul {
  margin: 8px 0 0 20px;
}

    </style>
</head>
<body>
    <div class="container">
        <h1>Heart Disease Prediction Form</h1>

        <details class="info-box" open>
            <summary>Before you start: what you will need</summary>
            <p>This assessment uses the same 13 measurements cardiologists record. Some you know already; others come from test reports:</p>
            <ul>
                <li><strong>Blood tests</strong>: a lipid profile (cholesterol) and a fasting blood sugar test.</li>
                <li><strong>ECG and exercise stress test</strong> (treadmill test / TMT).</li>
                <li><strong>Coronary angiogram</strong> and a <strong>thallium stress scan</strong>, if you have had them.</li>
            </ul>
            <p>Each question below says where to find the value. Enter exactly what the report says. Guessing makes the result less reliable.</p>
            <p>Haven't had some of these tests? <a href="doctors.php?search=Cardiologist">Book a cardiologist</a>. They can order the tests and go through the values with you.</p>
            <p>🔐 Your answers are private and only used to estimate your heart risk. You choose whether to share a result with a doctor.</p>
        </details>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <strong>Please check your answers:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="submit.php">
            <?php renderAssessmentFields($old); ?>
            <button type="submit">Submit</button>
        </form>
    </div>

</body>
</html>
