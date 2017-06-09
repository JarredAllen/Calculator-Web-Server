<?php
	echo 'This should not be used anymore, see the api.';
	http_response_code(410);
	die();
	
	include '../myphpheader.php';
	
	$email=$_POST['email'];
	$password=$_POST['password'];
	
	$cmd = 'SELECT email FROM users WHERE email=:email AND password=SHA2(:password, 256)';
	$conn = new PDO("mysql:host=localhost;dbname=mysql", view_username, view_password);
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$stmt = $conn->prepare($cmd);
	$stmt->bindParam(':email', $email);
	$hashpass = $_POST['password'].' '.$_POST['email'];
	$stmt->bindParam(':password', $hashpass);
	$stmt->execute();
	
	$blah = $stmt->fetchAll();
	if(count($blah)==0) {
		echo 'Invalid username or password';
		redirect('/login.php?invalid');
		die();
	}
	if(count($blah)>1) {
		echo 'The login function suffered some unknown error.';
		redirect('/login.php?failed');
		die();
	}
	
	login($blah[0][0]);
	
	if(isset($_GET['redirect'])) {
		redirect($_GET['redirect']);
	}
?>