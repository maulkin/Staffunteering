<?php

$admin_html = true;

require_once('admin-header.inc.php');
require_once('local_auth.inc.php');
require_once('user.inc.php');

$target_url = ServerConfig::BASE_URL . 'admin/';

if ($g_user) {
	header("Location: " . $target_url, true, 302);
	exit(0);
}

$error_detail = '';
$present_form = true;

if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['username']) && isset($_POST['password'])) {
	$user_id = admin_authenticate($_POST['username'], $_POST['password']);

	$error_detail = "Invalid username or password.";

	if ($user_id) {
		$user = new User($user_id);
		print_r($user);
		if ($user->is_valid()) {
			$user->set_persist();
			$present_form = false;
		}
	}
}

if (!$present_form) {
	header("Location: " . $target_url, true, 302);
	exit(0);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Cambridge Beer Festival Volunteering - Adminisration</title>
<?php
if (ServerConfig::SERVER_NAME)
	echo "<base href=\"" . ServerConfig::SERVER_NAME . ServerConfig::BASE_URL . "admin/\" />\n";
?>
<style>
header {
	background-color: #701d10 !important;
}
</style>
<link rel="stylesheet" href="../style/base.css" />
</head>
<body>
<header>
<h1>Administration - Cambridge Beer Festival Volunteering</h1>
</header>
<main>
<p>This is the administrative login only.</p>
<?php
if ($error_detail)
	echo "<p class=\"error\">$error_detail</p>\n";
?>
<form method="POST" action="" accept-charset="utf-8">
<label for="username">Username</label>
<input type="username" id="username" name="username" value=""  />

<label for="password">Password</label>
<input type="password" id="password" name="password" value="" />

<input type="submit" value="Login" />
</form>
</main>
</body>
</html>
