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
				return false;
			}
			function changePassword() {
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
			<form class="align" id="basic_info_form" name="basic_info" onsubmit="return updateBasicInfo()">
				<div><p>E-mail Address: &nbsp;</p>	<input id="basic_info_email" name="email" type="text" value="<?php echo getEmail(); ?>"></div>
				<div><p>Username: </p>				<input id="basic_info_username" name="username" type="text" value="<?php echo getUsername();?>"></div>
				<div><p>Password:</p>				<input id="basic_info_password" name="password" type="password"></div>
				<input type="submit" id="basic_info_submit" value="Update Information">
			</form>
		</div>
		<div id="password_change" class="broad">
			<h2>Change Password:</h1>
			<form class="align" id="password_change_form" name="password_change" onsubmit="return changePassword()">
				<div><p>Current Password:</p>			<input id="password_change_current" name="current" type="password"></div>
				<div><p>New Password:</p>				<input id="password_change_new" name="new" type="password"></div>
				<div><p>Confirm New Password:&nbsp;</p>	<input id="password_change_confirm" name="confirm" type="password"></div>
				<input type="submit" id="password_change_submit" value="New Password">
			</form>
		</div>
	</body>
</html>