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
				$cmd.=' WHERE INET6_NTOA(IPAddress) = :userid AND ISNULL(UserID)';
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
							die();
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
						// echo substr($res, 13);
						die();
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
			elseif (substr($res,1,9)=='calculate') {
				$res=substr($res,10);
				if(strlen($res)<=1) {
					http_response_code(404);
					echo 'Please specify an action.';
					header('Content-Type: text');
					die();
				}
				$res=explode('/',substr($res,1));
				if(strtolower($res[0])=='operations') {
					$x = new stdClass();
					$x->add = new stdClass();
					$x->add->format="%1+%2";
					$x->add->numbers=2;
					$x->subtract = new stdClass();
					$x->subtract->format="%1-%2";
					$x->subtract->numbers=2;
					$x->multiply = new stdClass();
					$x->multiply->format="%1*%2";
					$x->multiply->numbers=2;
					$x->divide = new stdClass();
					$x->divide->format="%1/%2";
					$x->divide->numbers=2;
					$x->sin = new stdClass();
					$x->sin->format="sin(%1)";
					$x->sin->numbers=1;
					$x->cos = new stdClass();
					$x->cos->format="cos(%1)";
					$x->cos->numbers=1;
					if(count($res)>1) {
						$res[1]=strtolower($res[1]);
						if(isset($x->$res[1])) {
							$x=$x->$res[1];
						}
						else {
							http_response_code(404);
							echo 'unrecognized operation';
							header('Content-Type: text');
							die();
						}
					}
					header('Content-Type: application/json');
					echo json_encode($x);
					break;
				}
				$result = 'Sorry, something broke.';
				$res[0]=strtolower($res[0]);
				switch($res[0]) {
					case 'add':
						if(count($res)<=2) {
							http_response_code(400);
							echo 'missing operator(s) for binary operation';
							header('Content-Type: text');
							die();
						}
						$first=(double)$res[1];
						$second=(double)$res[2];
						$op = $first.'+'.$second;
						$result=$first+$second;
						break;
					
					case 'subtract':
						if(count($res)<=2) {
							http_response_code(400);
							echo 'missing operator(s) for binary operation';
							header('Content-Type: text');
							die();
						}
						$first=(double)$res[1];
						$second=(double)$res[2];
						$op = $first.'-'.$second;
						$result=$first-$second;
						break;
					
					case 'multiply':
						if(count($res)<=2) {
							http_response_code(400);
							echo 'missing operator(s) for binary operation';
							header('Content-Type: text');
							die();
						}
						$first=(double)$res[1];
						$second=(double)$res[2];
						$op = $first.'*'.$second;
						$result=$first*$second;
						break;
					
					case 'divide':
						if(count($res)<=2) {
							http_response_code(400);
							echo 'missing operator(s) for binary operation';
							header('Content-Type: text');
							die();
						}
						$first=(double)$res[1];
						$second=(double)$res[2];
						$op = $first.'/'.$second;
						$result=$first/$second;
						break;
					
					case 'sin':
					case 'sine':
						if(count($res)<=1) {
							http_response_code(400);
							echo 'missing operator for unary operation';
							header('Content-Type: text');
							die();
						}
						$num=(double)$res[1];
						$op='sin('.$res[1].')';
						$result=sin($num);
						break;
					
					case 'cos':
					case 'cosine':
						if(count($res)<=1) {
							http_response_code(400);
							echo 'missing operator for unary operation';
							header('Content-Type: text');
							die();
						}
						$num=(double)$res[1];
						$op='cos('.$res[1].')';
						$result=cos($num);
						break;
					
					default:
						http_response_code(404);
						echo "Unreconized operation:\n".$res[0];
						die();
				}
				header('Contet-Type: text');
				logCalculation($_SERVER['REMOTE_ADDR'], getUserId(), $_SERVER['HTTP_USER_AGENT'], $op, $result);
				echo $op.'='.$result;
				break;
			}
			elseif (substr($res,1,5)=='users') {
				$res=substr($res,6);
				//echo $res;
			}
			elseif (substr($res,1,6)=='userid') {
				header('Content-Type: application/json');
				echo '{"id" : "';
				if(isLoggedIn()) {
					echo getUserId();
				}
				else {
					echo $_SERVER['REMOTE_ADDR'];
				}
				echo '"}';
			}
			else {
				http_response_code(404);
				echo 'Unrecognized resource.';
				die();
			}
			break;
		
		
		case 'POST':
			$res=explode('?', followingString($_SERVER['REQUEST_URI'], 'api.php'), 2)[0];
			if(substr($res, 1, 12) == 'calculations') {
				if(strlen(substr($res, 13))>1) {
					 http_response_code(405);
					 header('Allow: GET');
					 die();
				}
				if(!isset($_POST['entry'])) {
					http_response_code(400);
					echo 'Missing Post Parameter: entry';
					die();
				}
				$vals=json_decode($_POST['entry']);
				if(!isset($vals->operation) || !isset($vals->result)) {
					http_response_code(400);
					echo 'Incomplete JSON parameter';
					die();
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
			else {
				http_response_code(404);
				die();
			}
			break;
		
		default:
			http_response_code(405);
			header('Allow: GET, POST');
			die();
	}
?>