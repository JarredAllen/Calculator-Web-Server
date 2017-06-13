<?php
	include '../myphpheader.php';
?>

<!-- This banner is meant to go in a div with id banner_holder. Please put it there, and don't give that any style. -->
<div id="banner_username" class="dropdown"><?php if(isLoggedIn()){echo htmlentities(getUsername());}else{echo 'Log in or sign up to enjoy all our features.';} ?>
	<div id="banner_username_dropdown" class="dropdown_list">
		<?php
			if(isLoggedIn()) {
				echo '<button id="banner_manage_account" onclick="location.href=\'/manage_account.php\'">';
				echo	'Manage Account';
				echo '</button>';
			}
		?>
		<button id="banner_logout" onclick="location.href='/login.php?<?php
				if(isLoggedIn()) {
					echo'logout&';
				}
				if(isset($_SERVER['HTTP_REFERER']) and strstr(substr($_SERVER['HTTP_REFERER'], strstr($_SERVER['HTTP_REFERER'], '//')+2), $_SERVER['HTTP_HOST'])==0) {
					echo 'redirect='.$_SERVER['HTTP_REFERER'];
				}
				else {
					echo 'redirect=/clientcalc.php';
				}
			?>'"><?php if(isLoggedIn()){echo 'Logout';} else{echo 'Login';} ?></button>
		<?php
			if(!isLoggedIn()) {
				echo '<button id="banner_register" onclick="location.href=\'/register_account.php\';">Sign Up</button>';
			}
		?>
	</div>
</div>

<link rel="stylesheet" type="text/css" href="/banner_style.css">
<link rel="stylesheet" type="text/css" href="/dropdown.css">