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
	background-color: #ffffcc;
	clear: both;
}

.volunteer-details .column {
	float: left;
	margin-right: 20px;
}

.volunteer-details .internal-details {
	clear: both;
	font-size: small;
	font-style: italic;
}

button.submit {
	display: block;
	margin: 2ex 0;
}

a.pdf {
	background: transparent url("../graphics/pdf_file.png") center right no-repeat;
	padding-right: 34px;
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
<li><a href="#add"><span id="add-link">Add</span></a></li>
<li><a href="#incoming"><span id="incoming-link">Incoming</span></a></li>
<li><a href="#volunteers"><span id="volunteers-link">Volunteers</span></a></li>
<li><a href="#prev-volunteers" title="People who've volunteered at previous festivals but not the current one"><span id="prev-volunteers-link">Previous Volunteers</span></a></li>
<li><a href="#sessions"><span id="sessions-link">Sessions</span></a></li>
<li><a href="#reports"><span id="reports-link">Reports</span></a></li>
<li><a href="#badges"><span id="badges-link">Badges</span></a></li>
</ul>

<div id="add">
<form id="add-volunteer-member-form">
<label>Membership number<input type="text" id="add-volunteer-member-number"></label>
<button type="submit" id="add-volunteer-member-submit">Lookup</button>
<span id="add-volunteer-member-status" style="display:none;"></span>
</form>
<form id="add-volunteer-form">
<label class="required" for="add-volunteer-name">Name</label>
<input required type="text" id="add-volunteer-name" name="add-volunteer-name" value="" size="50">
<label for="add-volunteer-badge">Badge Name</label>
<input type="text" id="add-volunteer-badge" name="add-volunteer-badge" value="" size="50">
<label for="add-volunteer-email">Email address</label>
<input type="email" id="add-volunteer-email" name="add-volunteer-email" value="" size="50">
<label for="add-volunteer-address">Address</label>
<textarea id="add-volunteer-address" name="add-volunteer-address" rows="4" cols="80"></textarea>
<button class="submit" type="submit" id="add-volunteer-submit">Add</button>
<span id="add-volunteer-status" style="display:none;"></span>
</form>
</div>

<div id="incoming">
<table id="incoming-table" class="volunteer-list stripe">
<thead><tr><th></th><th>Name</th><th>Member</th><th>Job preferences</th><th>Qualifications</th><th>Notes</th><th></th></tr></thead>
</table>
</div>

<div id="volunteers">
<table id="volunteer-table" class="volunteer-list stripe">
<thead><tr><th></th><th>Name</th><th>Member</th></tr></thead>
</table>
</div>

<div id="prev-volunteers">
<table id="prev-volunteer-table" class="volunteer-list stripe">
<thead><tr><th></th><th>Name</th><th>Member</th></tr></thead>
</table>
</div>

<div id="sessions">
<select id="session-selector"><option value="0" selected style="display:none;">Select session...</option></select>
<table id="session-volunteer-table" class="volunteer-list stripe">
<thead><tr><th></th><th>Name</th><th>Member</th></tr></thead>
</table>
</div>

<div id="reports">
<select id="report-selector"><option value="0" selected style="display:none;">Select report...</option></select>
<a id="download-report-link" style="display:none;" href="">Download report...</a>
<table id="report-volunteer-table" class="volunteer-list stripe">
<thead><tr><th></th><th>Name</th><th>Member</th></tr></thead>
</table>
</div>

<div id="badges">
<p><a href="badge-generate.php" class="pdf">Get pending badges</a></p>
<h2>Custom badges</h2>
<form id="custom-badge-form">
<label class="required" for="custom-badge-name">Name</label>
<input required type="text" id="custom-badge-name" name="custom-badge-name" value="" size="50">
<label class="required" for="custom-badge-job">Job</label>
<input required type="text" id="custom-badge-job" name="custom-badge-job" value="" size="50">
<button type="submit" id="custom-badge-submit">Add</button>
<span id="custom-badge-status" style="display:none;"></span>
</form>
<table id="custom-badge-table" class="stripe">
<thead><tr><th>Name</th><th>Job</th><th></th></tr></thead>
</table>
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

	/* Add options for session selection. */
	$.each(festival_sessions, function(session_id, data) {
		$("#session-selector").append($("<option/>", {
			"value":session_id, "text":data.name}));
	});

	/* Format flags. */
	$.each(festival_data.flags, function(index, flagdata) {
		festival_flags[flagdata.id] = flagdata.description;
		$("#report-selector").append($("<option/>", {
			"value":flagdata.id, "text":flagdata.description}));
	});
}

function render_name(name, type, row)
{
	if (row.approved_badgename && row.approved_badgename.length && row.approved_badgename != name) {
		return name + " <em>(" + row.approved_badgename + ")</em>";
	} else if ((!row.approved_badgename || !row.approved_badgename.length) && row.badgename && row.badgename.length && row.badgename != name) {
		return name + " <em>(" + row.badgename + ")</em>";
	} else {
		return name;
	}
}

