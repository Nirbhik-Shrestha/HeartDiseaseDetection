<?php
    session_start();

    // Redirect if user is not logged in
    if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
        header("Location: adminLogin.php");
        exit(); // Stop further execution
    }

    include("../connection.php");
    include_once("../scheduleFunctions.php");

    date_default_timezone_set('Asia/Kathmandu');
    $today = date('Y-m-d');

    $errors = [];
    // Keep what was typed so a rejected form can be re-filled instead of cleared.
    $old = ['did' => '', 'sdate' => '', 'stime' => '', 'nop' => ''];

    // Check if form is submitted
    if (isset($_POST['submit'])) {
        $did  = $_POST["did"];
        $date = $_POST["sdate"];
        $time = $_POST["stime"];
        $nop  = $_POST["nop"];

        $old = ['did' => $did, 'sdate' => $date, 'stime' => $time, 'nop' => $nop];

        $errors = validateSessionInput($con, $did, $date, $time, $nop);

        if (empty($errors)) {
            $scid = createSession($con, $did, $date, $time, $nop);

            if ($scid) {
                // Redirect with success message
                header("Location: schedule.php?action=session-added");
                exit(); // Stop further execution
            }

            $errors[] = "Could not save the session. Please try again.";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Session</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 60%;
            margin: 50px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #00a99d;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #00a99d;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin: 5px 0;
        }
        .form-errors {
            background-color: #fdecea;
            border: 1px solid #f5c2bd;
            border-radius: 5px;
            color: #8b2c22;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .form-errors ul {
            margin: 8px 0 0;
            padding-left: 20px;
        }
        .btn-primary {
            background-color: #00a99d;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add New Session</h1>
        <?php if (!empty($errors)): ?>
            <div class="form-errors">
                <strong>This session could not be created:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="" method="POST">
            <div class="form-group">
                <label for="title">Session Title:</label>
                <input type="text" id="title" name="title" placeholder="Enter Session Title" required>
            </div>
            <div class="form-group">
                <label for="did">Doctors:</label>
                <select id="did" name="did" required>
                    <option value="" disabled <?php echo $old['did'] === '' ? 'selected' : ''; ?> hidden>Choose Doctor Name from the list</option>
                    <?php
                        $listDoctors = $con->query("SELECT * FROM Doctors ORDER BY dname ASC");
                        while($row = $listDoctors->fetch_assoc()){
                            $sel = ((string)$old['did'] === (string)$row["did"]) ? ' selected' : '';
                            echo "<option value='".$row["did"]."'$sel>".htmlspecialchars($row["dname"])."</option>";
                        }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nop">No of Patients:</label>
                <input type="number" id="nop" name="nop" min="1" max="<?php echo MAX_SLOTS_PER_SESSION; ?>"
                       value="<?php echo htmlspecialchars($old['nop']); ?>"
                       placeholder="1 to <?php echo MAX_SLOTS_PER_SESSION; ?>" required>
            </div>
            <div class="form-group">
                <label for="sdate">Session Date:</label>
                <input type="date" id="sdate" name="sdate" min="<?php echo $today; ?>"
                       value="<?php echo htmlspecialchars($old['sdate']); ?>" required>
            </div>
            <div class="form-group">
                <label for="stime">Session Time:</label>
                <input type="time" id="stime" name="stime"
                       value="<?php echo htmlspecialchars($old['stime']); ?>" required>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Place this Session</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </form>
    </div>
</body>
</html>
