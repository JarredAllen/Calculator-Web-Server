<?php
	include 'myphpheader.php';
?>
<!DOCTYPE html>

<head>
	<title>Log in</title>
	
	<link rel="stylesheet" type="text/css" href="/register_style.css">
	
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
	<div id="login_form_box">
		<p><?php	
			if(isset($_GET['logout'])) {
				logout();
			}
		?></p>
		<p>Please input your e-mail address and password to log in.</p>
		<p id="errors"><?php if(isset($_GET['invalid'])){ echo 'Invalid username or password.';} if(isset($_GET['failed'])){echo 'Unknown login function error.';}?></p>
		<form id="login_form" method="POST" action="/backend/login_process.php<?php 
																					if(isset($_SERVER['HTTP_REFERER']) && strstr(substr($_SERVER['HTTP_REFERER'], strstr($_SERVER['HTTP_REFERER'], '//')+2), $_SERVER['HTTP_HOST'])==0) {
																							echo '?redirect='.urlencode($_SERVER['HTTP_REFERER']);
																					}
																					else {
																						echo '?redirect=/';
																					}
																			  ?>">
				<div class="input_row"><p>E-mail: </p><input name="email" type="email" required></div>
				<div class="input_row"><p>Password: </p><input name="password" type="password" required></div>
				<input type="Submit" value="Login">
		</form>
	</div>
</body>