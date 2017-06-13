<?php
	$nosetCookie=true;
	
	include 'myphpheader.php';
	
	$body=json_decode(file_get_contents('php://input'));
	if(isset($body->token)) {
		$token=$body->token;
	}
	else {
		$token=null;
	}
	
	function lacksValidCredentials() {
		global $token;
		if($token!=null) {
			return time()>getSessionCookieExpiration($token);
		}
		elseif (isset($_COOKIE['User_Session_ID'])) {
			return time()>getSessionCookieExpiration($_COOKIE['User_Session_ID']);
		}
		return true;
	}
	
	if(lacksValidCredentials() and (substr($_SERVER['REQUEST_URI'],0,14)!='/api.php/token')) {
		header('Content-Type: text');
		http_response_code(403);
		echo 'You need to get a token.';
		die();
	}
	
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
	
	function isValidLogin($email, $password) {
		$cmd = 'SELECT email FROM users WHERE email=:email AND password=SHA2(:password, 256)';
		$conn = new PDO("mysql:host=localhost;dbname=mysql", view_username, view_password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($cmd);
		$stmt->bindParam(':email', $email);
		$hashpass = $password.' '.$email;
		$stmt->bindParam(':password', $hashpass);
		$stmt->execute();
		
		$blah = $stmt->fetchAll();
		
		return isset($blah[0][0]);
	}
	
	$res=explode('/',explode('?', followingString($_SERVER['REQUEST_URI'], 'api.php/'), 2)[0]);
	switch($_SERVER['REQUEST_METHOD']) {
		case 'POST':
			switch(strtolower($res[0])) {
				case 'accounts':
					if(isset($res[1])) {
						if(strtolower($res[1])=='current') {
							if(isLoggedIn($token)) {
								$res[1]=getUserId($token);
							}
							else {
								http_response_code(409);
								header('Content-Type: text');
								echo 'You can only do that while logged in.';
								die();
							}
						}
						if(is_numeric($res[1])) {
							$res[1]=(int)$res[1];
							if(getUserId($token)==$res[1] or hasAdminAccess($token)) {
								$x=new stdClass();
								$x->userid	= $res[1];
								$x->email	= getEmailById($res[1]);
								$x->username= getUserById($res[1]);
								if($x->email===null or $res[1]===4) {
									header('Content-Type: text');
									http_response_code(404);
									echo 'There is no user with that id';
									die();
								}
								else {
									header('Content-type: applications/json');
									echo json_encode($x);
								}
								break;
							}
							else {
								http_response_code(403);
								echo 'You do not have an admin account, so you are not allowed to access other accounts.';
								die();
							}
						}
						else {
							http_response_code(404);
							echo 'Only account numbers may be put here, not '.$res[1].'.';
							die();
						}
						break;
					}
					if(!isset($body->email) or !isset($body->username) or !isset($body->password)) {
						http_response_code(400);
						echo 'Incomplete information in JSON parameter.';
						die();
					}
					$email=$body->email;
					$username=$body->username;
					$password=$body->password;
					if(getIdByEmail($email)!==null) {
						http_response_code(409);
						echo 'There already exists an account with that email address';
						die();
					}
					$cmd = 'INSERT INTO users (email, password, username) VALUES (:email, SHA2(:password, 256), :username)';
					$conn = new PDO("mysql:host=localhost;dbname=mysql", insert_username, insert_password);
					$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$stmt = $conn->prepare($cmd);
					$stmt->bindParam(':email', $email);
					$hashpass = $password.' '.$email;
					$stmt->bindParam(':password', $hashpass);
					$stmt->bindParam(':username', $username);
					$stmt->execute();
					http_response_code(204);
					break;
				
				case 'calculations':
					if(count($res)>1) {
						$num=$res[1];
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
							// echo $res[1];
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
						if(strtolower($user)=='current') {
							$user=getUserIdentifier($token);
						}
					}
					if(!hasAdminAccess($token)) {
						$user=getUserIdentifier($token);
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
					
				case 'calculate':
					$res=array_splice($res,1);
					if(count($res)==0) {
						http_response_code(404);
						echo 'Please specify an action.';
						header('Content-Type: text');
						die();
					}
					if(strtolower($res[0])=='operations') {
						$x = new stdClass();
						$x->add = new stdClass();		//addition operator
						$x->add->format="%1+%2";
						$x->add->numbers=2;
						$x->subtract = new stdClass();	//subtraction operator
						$x->subtract->format="%1-%2";
						$x->subtract->numbers=2;
						$x->multiply = new stdClass();	//multiplication operator
						$x->multiply->format="%1*%2";
						$x->multiply->numbers=2;
						$x->divide = new stdClass();	//division operator
						$x->divide->format="%1/%2";
						$x->divide->numbers=2;
						$x->sin = new stdClass();		//sine operator
						$x->sin->format="sin(%1)";
						$x->sin->numbers=1;
						$x->cos = new stdClass();		//cosine operator
						$x->cos->format="cos(%1)";
						$x->cos->numbers=1;
						$x->tan = new stdClass();		//tangent operator
						$x->tan->format="tan(%1)";
						$x->tan->numbers=1;
						$x->tan->requiredCredentials="login";
						if(count($res)>1) {
							$res[1]=strtolower($res[1]);
							if(isset($x->$res[1])) {
								$x=$x->$res[1];
							}
							else {
								http_response_code(404);
								echo 'unrecognized operation';
								print_r($res);
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
						
						case 'tan':
						case 'tangent':
							if(getUsername($token)==null) {
								http_response_code(403);
								echo 'Please log in to access computationally difficult functions.';
								header('Content-Type: text');
								die();
							}
							if(count($res)<=1) {
								http_response_code(400);
								echo 'missing operator for unary operation';
								header('Content-Type: text');
								die();
							}
							$num=(double)$res[1];
							$op='tan('.$res[1].')';
							$result=tan($num);
							break;
						default:
							http_response_code(404);
							echo "Unreconized operation:\n";
							print_r($res);
							die();
					}
					header('Content-Type: application/json');
					logCalculation($_SERVER['REMOTE_ADDR'], getUserId($token), $_SERVER['HTTP_USER_AGENT'], $op, $result);
					echo '{ "operation" : "'.$op.'", "result":"'.$result.'"}';
					break;
				
				case 'change_password':
					if(!isset($body->old_password) or !isset($body->new_password)) {
						http_response_code(400);
						echo 'missing required data';
						header('Content-Type: text');
						die();
					}
					if(!isLoggedIn($token)) {
						http_response_code(409);
						echo 'You must be logged in to change your password.';
						header('Content-Type: text');
						die();
					}
					$oldpass=$body->old_password;
					$newpass=$body->new_password;
					$email = getEmail($token);
					if(isValidLogin($email, $oldpass)) {
						$cmd = 'UPDATE users SET password=SHA2(:password, 256) WHERE Email=:email';
						$conn = new PDO('mysql:host=localhost;dbname=mysql', modify_username, modify_password);
						$stmt = $conn->prepare($cmd);
						$hashpass = $newpass.' '.$email;
						$stmt->bindParam(':password', $hashpass);
						$stmt->bindParam(':email', $email);
						$stmt->execute();
						if(isset($body->force_logout)) {
							$cmd = 'UPDATE session_cookies SET Email=null WHERE Email=:email AND Cookie!=:cookie';
							$stmt = $conn->prepare($cmd);
							$stmt->bindParam(':email', $email);
							if(isset($token)) {
								$stmt->bindParam(':cookie', $token);
							}
							else {
								$stmt->bindParam(':cookie', $_COOKIE['User_Session_ID']);
							}
							$stmt->execute();
						}
						http_response_code(204);
					}
					else {
						http_response_code(400);
						echo 'Invalid password';
						header('Content-Type: text');
						die();
					}
					break;
				case 'users':
					http_response_code(501);
					die();
					break;
				case 'userid':
					header('Content-Type: application/json');
					$id=getUserIdentifier($token);
					if(is_numeric($id)) {
						echo '{"id":'.$id.'}';
					}
					else {
						echo '{"id" : "'.$id.'"}';
					}
					break;
				
				case 'token':
					$token=guid();
					assignCookie('User_Session_ID', $token, 1);
					echo '{ "token" : "'.$token.'" }';
					break;
				
				case 'login':
					if((!isset($body->email) and !isset($body->userid)) or !isset($body->password)) {
						http_response_code(400);
						header('Content-Type: text');
						echo 'Insufficient login credentials.';
						die();
					}
					if(isset($body->email)) {
						$email=$body->email;
					}
					else {
						$email=getEmailById($body->userid);
					}
					$password=$body->password;
					if(isValidLogin($email, $password)) {
						echo '{ "token" : "'.login($email).'" }';
						header('Content-Type: applications/json');
						http_response_code(200);
						break;
					}
					else {
						http_response_code(400);
						echo 'invalid login credentials';
						die();
					}
					break;
				
				case 'logout':
					header('Content-Type: text');
					if(isLoggedIn($token)) {
						logout($token);
					}
					else {
						echo 'You were not logged in.';
						http_response_code(409);
					}
					break;
				
				default:
					http_response_code(404);
					print_r($res);
					die();
				break;
			}
		break;
		
		case 'PUT':
			switch($res[0]) {
				case 'accounts':
					if(isset($res[1])) {
						if(is_numeric($res[1])) {
							if(!isset($body->password)) {
								http_response_code(400);
								header('Content-Type: text');
								echo 'You need to specify a password.';
								die();
							}
							$res[1]=(int)$res[1];
							if(isValidLogin(getEmailById($res[1]), $body->password)) {
								$conn = new PDO('mysql:host=localhost;dbname=mysql', modify_username, modify_password);
								$cmd= 'UPDATE users SET ';
								if(isset($body->email)) {
									$cmd.='Email=:email, ';
								}
								if(isset($body->username)) {
									$cmd.='Username=:username, ';
								}
								if(isset($body->new_password) or isset($body->email)) {
									$cmd.='Password=SHA2(:password, 256), ';
								}
								if(strlen($cmd)==17) {
									http_response_code(400);
									header('Content-Type: text');
									echo 'You are not changing anything.';
									die();
								}
								$cmd=substr($cmd, 0, -2).' WHERE userid=:userid';
								$stmt=$conn->prepare($cmd);
								if(isset($body->email)) {
									$stmt->bindParam(':email', $body->email);
									if(!isset($body->new_password)) {
										$hashpass=$body->password.' '.$body->email;
										$stmt->bindParam(':password', $hashpass);
									}
								}
								if(isset($body->username)) {
									$stmt->bindParam(':username', $body->username);
								}
								if(isset($body->new_password)) {
									$hashpass=$body->new_password.' ';
									if(isset($body->email)) {
										$hashpass.=$body->email;
									}
									else {
										$hashpass.=getEmailById($res[1]);
									}
									$stmt->bindParam(':password', $hashpass);
								}
								$stmt->bindParam(':userid', $res[1]);
								$stmt->execute();
								$stmt->fetchAll();
								$x=new stdClass();
								$x->userid	= $res[1];
								$x->email	= getEmailById($res[1]);
								$x->username= getUserById($res[1]);
								header('Content-Type: applications/json');
								echo(json_encode($x));
							}
							else {
								http_response_code(403);
								header('Content-Type: text');
								echo 'Invalid password or non-existant userid.';
								die();
							}
						}
						else {
							http_response_code(404);
							header('Content-Type: text');
							echo 'You must specify an account number.';
							die();
						}
						break;
					}
					// else { fallThrough()...
				default:
					http_response_code(405);
					header('Allow: POST');
			}
			
			break;
		
		default:
			http_response_code(405);
			if($res[0]='accounts' and count($res)>1) {
				header('Allow: POST, PUT');
			}
			else {
				header('Allow: POST');
			}
			die();
	}
?>