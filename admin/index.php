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
<?php
if (ServerConfig::SERVER_NAME)
	echo "<base href=\"" . ServerConfig::SERVER_NAME . ServerConfig::BASE_URL . "admin/\" />\n";
?>
<style>
header, nav {
	background-color: #701d10 !important;
}

td.details-control {
	background: url('../graphics/details_open.png') no-repeat center center;
	cursor: pointer;
}

tr.shown td.details-control {
	background: url('../graphics/details_close.png') no-repeat center center;
}

.volunteer-details h2 {
	margin: 0 0 5px 0;
}

.volunteer-details {
	clear: both;
}

.volunteer-details .column {
	float: left;
	margin-right: 20px;
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
<li><a href="#incoming"><span id="incoming-link">Incoming</span></a></li>
<li><a href="#volunteers"><span id="volunteers-link">Volunteers</span></a></li>
<li><a href="#badges"><span id="badges-link">Badges</span></a></li>
</ul>
<div id="incoming">
<table id="incoming-table" class="stripe">
<thead><tr><th></th><th>Name</th><th>Member</th><th>Job preferences</th><th>Qualifications</th><th>Notes</th><th></th></tr></thead>
</table>
</div>
<div id="volunteers">
<table id="volunteer-table" class="stripe">
<thead><tr><th></th><th>Name</th><th>Member</th></tr></thead>
</table>
</div>
<div id="badges">
<p>Badge generation, including custom badges</p>
</div>
</main>

<script>

var festival_data = null;
var festival_sessions = {};
var festival_flags = {};

$.ajax({
	"dataType": "json",
	"url": "festival.php",
	"async": false,
	"global": false,
	"success": function (data) {
		festival_data = data;
	}
});

function format_time(dt)
{
	var s = dt.toTimeString().substr(0,5);
	return (s == "00:00") ? "midnight" : s;
}

if (!festival_data) {
	alert("Failed to load festival data. Fuck.");
} else {
	/* Transform session data for lookup */
	$.each(festival_data.sessions, function(group, day) {
		$.each(day, function(session_date, session_list) {
			$.each(session_list, function(index, session_data) {
				var start = new Date(session_data.start);
				var end = new Date(session_data.end);
				festival_sessions[session_data.id] = {
					"raw": session_data,
					"name": group + " " + $.datepicker.formatDate('D d M', start) + " " +
						format_time(start) + "-" + format_time(end)
				};
			});
		});
	});

	/* Format flags. */
	$.each(festival_data.flags, function(index, flagdata) {
		festival_flags[flagdata.id] = flagdata.description;
	});
}

var incoming_table = $("#incoming-table").DataTable( {
	"autoWidth": false,
	"ajax": {
		"url":"incoming.php",
		"dataSrc":""
	},
	"columns": [
		{ "data": null, "className":'details-control', "defaultContent": "", "orderable":false, "searchable":false, "width":"20px" },
		{ "data": "name", "render": function(data, type, row) {
			if (row.badgename != data) {
				return data + " <em>(" + row.badgename + ")</em>";
			} else {
				return data;
			}
		}},
		{ "data": "membership", "defaultContent": "-"},
		{ "data": "jobprefs"},
		{ "data": "quals"},
		{ "data": "notes"},
		{ "data": null, "defaultContent": "<button class='accept-button'>Accept</button>", "orderable":false, "searchable":false }
		],
	"order": [[ 1, "asc" ]]
});

$("#incoming-table tbody").on('click', 'button', function() {
	var row = incoming_table.row($(this).parents('tr'));
	var person_id = row.data().person_id;

	$.post("accept-incoming.php", {person:person_id}).done(
		function(data) {
			row.remove().draw(false);
		});
});

var volunteer_table = $("#volunteer-table").DataTable( {
	"autoWidth": false,
	"ajax": {
		"url":"volunteers.php",
		"dataSrc":""
	},
	"columns": [
		{ "data": null, "className":'details-control', "defaultContent": "", "orderable":false, "searchable":false, "width":"20px" },
		{ "data": "name", "render": function(data, type, row) {
			if (row.badgename != data) {
				return data + " <em>(" + row.badgename + ")</em>";
			} else {
				return data;
			}
		}},
		{ "data": "membership", "defaultContent": "-"},
		],
	"order": [[ 1, "asc" ]]
});

function get_volunteer_details(id, target)
{
	$.get("volunteer-single.php?person=" + id)
		.done(function (data) {
			target.child(format_volunteer_details(data));
		})
		.fail(function() {
			target.child("<span class='error'>Failed to load</span>");
		})
}

function format_volunteer_details(data)
{
	var f = "<div class='volunteer-details'>";

	console.log(data);

	f += "<div class='column'><h2>Contact information</h2>";
	if (data.person.email) {
		f += "<div class='email'><a href='mailto:" + data.person.email + "' target='_blank'>" + data.person.email + "</a></div>";
	} else {
		f += "<div class='email'>No email address available</div>";
	}

	if (data.person.address) {
		f += "<div class='address'>" + data.person.address + "</div>";
	}
	f += "</div>";

    f += "<div class='column'><h2>Festival information</h2>";
	if (data.flags.length) {
		f += "<div><h3>Requests</h3><ul>";
		$.each(data.flags, function(index, flag_id) {
			if (flag_id in festival_flags) {
				f += "<li>" + festival_flags[flag_id];
			}
		});
		f += "</div>";
	}

	if (data.sessions.length) {
		f += "<div><h3>Sessions</h3><ul>";
		$.each(data.sessions, function(index, session_id) {
			if (session_id in festival_sessions) {
				f += "<li>" + festival_sessions[session_id].name;
			}
		});
		f += "</div>";
	}
	f += "</div>";

	f += "</div>";
	return f;
}

$("#volunteer-table tbody, #incoming-table tbody").on('click', 'td.details-control', function() {
	var tr = $(this).parents('tr');
	var dt = $(this).parents('table').DataTable();
	var row = dt.row(tr);

	if (row.child.isShown()) {
		row.child.hide();
		tr.removeClass('shown');
	} else {
		row.child("Loading....").show();
		get_volunteer_details(row.data().person_id, row);
		tr.addClass('shown');
	}
});

$("#tabs").tabs({
	beforeActivate: function (event, ui) {
		window.location.hash = ui.newPanel.attr('id');
	},
	activate: function(event, ui) {
		if (ui.newPanel.is("#incoming")) {
			incoming_table.ajax.reload();
		} else if (ui.newPanel.is("#volunteers")) {
			volunteer_table.ajax.reload();
		}
	}
});

if (location.hash) {
	var el = $('#tabs a[href="' + location.hash + '"]');
	if (el) {
		$('#tabs').tabs('option', 'active', el.parent().index());
	}
}

</script>
</body>
</html>
