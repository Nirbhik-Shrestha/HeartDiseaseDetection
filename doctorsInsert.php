<?php
session_start();
	include_once 'doctorsDatabase.php';

	$message = '';

	if(isset($_POST['submit']))
	{
		$name = $_POST['name'];
		$address = $_POST['address'];
		$age = $_POST['age'];
		$gender = $_POST['gender'];
		$years_of_experience = $_POST['exp'];
		$myquery = "insert into doctors(name,address,age,gender,exp)values('$name','$address','$age','$gender','$years_of_experience')";
		if(mysqli_query($con,$myquery))
		{
			$message = "Data inserted successfully!!";
		}
		else
		{
			$message = "Data not valid!!";
		}
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title></title>
</head>
<body>
	<h1> DOCTOR REGISTRATION FORM </h1>

	<form action="" method="POST">
		Name: <input type="text" name="name" required><br><br>
		Address: <input type="text" name="address" required><br><br>
		Age: <input type="text" name="age" required min="21"><br><br>
		Gender: <input type="radio" name="gender" value="male"> Male
				<input type="radio" name="gender" value="female"> Female
				<input type="radio" name="gender" value="other"> Others
		<br><br>
		Years of experience: <input type="text" name="exp" required><br><br>

		<input type="submit" name="submit" value="Submit">
	</form>

	<br>
	<h3 style="color: blue;"><?php echo $message ?></h3>

</body>
</html>