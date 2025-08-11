<?php 


session_start();

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

    include("../connection.php");

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
form input[type="text"] {
  width: 100%;
  padding: 10px;
  margin-top: 6px;
  border: 1px solid #ccc;
  border-radius: 6px;
  transition: 0.2s ease;
}

form input:focus {
  border-color: #3498db;
  outline: none;
  box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
}

form .hint {
  font-size: 12px;
  color: #888;
  margin-top: 4px;
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
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 25px;
  font-family: Arial, sans-serif;
  color: #333;
}

.info-box h3 {
  margin-top: 0;
  font-size: 1.3rem;
}

.info-box ul {
  list-style: none;
  padding-left: 0;
}

.info-box ul li {
  margin: 8px 0;
  line-height: 1.5;
}

#toggleBtn {
  background-color: #f39c12;
  border: none;
  color: white;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 15px;
  cursor: pointer;
  transition: 0.3s;
}

#toggleBtn:hover {
  background-color: #e67e22;
}


    </style>
</head>
<body>
<div style="text-align: center; margin-bottom: 10px;">
    <button onclick="toggleInfo()" id="toggleBtn">🛈 Show Help</button>
</div>

<div class="info-box" id="infoBox" style="display: none;">
  <h3>🫀 Check Your Heart Health</h3>
  <p>This form helps you know if you might have a heart problem.</p>
  <ul>
    <li>👵 <strong>Age</strong>: How old are you?</li>
    <li>🚻 <strong>Sex</strong>: Are you male or female?</li>
    <li>💓 <strong>Chest Pain</strong>: Do you feel pain in your chest?</li>
    <li>🩸 <strong>Blood Pressure</strong>: Is your blood pressure high?</li>
    <li>🥚 <strong>Cholesterol</strong>: Do you have high fat in your blood?</li>
    <li>🧃 <strong>Blood Sugar</strong>: Is your sugar level high when you haven’t eaten?</li>
    <li>💗 <strong>Heart Beat</strong>: What is your heartbeat during exercise?</li>
    <li>🏃‍♂️ <strong>Exercise Pain</strong>: Do you get chest pain when walking or running?</li>
    <li>📉 <strong>Heart Stress</strong>: How tired does your heart get during work?</li>
    <li>🩻 <strong>Blood Flow</strong>: How many heart vessels are open?</li>
    <li>🧬 <strong>Thalassemia</strong>: Do you have a blood problem like thalassemia?</li>
  </ul>
  <p>✅ Fill each box carefully. If you don’t know, ask someone or leave it blank.</p>
  <p>🔐 Your answers are private. Used only to check heart risk.</p>
  <p>❤️ After you click "Submit", you’ll see if your heart is at risk or not.</p>
</div>



    <div class="container">
        <h1>Heart Disease Prediction Form</h1>
        <form method="post" action="submit.php">

    <label>Age:</label>
    <input type="number" name="age" required placeholder="Your age in years" title="Enter your age in years" />

    <label>Sex:</label>
    <p class="hint">0: Female, 1: Male</p>
    <input type="number" name="sex" min="0" max="1" required placeholder="0 or 1" title="0 = Female, 1 = Male" />

    <label>Chest Pain Type:</label>
    <p class="hint">0: Typical angina, 1: Atypical angina, 2: Non-anginal pain, 3: Asymptomatic</p>
    <input type="number" name="cp" min="0" max="3" required placeholder="0 to 3" 
           title="Type of chest pain: 0 = Typical angina, 1 = Atypical, 2 = Non-anginal, 3 = No symptoms" />

    <label>Resting Blood Pressure:</label>
    <p class="hint">Blood pressure (in mm Hg) when resting</p>
    <input type="number" name="trestbps" required placeholder="e.g. 120" title="Usual blood pressure when you're at rest" />

    <label>Serum Cholesterol:</label>
    <p class="hint">Cholesterol level in blood (mg/dl). Normal is below 200.</p>
    <input type="number" name="chol" required placeholder="e.g. 180" title="Blood cholesterol level in milligrams per deciliter" />
    
    <label>Fasting Blood Sugar:</label>
    <p class="hint">Is your fasting blood sugar above 120 mg/dl? (1 = Yes, 0 = No)</p>
    <input type="number" name="fbs" min="0" max="1" required placeholder="0 or 1" 
           title="1 = Yes, if blood sugar is over 120 after fasting; 0 = No" />

    <label>Resting ECG Result:</label>
    <p class="hint">0: Normal, 1: ST-T wave abnormality, 2: Left ventricular hypertrophy</p>
    <input type="number" name="restecg" min="0" max="2" required placeholder="0 to 2" 
           title="ECG at rest: 0 = Normal, 1 = Minor issues, 2 = Possible heart muscle thickening" />

    <label>Max Heart Rate Achieved:</label>
    <p class="hint">Your highest heart rate during exercise</p>
    <input type="number" name="thalach" required placeholder="e.g. 150" title="Your peak heart rate during physical activity" />

    <label>Exercise-Induced Angina:</label>
    <p class="hint">Chest pain during exercise? (1 = Yes, 0 = No)</p>
    <input type="number" name="exang" min="0" max="1" required placeholder="0 or 1" 
           title="1 = Yes, you feel chest pain during exercise; 0 = No pain" />

    <label>ST Depression:</label>
    <p class="hint">ST segment depression during exercise (relative to rest)</p>
    <input type="text" name="oldpeak" required placeholder="e.g. 1.4" 
           title="A number like 1.4, which shows stress on the heart during exercise" />

    <label>Slope of the ST Segment:</label>
    <p class="hint">0: Upsloping, 1: Flat, 2: Downsloping</p>
    <input type="number" name="slope" min="0" max="2" required placeholder="0 to 2" 
           title="Shape of ST segment during exercise: 0 = Up, 1 = Flat, 2 = Down" />

    <label>Number of Major Vessels Colored by Fluoroscopy:</label>
    <p class="hint">Number of major blood vessels visible (0 to 3)</p>
    <input type="number" name="ca" min="0" max="3" required placeholder="0 to 3" 
           title="Number of vessels (arteries) with visible blood flow under scan" />

    <label>Thalassemia:</label>
    <p class="hint">1: Normal, 2: Fixed defect, 3: Reversible defect</p>
    <input type="number" name="thal" min="1" max="3" required placeholder="1 to 3" 
           title="Type of thalassemia: 1 = Normal, 2 = Fixed heart issue, 3 = Can be treated" />

    <button type="submit">Submit</button>
</form>

    </div>


<script>
  function toggleInfo() {
    const box = document.getElementById("infoBox");
    const btn = document.getElementById("toggleBtn");
    if (box.style.display === "none") {
      box.style.display = "block";
      btn.innerText = "✖ Hide Help";
    } else {
      box.style.display = "none";
      btn.innerText = "🛈 Show Help";
    }
  }
</script>



</body>
</html>
