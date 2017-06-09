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
<button id="logout" onclick="location.href='/login.php?<?php
			if(isLoggedIn()) {
				echo'logout&';
			}
			if(isset($_SERVER['HTTP_REFERER']) and strstr(substr($_SERVER['HTTP_REFERER'], strstr($_SERVER['HTTP_REFERER'], '//')+2), $_SERVER['HTTP_HOST'])==0) {
				echo 'redirect='.$_SERVER['HTTP_REFERER'];
			}
			else {
				echo 'redirect=/clientcalc.php';
			}
		?>'"><?php if(isLoggedIn()){echo 'Logout';} else{echo 'Login';}?></button>
<?php
	if(!isLoggedIn()) {
		echo '<button id="register" onclick="location.href=\'/register_account.php\';">Sign Up</button>';
	}
?>

<link rel="stylesheet" type="text/css" href="/banner_style.css">