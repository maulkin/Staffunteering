<?php

$admin_html = true;

require_once('admin-header.inc.php');

if (!$g_user) {
	header("Location: " . ServerConfig::BASE_URL . 'admin/adminlogin.php', true, 302);
}

function h($t)
{
	return htmlspecialchars($t, ENT_QUOTES|ENT_HTML5);
}

?>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Administration - Cambridge Beer Festival Volunteering</title>
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/flick/jquery-ui.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
</head>
<body>
<header>
<h1>Administration - Cambridge Beer Festival Volunteering</h1>
<nav>
Logged in as <?php echo(h($g_user->username)); ?> | <a href="adminlogout.php" title="Logout">Logout</a>
</nav>
</header>

<main id="tabs">
<ul>
<li><a href="#incoming"><span id="incoming-link">Incoming</span></a></li>
<li><a href="#volunteers"><span id="volunteers-link">Volunteers</span></a></li>
<li><a href="#badges"><span id="badges-link">Badges</span></a></li>
</ul>
<div id="incoming">
<p>Table of incoming volunteers to go here</p>
</div>
<div id="volunteers">
<p>Table of existing volunteers</p>
</div>
<div id="badges">
<p>Badge generation, including custom badges</p>
</div>
</main>

<script>
$("#tabs").tabs();
</script>
</body>
</html>
