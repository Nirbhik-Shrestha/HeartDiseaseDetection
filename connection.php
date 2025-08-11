<?php
				$servername='localhost';
				$user='root';
				$password='';
				$db="doctorProject";
				$con=new mysqli($servername,$user,$password,$db);
				if($con->connect_error)
				{	
					die("Connection Error". $con->connect_error);
				}
				
?>