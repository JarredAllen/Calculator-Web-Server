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
			$conn = new PDO("mysql:host=localhost;dbname=mysql", view_username, view_password);
			$stmt = $conn->prepare($cmd);
			$stmt->bindParam(':value', $value);
			$stmt->execute();
			if($stmt->fetchAll()[0][0] == 0) {
				//give it to the user			
				setcookie($name, $value, $expiration, '/');
				$_COOKIE['User_Session_ID']=$value;
				//update the cookie database
				$cmd = 'INSERT INTO session_cookies (cookie, expire) VALUES ( :value , :expiration )';
				$conn = new PDO("mysql:host=localhost;dbname=mysql", insert_username, insert_password);
				$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$stmt = $conn->prepare($cmd);
				$stmt->bindParam(':value', $value);
				$stmt->bindParam(':expiration', $expiration);
				$stmt->execute();
			}
		}
		else {
			setcookie($name, $value, $expiration, '/');
		}
	}

	function getSessionCookieExpiration($cookie) {
		// return 955627200;		//This line may be uncommented to test handling of expired cookies
		$cmd = 'SELECT expire FROM session_cookies WHERE cookie=:cookie';
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':cookie', $cookie);
		$stmt->execute();
		$time = $stmt->fetchAll()[0][0];
		if(isset($time[0][0])) {
			return $time;
		}
		else {
			//The cookie does not exist, so it returns a time guaranteed to be in the past.
			return 955627200;
		}
	}
	
	function redirect($page) {
		header('location: ' . $page);
	}
	
	function clearOldCookies() {
		$cmd = 'DELETE FROM session_cookies WHERE expire<=:time';
		$conn = new PDO('mysql:host=localhost;dbname=mysql', modify_username, modify_password);
		$stmt = $conn->prepare($cmd);
		$time=time();
		$stmt->bindParam(':time', $time);
		$stmt->execute();
	}
	if(!isset($nosetCookie) or !$nosetCookie) {
		if(!isset($_COOKIE['User_Session_ID'])) {
			//the user lacks a cookie
			assignCookie('User_Session_ID', guid(), 14);
		}
		if(time()>getSessionCookieExpiration($_COOKIE['User_Session_ID'])) {
			//the user tried to use an expired cookie
			assignCookie('User_Session_ID', guid(), 14);
			clearOldCookies();
		}
	}
	
	function logout($token = null) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', modify_username, modify_password);
		$cmd = 'UPDATE session_cookies SET Email=null WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		if($token===null) {
			$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		}
		else {
			$stmt->bindParam(':cookie', $token);
		}
		$stmt->execute();
		echo 'You have been logged out.';
	}
	
	function login($email, $token=null) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', modify_username, modify_password);
		$cmd = 'UPDATE session_cookies SET Email=:email WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		if($token===null) {
			$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		}
		else {
			$stmt->bindParam(':cookie', $token);
		}
		$stmt->execute();
	}
	
	$current_user_username = null;
	function getUsername($token = null) {
		global $current_user_username;
		if($current_user_username !== null) {
			if(current_user_username==="") {
				return null;
			}
			return $current_user_username;
		}
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		if($token === null) {
			$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		}
		else {
			$stmt->bindParam(':cookie', $token);
		}
		$stmt->execute();
		$email=$stmt->fetchAll()[0][0];
		
		$cmd = 'SELECT Username FROM Users WHERE Email=:email;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$stmt->execute();
		
		$val = $stmt->fetchAll();
		if(isset($val[0][0])) {
			$current_user_username=$val[0][0];
			return $val[0][0];
		}
		$current_user_username="";
		return null;
	}
	
	$current_user_id=null;
	function getUserID($token=null) {
		global $current_user_id;
		if($current_user_id !== null) {
			if($current_user_id<0) {
				return null;
			}
			return $current_user_id;
		}
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		if($token !== null) {
			$stmt->bindParam(':cookie', $token);
		}
		else {
			$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		}
		$stmt->execute();
		$email=$stmt->fetchAll()[0][0];
		
		$cmd = 'SELECT UserID FROM Users WHERE Email=:email;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$stmt->execute();
		
		$val = $stmt->fetchAll();
		if(isset($val[0][0])) {
			$current_user_id=$val[0][0];
			return $val[0][0];
		}
		$current_user_id=-1;
		return null;
	}
	
	function getUserById($id) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Username FROM Users WHERE UserID=:id;';
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		$username=$stmt->fetchAll()[0][0];
		return $username;
	}
	
	function getUserIdentifier($token=null) {
		//this is the user's id if logged in, or the 
		$id = getUserID($token);
		if($id===null) {
			return $_SERVER['REMOTE_ADDR'];
		}
		else {
			return $id;
		}
	}
	
	$is_logged_in=null;
	function isLoggedIn( $token = null) {
		global $is_logged_in;
		if($is_logged_in) {
			//warning: This assumes that the token is kept constant and equal to the cookie.
			return $is_logged_in;
		}
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Email FROM Session_Cookies WHERE Cookie=:cookie;';
		$stmt = $conn->prepare($cmd);
		if($token !== null) {
			$stmt->bindParam(':cookie', $token);
		}
		else {
			$stmt->bindParam(':cookie', $_COOKIE["User_Session_ID"]);
		}
		$stmt->execute();
		
		$is_logged_in = ($stmt->fetchAll()[0][0]!==null);
		return $is_logged_in;
	}
	
	function hasAdminAccess( $token = null ) {
		if (getUserID($token)==1) {
			//do not replace that with ===, because getUserID returns a string
			return true;
		}
		return $_SERVER['REMOTE_ADDR']==="::1" or $_SERVER['REMOTE_ADDR']==="127.0.0.1";
	}
?>