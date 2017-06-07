<?php
	echo 'This should not be used anymore, see the api.';
	die();
	
	include '../myphpheader.php';
	
	$timestamp = date("m/d/Y h:i:sa");
	$ipaddress = $_SERVER['REMOTE_ADDR'];
	$userAgent = 'No user agent';
	if(isset($_SERVER['HTTP_USER_AGENT'])) {
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
	}
	$op = $_POST['operation'];
	$result=$_POST['result'];
	
	try {
		$conn = new PDO("mysql:host=localhost;dbname=mysql", insert_credentials[0], insert_credentials[1]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$cmd = "INSERT INTO calc_log (IPAddress, UserID, UserAgent, Operation, Result) VALUES (INET6_ATON(:ipaddress), :userid, :userAgent, :op, :result)";
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':ipaddress', $ipaddress);
		$stmt->bindParam(':userAgent', $userAgent);
		$stmt->bindParam(':op', $op);
		$stmt->bindParam(':result', $result);
		if(isLoggedIn()) {
			$id=getUserID();
			$stmt->bindParam(':userid', $id);
		}
		else {
			$id=null;
			$stmt->bindParam(':userid', $id);
		}
		$stmt->execute();
	}
	catch(PDOException $e) {
		echo '</br>' . $e->getMessage();
	}
?>