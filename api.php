<?php
	include 'myphpheader.php';
	
	function intify_id($id) {
		if(strlen($id)>0) {
			return (int)$id;
		}
		return $id;
	}
	
	function followingString($haystack, $needle) {
		$res=strstr($haystack, $needle);
		if($res === false) {
			return null;
		}
		return substr($res, strlen($needle));
	}
	
	function getCalculation($num) {
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Timestamp, INET6_NTOA(IPAddress), UserID, UserAgent, Operation, Result FROM calc_log LIMIT '.($num-1).',1';
		$stmt = $conn->prepare($cmd);
		$stmt->execute();
		$response = $stmt->fetchAll();
		if(isset($response[0])) {
			$line=$response[0];
			return json_encode(array($line['Timestamp'], $line['INET6_NTOA(IPAddress)'], intify_id($line['UserID']),
																					$line['UserAgent'], $line['Operation'], $line['Result']));
		}
		return false;
	}
	
	function logCalculation($ipaddress, $userid, $useragent, $operation, $result) {
		$cmd='INSERT INTO calc_log (IPAddress, UserID, UserAgent, Operation, Result) VALUES (INET6_ATON(:ipaddress), :userid, :useragent, :operation, :result)';
		$conn = new PDO('mysql:host=localhost;dbname=mysql', insert_username, insert_password);
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':ipaddress', $ipaddress);
		$stmt->bindParam(':userid', $userid);
		$stmt->bindParam(':useragent', $useragent);
		$stmt->bindParam(':operation', $operation);
		$stmt->bindParam(':result', $result);
		$stmt->execute();
		return $stmt->fetchAll();
	}
	
	function getCalculationLog($user, $orderby, $page, $pagesize) {
		// $page is injection-vulnerable, so the calling method must check this is a legit number
		$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
		$cmd = 'SELECT Timestamp, INET6_NTOA(IPAddress), UserID, UserAgent, Operation, Result FROM calc_log';
		if($user !== null) {
			if(is_numeric($user)) {
				$cmd.=' WHERE UserID = :userid';
			}
			else {
				$cmd.=' WHERE INET6_NTOA(IPAddress) = :userid';
			}
		}
		if($orderby !== null) {
			switch(strtolower($orderby)) {
			// Fix capitalization, because that sucks
				case 'timestamp':
					$orderby='Timestamp';
					break;
				case 'ipaddress':
					$orderby='IPAddress';
					break;
				case 'userid':
					$orderby='UserID';
					break;
				case 'useragent':
					$orderby='UserAgent';
					break;
				case 'operation':
					$orderby='Operation';
					break;
				case 'result':
					$orderby='Result';
					break;
			}
			$cols=array('Timestamp', 'IPAddress', 'UserID', 'UserAgent', 'Operation', 'Result');
			$orderby=explode(' ', $orderby);
			$dir=' ASC';
			if(isset($orderby[1]) and (strtoupper($orderby[1])=='ASC' or strtoupper($orderby[1])=='DESC')) {
				$dir=' '.$orderby[1];
			}
			elseif ($orderby[0]=='Timestamp') {
				$dir=' DESC';
			}
			$orderby=$orderby[0];
			if(array_search($orderby, $cols) !== false) {
				switch ($orderby) { //Adjust so that it sorts properly
				case 'UserID':
					$orderby='ISNULL(UserID), UserID';
					break;
				}
				$cmd.=' ORDER BY '.$orderby.$dir;
			}
		}
		if($page!==null && $page>0) {
			$page--;
			$cmd.=' LIMIT '.($page*$pagesize).','.$pagesize;
		}
		// echo $cmd;  //Uncomment this if this method is acting up
		$stmt = $conn->prepare($cmd);
		if($user !== null) {
			$stmt->bindParam(':userid', $user);
		}
		$stmt->execute();
		$calculations=array();
		foreach($stmt->fetchAll() as $line) {
			$foo=array($line['Timestamp'], $line['INET6_NTOA(IPAddress)'], intify_id($line['UserID']), $line['UserAgent'],
																									$line['Operation'], $line['Result']);
			$calculations[count($calculations)+1]=$foo;
		}
		return json_encode($calculations);
	}
	
	switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$res=explode('?', followingString($_SERVER['REQUEST_URI'], 'api.php'), 2)[0];
			if(substr($res, 1, 12) == 'calculations') {
				if(strlen(substr($res, 13))>1) {
					$num=substr($res,14);
					if(is_numeric($num)) {
						$calc = getCalculation((int)$num);
						if( $calc === false ) {
							http_response_code(404);
							header('Content-Type: text');
							echo 'Invalid calculation number.';
							break;
						}
						else {
							header('Content-Type: application/json');
							echo $calc;
							break;
						}
					}
					else {
						http_response_code(404);
						echo "The only valid next URLs are the number of the calculation.\n";
						echo substr($res, 13);
						break;
					}
				}
				header('Content-Type: application/json');
				$user=null;
				$sortby=null;
				$page=null;
				$pagesize=10;
				if(isset($_GET['user'])) {
					$user=$_GET['user'];
				}
				if(isset($_GET['sortby']) || isset($_GET['orderby'])) {
					if(isset($_GET['sortby'])) {
						$sortby=$_GET['sortby'];
					}
					else {
						$sortby=$_GET['orderby'];
					}
					if(isset($_GET['order'])) {
						$sortby.=' '.strtoupper($_GET['order']);
					}
				}
				if(isset($_GET['page'])) {
					$page=(int)$_GET['page'];	//note: This returns 0 if the user does not give a valid number.
				}
				if(isset($_GET['pagesize'])) {
					$pagesize=(int)$_GET['pagesize'];
					if($pagesize<=0) {
						$pagesize=10;
					}
				}
				echo getCalculationLog($user, $sortby, $page, $pagesize);
				break;
			}
			elseif (substr($res,1,6)=='userid') {
				header('Content-Type: text');
				if(isLoggedIn()) {
					echo getUserId();
				}
				else {
					echo $_SERVER['REMOTE_ADDR'];
				}
			}
			else {
				http_response_code(404);
				echo 'Unrecognized resource.';
			}
			break;
		
		
		case 'POST':
			$res=explode('?', followingString($_SERVER['REQUEST_URI'], 'api.php'), 2)[0];
			if(substr($res, 1, 12) == 'calculations') {
				if(strlen(substr($res, 13))>1) {
					 http_response_code(405);
					 header('Allow: GET');
					 break;
				}
				if(!isset($_POST['entry'])) {
					http_response_code(400);
					echo 'Missing Post Parameter: entry';
					break;
				}
				$vals=json_decode($_POST['entry']);
				if(!isset($vals->operation) || !isset($vals->result)) {
					http_response_code(400);
					echo 'Incomplete JSON parameter';
					break;
				}
				echo json_encode(logCalculation($_SERVER['REMOTE_ADDR'], getUserId(), $_SERVER['HTTP_USER_AGENT'], $vals->operation, $vals->result));
				
				$conn = new PDO('mysql:host=localhost;dbname=mysql', view_username, view_password);
				$stmt = $conn->prepare('SELECT COUNT(*) FROM calc_log;');
				$stmt->execute();
				$number = $stmt->fetchAll()[0][0];
				http_response_code(201);
				header('Location: /api.php/calculations/'.$number);
				break;
			}
			http_response_code(404);
			break;
		
		default:
			http_response_code(405);
			header('Allow: GET, POST');
	}
?>