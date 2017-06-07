<?php
	include 'myphpheader.php';
	
	if(!isset($_POST['email'])) {
		redirect('/register_account.php');
	}
?>
<!DOCTYPE html>

<head id="head">
	<title>Thanks for making a new account</title>
	
	<script>
		function displayBanner() {
			document.getElementById("banner_holder").innerHTML = this.responseText;
		}
		var req=new XMLHttpRequest();
		req.addEventListener("load", displayBanner);
		req.open("GET", "/backend/banner.php");
		req.send();
	</script>
</head>

<body>
	<div id="banner_holder"></div>
	<div id="output">
		<?php 
			if(!isset($_POST['email'])) {
				echo 'You did not just make an account and you broke the redirect. Please go to <a href="/register_account.php">the make account page</a>.';
				die();
			}
			function emailExists($email) {
				$cmd = 'SELECT COUNT(*) FROM users WHERE email=:email';
				$conn = new PDO("mysql:host=localhost;dbname=mysql", view_username, view_password);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmt = $conn->prepare($cmd);
				$stmt->bindParam(':email', $email);
				$stmt->execute();
				return $stmt->fetchAll()[0][0]!='0';
			}
			if(emailExists($_POST['email'])) {
				echo '<p>Sorry, that e-mail address is already registered. Please <a href="/register_account.php">create a new account with a different e-mail address</a>';
				echo 'or <a href="login.php">log in to that account</a>.</p>';
			}
			else {
				$cmd = 'INSERT INTO users (email, password, username) VALUES (:email, SHA2(:password, 256), :username)';
				$conn = new PDO("mysql:host=localhost;dbname=mysql", insert_username, insert_password);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmt = $conn->prepare($cmd);
				$stmt->bindParam(':email', $_POST['email']);
				$hashpass = $_POST['password'].' '.$_POST['email'];
				$stmt->bindParam(':password', $hashpass);
				$stmt->bindParam(':username', $_POST['username']);
				$stmt->execute();
				echo '<p>Congradulations! You just made an account. Now <a href="/login.php">log in to enjoy the entire site</a>.</p>';
			}
		?>
	</div>
</body>