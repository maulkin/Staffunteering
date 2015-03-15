<?php

require_once('header.inc.php');

if (!$g_person) {
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

require_once('festival.inc.php');
$festival = Festival::current();

if (!$festival) {
	/* No current festival */
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

$is_member = $g_person->is_member();

if (!$festival->nonmembers && !$is_member) {
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

$submitted = false;
$invalid = false;
$form_errors = array();

require_once('person-festival.inc.php');

/* Get any existing data from the database. */
$pf = new PersonFestival($g_person, $festival);

if (($_SERVER['REQUEST_METHOD'] == 'POST')) {
	$submitted = true;

	/* Validate all form fields. */
	$g_person->badgename = isset($_POST['badgename']) ? $_POST['badgename'] : '';

	$pf->clear_sessions();
	foreach ($festival->sessions as $session_group) {
		foreach ($session_group as $day) {
			foreach ($day as $sess) {
				if (isset($_POST[$sess['tag']])) {
					$pf->add_session($sess['id']);
				}
			}
		}
	}

	$pf->clear_flags();
	foreach ($festival->flags as $flag) {
		if (isset($_POST['flag_' . $flag['id']])) {
			$pf->add_flag($flag['id']);
		}
	}

	$pf->jobprefs = isset($_POST['jobprefs']) ? $_POST['jobprefs'] : '';
	$pf->quals = isset($_POST['quals']) ? $_POST['quals'] : '';
}

if ($submitted && !$invalid) {
	db_begin();
	$pf->save();
	$g_person->save();
	db_commit();
	echo $g_twig->render('thanks.html', array('festival'=>$festival));
} else {
	echo $g_twig->render('volunteer.html', array('festival'=>$festival, 'pf'=>$pf));
}
