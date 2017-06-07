<?php
	include '../myphpheader.php';
?>

<script>
function usernameDropdown() {
	//TODO: implement account management buttons here eventually
}

function logout() {
	location.href = "/login.php<?php if(isLoggedIn()){echo '?logout';}?>";
}

function register() {
	location.href = "/register_account.php";
}
</script>
<!-- This banner is meant to go in a div with id banner_holder. Please put it there, and don't give that any style. -->
<div id="username"><?php if(isLoggedIn()){echo getUsername();}else{echo 'Log in or sign up to enjoy all our features.';} ?></div>
<button id="logout" onclick="location.href='/login.php?<?php if(isLoggedIn())echo'logout';?>'"><?php if(isLoggedIn()){echo 'Logout';} else{echo 'Login';}?></button>
<?php
	if(!isLoggedIn()) {
		echo '<button id="register" onclick="location.href=\'/register_account.php\';">Sign Up</button>';
	}
?>

<link rel="stylesheet" type="text/css" href="/banner_style.css">