var incoming_table = $("#incoming-table").DataTable( {
	"autoWidth": false,
	"ajax": {
		"url":"incoming.php",
		"dataSrc":""
	},
	"columns": [
		{ "data": null, "className":'details-control', "defaultContent": "", "orderable":false, "searchable":false, "width":"20px" },
		{ "data": "name", "render": render_name },
		{ "data": "membership", "defaultContent": "-"},
		{ "data": "jobprefs"},
		{ "data": "quals"},
		{ "data": "notes"},
		{ "data": null, "defaultContent": "<button class='accept-button'>Accept</button>", "orderable":false, "searchable":false }
		],
	"order": [[ 1, "asc" ]]
});

var volunteer_table_options = {
	"autoWidth": false,
	"ajax": {
		"dataSrc":"",
		"url":"empty.json"
	},
	"deferLoading":10,
	"columns": [
		{ "data": null, "className":'details-control', "defaultContent": "", "orderable":false, "searchable":false, "width":"20px" },
		{ "data": "name", "render": render_name },
		{ "data": "membership", "defaultContent": "-"},
		],
	"order": [[ 1, "asc" ]]
};

var volunteer_table = $("#volunteer-table").DataTable(volunteer_table_options);
volunteer_table.ajax.url("volunteers.php").load();

var prev_volunteer_table = $("#prev-volunteer-table").DataTable(volunteer_table_options);
prev_volunteer_table.ajax.url("non-volunteers.php").load();

var session_volunteer_table = $("#session-volunteer-table").DataTable(volunteer_table_options);
$("#session-selector").change(function() {
	var session_id = $(this).val();
	if (session_id) {
		session_volunteer_table.ajax.url("session-volunteers.php?session=" + session_id).load();
	}
})

var report_volunteer_table = $("#report-volunteer-table").DataTable(volunteer_table_options);
$("#report-selector").change(function() {
	var flag_id = $(this).val();
	if (flag_id) {
		var report_url = "report.php?report=flag&flag=" + flag_id;
		report_volunteer_table.ajax.url(report_url).load();
		$("#download-report-link").attr("href", report_url + "&format=csv").show();
	}
})

