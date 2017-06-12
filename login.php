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
		
		function processLogin() {
			var form=document.getElementById("login_form");
			var params='{ "email" : "'+form.email.value+'", "password" : "'+form.password.value+'" }';
			function processLoginReply() {
				if(this.status==204) {
					location.href="<?php if(isset($_GET['redirect'])){ echo $_GET['redirect']; } else{ echo '/clientcalc.php'; } ?>"
				}
				else {
					document.getElementById("errors").innerHTML='Invalid username or password';
				}
			}
			var lr=new XMLHttpRequest();
			lr.addEventListener("load", processLoginReply);
			lr.open("POST", "/api.php/login");
			lr.send(params);
			
			return false;
		}
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
		<?php
			if(isset($_GET['justregistered'])) {
				echo '<p>Your account has been created. Please log in.</p>';
			}
			else {
				echo '<p>Please input your e-mail address and password to log in.</p>';
			}
		?>
		<p id="errors"><?php if(isset($_GET['invalid'])){ echo 'Invalid username or password.';} if(isset($_GET['failed'])){echo 'Unknown login function error.';}?></p>
		<form id="login_form" onsubmit="return processLogin()" method="GET" action="/login.php">
				<div class="input_row"><p>E-mail: </p><input name="email" type="email" required></div>
				<div class="input_row"><p>Password: </p><input name="password" type="password" required></div>
				<input type="Submit" value="Login">
		</form>
	</div>
</body>