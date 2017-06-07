<?php
	include (realpath(dirname(__FILE__)).'/config.php');
	//Note: This must be included in the <head> portion of the document, or else it will not work correctly.
	
	function guid()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
	
	function assignCookie($name, $value, $days) {
		$expiration = round(time()/86400)*86400 + $days*86400;
		if($name  == 'User_Session_ID') {
			//check if it already is in the cookie database
			$cmd = 'SELECT COUNT(*) FROM session_cookies WHERE Cookie=:value';
			$conn = new PDO("mysql:host=localhost;dbname=mysql", databaseViewLogin()[0], databaseViewLogin()[1]);
			$stmt = $conn->prepare($cmd);
			$stmt->bindParam(':value', $value);
			$stmt->execute();
			if($stmt->fetchAll()[0][0] == 0) {
				//give it to the user			
				setcookie($name, $value, $expiration);
				$_COOKIE['User_Session_ID']=$value;
				//update the cookie database
				$cmd = 'INSERT INTO session_cookies (cookie, expire) VALUES ( :value , :expiration )';
				$conn = new PDO("mysql:host=localhost;dbname=mysql", databaseInsertLogin()[0], databaseInsertLogin()[1]);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmt = $conn->prepare($cmd);
				$stmt->bindParam(':value', $value);
				$stmt->bindParam(':expiration', $expiration);
				$stmt->execute();
			}
		}
		else {
			setcookie($name, $value, $expiration);
		}
	}

	function getSessionCookieExpiration($cookie) {
		// return 955627200;		//This line may be uncommented to test handling of expired cookies
		$cmd = 'SELECT expire FROM session_cookies WHERE cookie=:cookie';
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseViewLogin()[0], databaseViewLogin()[1]);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $cookie);
		$stmt->execute();
		
		try {
			$time = $stmt->fetchAll()[0][0];
			// echo $time;
			return $time;
		}
		catch(Exception $e) {
			//The cookie does not exist, so it returns a time guaranteed to be in the past.
			return 955627200;
		}
	}
	
	function redirect($page) {
		header('location: ' . $page);
	}
	
	function clearOldCookies() {
		$cmd = 'DELETE FROM session_cookies WHERE expire<=:time';
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseModifyLogin()[0], databaseModifyLogin()[1]);
		$stmt = $conn->prepare($cmd);
		$time=time();
		$stmt->bindParam(':time', $time);
		$stmt->execute();
	}
	
	if(!isset($_COOKIE['User_Session_ID'])) {
		//the user lacks a cookie
		assignCookie('User_Session_ID', guid(), 14);
	}
	if(time()>getSessionCookieExpiration($_COOKIE['User_Session_ID'])) {
		//the user tried to use an expired cookie
		assignCookie('User_Session_ID', guid(), 14);
		clearOldCookies();
	}
	
	function logout() {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseModifyLogin()[0], databaseModifyLogin()[1]);
		$cmd = 'UPDATE session_cookies SET Email=null WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		$stmt->execute();
		echo 'You have been logged out.';
	}
	
	function login($email) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseModifyLogin()[0], databaseModifyLogin()[1]);
		$cmd = 'UPDATE session_cookies SET Email=:email WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		$stmt->execute();
	}
	
	function getUsername() {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseViewLogin()[0], databaseViewLogin()[1]);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		$stmt->execute();
		$email=$stmt->fetchAll()[0][0];
		
		$cmd = 'SELECT Username FROM Users WHERE Email=:email;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$stmt->execute();
		
		$val = $stmt->fetchAll();
		if(isset($val[0][0])) {
			return $val[0][0];
		}
		return null;
	}
	
	function getUserID() {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseViewLogin()[0], databaseViewLogin()[1]);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		$stmt->execute();
		$email=$stmt->fetchAll()[0][0];
		
		$cmd = 'SELECT UserID FROM Users WHERE Email=:email;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$stmt->execute();
		
		$val = $stmt->fetchAll();
		if(isset($val[0][0])) {
			return $val[0][0];
		}
		return null;
	}
	
	function getUserById($id) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseViewLogin()[0], databaseViewLogin()[1]);
		$cmd = 'SELECT Username FROM Users WHERE UserID=:id;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$username=$stmt->fetchAll()[0][0];
		return $username;
	}
	
	function isLoggedIn() {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', databaseViewLogin()[0], databaseViewLogin()[1]);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		$stmt->execute();
		
		return $stmt->fetchAll()[0][0]!=null;
	}
?>