<?php
	include 'myphpheader.php';
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Please make an account.</title>

		<link rel="stylesheet" type="text/css" href="/register_style.css">
		
		<script src="/backend/display_banner.js" type="text/javascript"></script>
		<script>
			function validate() {
				var form=document.getElementById("make_account_form");
				var err=document.getElementById("errors");
				err.innerHTML="";
				var submit=true;
				if (form.password.value!=form.confirm_password.value) {
					err.innerHTML+="The passwords do not match. ";
					submit=false;
				}
				if (form.password.value.length<8) {
					err.innerHTML+="Your password must contain at least 8 characters.";
					submit=false;
				}
				if(!submit) {
					return false;
				}
				//passed all of the client-side checks
				if(form.username.value=="") {
					form.username.value=form.email.value.substring(0,form.email.value.indexOf("@"));
				}
				return true;
			}
			function process() {
				if(!validate()) {
					return false;
				}
				var form=document.getElementById("make_account_form");
				function getResults() {
					switch(this.status) {
						case 204: //successful
							window.location='/login.php?justregistered';
							console.log('Success!');
							break;
						
						case 409: //that email is already registered
							err.innerHTML="That email is already associated with an account.";
							break;
					}
				}
				var req = new XMLHttpRequest();
				req.addEventListener("load", getResults);
				req.open("POST", "/api.php/accounts");
				var params = '{ "username" : "'+form.username.value+'", "email" : "'+form.email.value+'", "password" : "'+form.password.value+'" }';
				req.send(params);
				
				return false;
			}
			
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
		<div id="register_box">
			<p>Please create an account</p>
			<p id="errors"></p>
			<form id="make_account_form" method="POST" action="account_created.php" onSubmit="return process();">
				<div class="input_row"><p>E-mail: </p>				<input name="email" type="email" required></div>
				<div class="input_row"><p>Password: </p>			<input name="password" type="password" required></div>
				<div class="input_row"><p>Confirm Password: </p>	<input name="confirm_password" type="password" required></div>
				<div class="input_row"><p>Username (optional):</p>	<input name="username" type="text"></div>
				<input type="Submit" value="Create account">
			</form>
		</div>
	</body>
</html>