var badge_table = $("#custom-badge-table").DataTable( {
	"autoWidth": false,
	"ajax": {
		"url":"badge-list.php",
		"dataSrc":""
	},
	"columns": [
		{ "data": "name" },
		{ "data": "job"},
		{ "data": null, "defaultContent": "<button class='custom-badge-reprint-button'>Reprint</button>", "orderable":false, "searchable":false }
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
	if (data.volunteered) {
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
	} else {
		f += "<div class='add-volunteer-festival-wrapper'>This person has not yet volunteered for this festival";
		f += "<button class='add-volunteer-festival-button'>Add</button></div>";
	}
	f += "</div>";

	/* Job assignment. */
	f += "<div class='column job-list'><h2>Festival jobs</h2><p><button class='volunteer-badge-reprint-button'>Reprint badge</button></p>";
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

	/* Badge stuff. */
	f += "<div class='column volunteer-badge'><h2>Badging</h2>";
	var current_badge_name = '';
	var requested_badge_name;
	if (data.person.badgename && data.person.badgename.length) {
		requested_badge_name = data.person.badgename;
	} else {
		requested_badge_name = data.person.name;
	}
	if (data.person.approved_badgename && data.person.approved_badgename.length) {
		current_badge_name = data.person.approved_badgename;
	}
	f += "<p>Requested name: <span class='requested-badge-name'>" + requested_badge_name + "</span> <button class='copy-badge-name-button'>Use name</button></p>";
	f += "<p><button class='badge-real-name-button' data-name='" + data.person.name + "'>Use real name</button></p>";
	f += "<p><input class='badge-name-input' type='text' value='" + current_badge_name + "'><button class='save-badge-name-button'>Save</button></p>";
	f += "</div>";

	f += "<div class='internal-details'>person.id: " + data.person.id + "</div>";
	f += "</div>";

	return $.parseHTML(f);
}

$(".volunteer-list tbody").on('click', 'td.details-control', function() {
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

$(".volunteer-list tbody").on('click', 'button.drop-job-button', function() {
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

$(".volunteer-list tbody").on('click', 'button.add-job-button', function() {
	var wrapper = $(this).closest('div.job-list');
	var job_id = wrapper.find('select').first().val();
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	var add_festival_wrapper = $(this).closest('div.volunteer-details').find('.add-volunteer-festival-wrapper').first();

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
			if (add_festival_wrapper) {
				add_festival_wrapper.hide("fade", null, 1000);
			}
		});
});

$(".volunteer-list tbody").on('click', 'button.add-volunteer-festival-button', function() {
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	var wrapper = $(this).closest('div.add-volunteer-festival-wrapper');

	console.log("Here");

	$.post("volunteer-festival-add.php", {"person":person_id}).done(
		function(data) {
			wrapper.hide("highlight", null, 1000);
		});
});

$(".volunteer-list tbody").on('click', 'button.badge-real-name-button', function() {
	var wrapper = $(this).closest('div.volunteer-badge');
	var name = $(this).attr('data-name');
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	var tr = $(this).closest('tr').prev(); // Child is actually an extra row.
	var dt = $(this).closest('table').DataTable();
	var row = dt.row(tr);

	wrapper.find('.badge-name-input').first().val(name);

	$.post("volunteer-set-badge.php", {"person":person_id, "name":name})
		.done( function() {
			var d = row.data();
			d.approved_badgename = name;
			row.data(d).draw(false);
		})
		.fail( function() {
		});
});

$(".volunteer-list tbody").on('click', '.copy-badge-name-button', function() {
	var wrapper = $(this).closest('div.volunteer-badge');
	var name = wrapper.find('.requested-badge-name').first().text();
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	var tr = $(this).closest('tr').prev(); // Child is actually an extra row.
	var dt = $(this).closest('table').DataTable();
	var row = dt.row(tr);

	wrapper.find('.badge-name-input').first().val(name);

	$.post("volunteer-set-badge.php", {"person":person_id, "name":name})
		.done( function() {
			var d = row.data();
			d.approved_badgename = name;
			row.data(d).draw(false);
		})
		.fail( function() {
		});
});

$(".volunteer-list tbody").on('click', '.save-badge-name-button', function() {
	var wrapper = $(this).closest('div.volunteer-badge');
	var name = wrapper.find('.badge-name-input').first().val();
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	var tr = $(this).closest('tr').prev(); // Child is actually an extra row.
	var dt = $(this).closest('table').DataTable();
	var row = dt.row(tr);

	$.post("volunteer-set-badge.php", {"person":person_id, "name":name})
		.done( function() {
			var d = row.data();
			d.approved_badgename = name;
			row.data(d).draw(false);
		})
		.fail( function() {
		});
});

$(".volunteer-list tbody").on('click', 'button.volunteer-badge-reprint-button', function() {
	var person_id = $(this).closest('div.volunteer-details').attr('data-person-id');
	$.post("badge-reprint.php", {"person":person_id}).done(
		$(this).effect('highlight'));
});

$("#custom-badge-table tbody").on('click', 'button.custom-badge-reprint-button', function() {
	var badge_id = badge_table.row($(this).closest('tr')).data().id;
	$.post("badge-reprint.php", {"badge":badge_id}).done(
		$(this).effect('highlight'));
});

$("#incoming-table tbody").on('click', 'button.accept-button', function() {
	var row = incoming_table.row($(this).closest('tr'));
	var person_id = row.data().person_id;

	$.post("accept-incoming.php", {person:person_id}).done(
		function(data) {
			row.remove().draw(false);
		});
});

$("#add-volunteer-member-form").on('submit', function(event) {
	var number = $("#add-volunteer-member-number").val();
	var status_field = $("#add-volunteer-member-status");
	event.preventDefault();
	status_field.text("Looking up details...").show();
	$.get("member-check.php?number=" + number)
		.done(function (data) {
			status_field.text("Done").hide("highlight", null, 1000);
			$("#add-volunteer-name").val(data.name);
			if (data.email) {
				$("#add-volunteer-email").val(data.email);
			} if (data.address) {
				$("#add-volunteer-address").val(data.address);
			}
		})
		.fail(function () {
			status_field.html("<span class='error'>Lookup failed</span>");
		});
});

$("#add-volunteer-form").on('submit', function(event) {
	$("#add-volunteer-status").hide();
	event.preventDefault();
	var data = {
		"name":$("#add-volunteer-name").val(),
		"badgename":$("#add-volunteer-badge").val(),
		"email":$("#add-volunteer-email").val(),
		"address":$("#add-volunteer-address").val(),
		"membership":$("#add-volunteer-member-number").val()
	};

	$.post("volunteer-add.php", data)
		.done(function () {
			$("#add-volunteer-status").text("Added").show().hide("highlight", null, 3000);
			$("#add-volunteer-member-form")[0].reset();
			$("#add-volunteer-form")[0].reset();
		})
		.fail(function (req) {
			if (req.status == 409) {
				$("#add-volunteer-status").html("<span class='error'>" + req.responseText + "</span>").show();
			} else {
				$("#add-volunteer-status").html("<span class='error'>Unknown error</span>").show();
			}
		});
});

$("#custom-badge-form").on('submit', function(event) {
	$("#custom-badge-status").hide();
	event.preventDefault();
	var data = {
		"name":$("#custom-badge-name").val(),
		"job":$("#custom-badge-job").val()
	};

	$.post("badge-add.php", data)
		.done(function (data) {
			badge_table.row.add(data).draw();
			$("#custom-badge-status").text("Added").show().hide("highlight", null, 3000);
			// Leave job field in place.
			$("#custom-badge-name").val("");
		})
		.fail(function () {
			$("#custom-badge-status").html("<span class='error'>Unexpected item in the badging area</span>").show();
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
		} else if (ui.newPanel.is("#prev-volunteers")) {
			prev_volunteer_table.ajax.reload();
		} else if (ui.newPanel.is("#badges")) {
			badge_table.ajax.reload();
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
