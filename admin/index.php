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
html {
	overflow-y: scroll;
}

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
var available_jobs = null;
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

$.ajax({
	"dataType": "json",
	"url": "jobs.php",
	"async": false,
	"global": false,
	"success": function (data) {
		available_jobs = data;
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
	var f = "<div class='volunteer-details' data-person-id='" + data.person.id + "'>";

	/* Contact information */
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

	/* Information relating to this festival. */
	f += "<div class='column'><h2>Festival information</h2>";
	if (data.flags.length) {
		f += "<div><h3>Requests</h3><ul>";
		$.each(data.flags, function(index, flag_id) {
			if (flag_id in festival_flags) {
				f += "<li data-flag-id='" + flag_id + "'>" + festival_flags[flag_id];
			}
		});
		f += "</div>";
	}

	if (data.sessions.length) {
		f += "<div><h3>Sessions</h3><ul>";
		$.each(data.sessions, function(index, session_id) {
			if (session_id in festival_sessions) {
				f += "<li data-session-id='" + session_id + "'>" + festival_sessions[session_id].name;
			}
		});
		f += "</div>";
	}
	f += "</div>";

	/* Job assignment. */
	f += "<div class='column job-list'><h2>Festival jobs</h2>";
	if (data.jobs.length) {
		f += "<ul class='job-list'>";
		$.each(data.jobs, function(index, job_id) {
			if (job_id in available_jobs) {
				f += "<li data-job-id='" + job_id + "'>" + available_jobs[job_id].name + "<button class='drop-job-button'>Drop</button>";
			}
		});
		f += "</ul>";
	} else {
		f += "<p class='job-empty'>No specific jobs assigned</p>"
	}
	f += "<label>New role:<select class='add-job-select'>"
	$.each(available_jobs, function(id, job) {
		f += "<option value='" + id + "'";
		if ($.inArray(id, data.jobs) >= 0) {
			f += " disabled";
		}
		f += ">" + job.name + "</option>";
	});
	f += "</select></label><button class='add-job-button'>Add</button>";
	f += "</div>";

	f += "</div>";

	return $.parseHTML(f);
}

$("#volunteer-table tbody, #incoming-table tbody").on('click', 'td.details-control', function() {
	var tr = $(this).closest('tr');
	var dt = $(this).closest('table').DataTable();
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

$("#volunteer-table tbody, #incoming-table tbody").on('click', 'button.drop-job-button', function() {
	var wrapper = $(this).closest('div.job-list');
	var li = $(this).closest('li');
	var job_list = li.closest('ul');
	var job_id = li.attr('data-job-id');
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');

	$.post("set-volunteer-job.php", {"person":person_id, "job":job_id, "op":"drop"}).done(
		function(data) {
			wrapper.find('select option[value="' + job_id + '"]').first().removeAttr("disabled");
			if (job_list.children("li").length == 1) {
				job_list.replaceWith("<p class='job-empty'>No specific roles assigned</p>");
			} else {
				li.slideUp('fast', function() {
					li.remove();
				});
			}
		});
});

$("#volunteer-table tbody, #incoming-table tbody").on('click', 'button.add-job-button', function() {
	var wrapper = $(this).closest('div.job-list');
	var job_id = wrapper.find('select').first().val();
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');

	if (!job_id)
		return;

	$.post("set-volunteer-job.php", {"person":person_id, "job":job_id, "op":"add"}).done(
		function(data) {
			wrapper.find('select option[value="' + job_id + '"]').first().attr("disabled","");
			var new_li = $("<li data-job-id='" + job_id + "'>" + available_jobs[job_id].name + "<button class='drop-job-button'>Drop</button>");
			var empty = wrapper.find("p.job-empty").first();
			if (empty.length) {
				var job_list = $("<ul class='job-list'>");
				new_li.appendTo(job_list);
				empty.replaceWith(job_list);
			} else {
				var job_list = wrapper.find("ul.job-list").first();
				new_li.hide();
				new_li.appendTo(job_list);
				new_li.slideDown('fast');
			}
		});
});

$("#incoming-table tbody").on('click', 'button.accept-button', function() {
	var row = incoming_table.row($(this).closest('tr'));
	var person_id = row.data().person_id;

	$.post("accept-incoming.php", {person:person_id}).done(
		function(data) {
			row.remove().draw(false);
		});
});

/* Reload data on tab activation. */
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
