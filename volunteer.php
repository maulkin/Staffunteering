<?php

require_once('header.inc.php');
require_once('auth.inc.php');

require_once('festival.inc.php');

$festival = Festival::current();

if (!$festival) {
	/* No current festival */
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

$is_member = $g_person ? $g_person->is_member() : false;

if (!$festival->nonmembers && !$is_member) {
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

$present_form = true;
$invalid = false;
$form_errors = array();

require_once('person-festival.inc.php');

/* Get any existing data from the database. */
$pf = new PersonFestival($g_person, $festival);

if (($_SERVER['REQUEST_METHOD'] == 'POST')) {
	/* Validate all form fields. */
	if (!$is_member) {
		if (isset($_POST['name']) && preg_match("/\S+/", $_POST['name'])) {
			$name = $_POST['name'];
		} else {
			$invalid = true;
			$form_errors['name'] = "Name is required";
		}

		if (isset($_POST['email']) && preg_match("/^\S+@\S+$/", $_POST['email'])) {
			$email = $_POST['email'];
		} else {
			$invalid = true;
			$form_errors['email'] = "Email is required";
		}

		if (isset($_POST['address']) && preg_match("/^\S+@\S+$/", $_POST['address'])) {
			$address = $_POST['address'];
		} else {
			$invalid = true;
			$form_errors['address'] = "Address is required";
		}
	}

	$g_person->badgename = isset($_POST['badgename']) ? $_POST['badgename'] : '';
	$jobs = isset($_POST['jobs']) ? $_POST['jobs'] : '';
	$qual = isset($_POST['qual']) ? $_POST['qual'] : '';

	foreach ($festival->sessions as $session_group) {
		foreach ($session_group as $day) {
			foreach ($day as $sess) {
				if (isset($_POST[$sess['tag']])) {
					$pf->add_session($sess['id']);
				}
			}
		}
	}
}

db_begin();
$pf->save();
$g_person->save();
db_commit();

if ($present_form) {
	echo $g_twig->render('volunteer.html', array('festival'=>$festival, 'pf'=>$pf));
}
