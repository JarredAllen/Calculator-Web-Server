<?php
	include 'myphpheader.php';
	
	if(!isLoggedIn()) {
		http_response_code(409);
		echo 'You must log in to manage your account settings.';
		die();
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Account Management</title>
		
		<script src="/backend/display_banner.js" type="text/javascript"></script>
		<script>
			function updateBasicInfo() {
				var form=document.getElementById("basic_info_form");
				var email = form.email.value;
				var username = form.username.value;
				var password = form.password.value;
				var params = JSON.stringify({"password":password, "email":email, "username":username});
				function updateResponseHandler() {
					var response=document.getElementById('basic_info_response');
					switch (this.status) {
						case 200:
							response.className="confirm_success";
							response.innerHTML="Info successfully changed.";
							reloadBanner();
							break;
						
						case 403:
							if(this.responseText=='Invalid password or non-existant userid.') {
								response.className="error";
								response.innerHTML="The password is incorrect.";
								break;
							}
							// else { fallThrough()...
						default:
							response.className="error";
							response.innerHTML="A unknown error has occured.";
							break;
					}
				}
				var req=new XMLHttpRequest();
				req.addEventListener("load", updateResponseHandler);
				req.open("PUT", "/api.php/accounts/<?php echo getUserId();?>");
				req.send(params);
				return false;
			}
			function changePassword() {
				var form=document.getElementById("password_change_form");
				var password = form.current.value;
				var newPassword = form.new.value;
				var newPasswordCopy = form.confirm.value;
				if(newPassword !== newPasswordCopy) {
					var response=document.getElementById('password_change_response');
					response.className='error';
					response.innerHTML="The passwords do not match.";
					return false;
				}
				if(newPassword.length<8) {
					var response=document.getElementById('password_change_response');
					response.className='error';
					response.innerHTML="Your password must have at least 8 characters.";
					return false;
				}
				var params = JSON.stringify({"password":password, "new_password":newPassword});
				function updateResponseHandler() {
					var response=document.getElementById('password_change_response');
					switch (this.status) {
						case 200:
							response.className="confirm_success";
							response.innerHTML="Password successfully changed.";
							break;
						
						case 403:
							response.className="error";
							response.innerHTML="The password is incorrect.";
							break;
						
						default:
							response.className="error";
							response.innerHTML="A unknown error has occured.";
							break;
					}
				}
				var req=new XMLHttpRequest();
				req.addEventListener("load", updateResponseHandler);
				req.open("PUT", "/api.php/accounts/<?php echo getUserId();?>");
				req.send(params);
				return false;
			}
		</script>
		<link rel="stylesheet" type="text/css" href="manage_account_style.css">
	</head>
	
	<body>
		<div id="banner_holder"></div>
		<h1>Account Management Options</h1>
		<div id="basic_info" class="broad">
			<h2>Account Information</h1>
			<p>This section contains basic information pertaining to your account.
			   You may change your e-mail or username, but you must input your password to do so.</p>
			<p id="basic_info_response">&nbsp;</p>
			<form class="align" id="basic_info_form" name="basic_info" onsubmit="return updateBasicInfo()">
				<div><p>E-mail Address: &nbsp;</p>	<input id="basic_info_email" name="email" type="text" value="<?php echo getEmail(); ?>"></div>
				<div><p>Username: </p>				<input id="basic_info_username" name="username" type="text" value="<?php echo getUsername();?>"></div>
				<div><p>Password:</p>				<input id="basic_info_password" name="password" type="password"></div>
				<p class="blankline">&nbsp;</p>
				<input type="submit" id="basic_info_submit" value="Update Information">
			</form>
		</div>
		<div id="password_change" class="broad">
			<h2>Change Password:</h1>
			<p id="password_change_response">&nbsp;</p>
			<form class="align" id="password_change_form" name="password_change" onsubmit="return changePassword()">
				<div><p>Current Password:</p>			<input id="password_change_current" name="current" type="password"></div>
				<div><p>New Password:</p>				<input id="password_change_new" name="new" type="password"></div>
				<div><p>Confirm New Password:&nbsp;</p>	<input id="password_change_confirm" name="confirm" type="password"></div>
				<p class="blankline">&nbsp;</p>
				<input type="submit" id="password_change_submit" value="New Password">
			</form>
		</div>
	</body>
</html>