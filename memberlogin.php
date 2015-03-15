<?php

require_once('header.inc.php');
require_once('camra_auth.inc.php');
require_once('person.inc.php');

$target_url = ServerConfig::BASE_URL . 'volunteer.php';

if ($g_person && $g_person->is_member()) {
	header("Location: " . $target_url, true, 302);
	exit(1);
}

$error_title = '';
$error_detail = '';
$present_form = true;

if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['number']) && isset($_POST['password'])) {
	$error_code = 0;
	$member = camra_authenticate($_POST['number'], $_POST['password'], $error_code);

	if (!$member) {
		$error_title = "Login failed";

		if ($error_code != 0) {
			$error_detail = "The service we use to authenticate you is not available.";
		} else {
			$error_detail = "Invalid membership number or password.";
		}
	} else {
		$present_form = false;
		$person = Person::from_member($_POST['number'], true);
		if ($person) {
			/* Save basic details from authentication system. */
			$person->name = $member->Forename . ' ' . $member->Surname;
			if (isset($member->EmailMain) && isset($member->ContactByEmail) && $member->ContactByEmail=='Y')
				$person->email = $member->EmailMain;
			$person->save();
			$person->set_persist();
		}
	}
}

if ($present_form) {
	echo $g_twig->render('memberlogin.html', array(
		'error_title' => $error_title,
		'error_detail' => $error_detail,
	));
} else {
	header("Location: " . $target_url, true, 302);
}
