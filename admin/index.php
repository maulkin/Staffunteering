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
<title>Cambridge Beer Festival Volunteering - Adminisration</title>
<style>
header, nav {
	background-color: #701d10 !important;
}
</style>
<link rel="stylesheet" href="../style/base.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="//cdn.datatables.net/plug-ins/1.10.6/integration/jqueryui/dataTables.jqueryui.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
<script src="//cdn.datatables.net/1.10.6/js/jquery.dataTables.min.js"></script>
<script src="//cdn.datatables.net/plug-ins/1.10.6/integration/jqueryui/dataTables.jqueryui.js"></script>
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
<li><a href="#incoming-tab"><span id="incoming-link">Incoming</span></a></li>
<li><a href="#volunteers-tab"><span id="volunteers-link">Volunteers</span></a></li>
<li><a href="#badges-tab"><span id="badges-link">Badges</span></a></li>
</ul>
<div id="incoming-tab">
<table id="incoming-table">
<thead><tr><th>Name</th><th>Job preferences</th><th>Qualifications</th><th>Notes</th><th></th></tr></thead>
</table>
</div>
<div id="volunteers-tab">
<p>Table of existing volunteers</p>
</div>
<div id="badges-tab">
<p>Badge generation, including custom badges</p>
</div>
</main>

<script>
$("#tabs").tabs();

var incoming_table = $("#incoming-table").DataTable( {
	"autoWidth":false,
	"ajax": {
		"url":"incoming.php",
		"dataSrc":""
	},
	"columns": [
		{ "data": "name"},
		{ "data": "jobprefs"},
		{ "data": "quals"},
		{ "data": "notes"},
		{ "data": null, "defaultContent": "<button class='accept-button'>Accept</button>", "orderable":false }
		]
});

$("#incoming-table tbody").on('click', 'button', function() {
	var row = incoming_table.row($(this).parents('tr'));
	var person_id = row.data().person_id;

	$.post("accept-incoming.php", {person:person_id}).done(
		function(data) {
			row.remove().draw(false);
		});
});

</script>
</body>
</html>
