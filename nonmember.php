<?php

require_once('header.inc.php');
require_once('person.inc.php');

require_once('local_auth.inc.php');

$volunteer_url = ServerConfig::BASE_URL . 'volunteer.php';

if ($g_person) {
	header("Location: " . $volunteer_url, true, 302);
	exit(0);
}

$error_title = '';
$error_detail = '';
$present_form = true;


if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['newvolunteer'])) {
	$person = new Person();
	$newform = [];
	$invalid = false;

	/* New volunteer sign up */
	if (isset($_POST['name'])) {
		$newform['name'] = $_POST['name'];
		$person->name = trim($_POST['name']);
	}
	if (isset($_POST['email'])) {
		$newform['email'] = $_POST['email'];
		$person->email = trim($_POST['email']);
	}
	if (isset($_POST['address'])) {
		$newform['address'] = $_POST['address'];
		$person->address = trim($_POST['address']);
	}

	/* Now validate. */
	if (!strlen($person->name)) {
		$newform['name_err'] = "A name is required";
		$invalid = true;
	}
	if (!preg_match("/^\S+@\S+$/", $person->email)) {
		$newform['email_err'] = "An email address is required";
		$invalid = true;
	}
	if (!strlen($person->address)) {
		$newform['address_err'] = "An address is required";
		$invalid = true;
	}

	if (isset($_POST['password']) && isset($_POST['password_c'])) {
		if ($_POST['password'] != $_POST['password_c']) {
			$newform['password_err'] = "Passwords do not match";
			$invalid = true;
		} elseif (strlen($_POST['password']) < 6) {
			$newform['password_err'] = "Password must be at least 6 characters";
			$invalid = true;
		}
	} else {
		$newform['password_err'] = "A password is required";
	}

	if (!$invalid) {
		/* Is this email already in use? */
		$sth = db_prepare("SELECT COUNT(*) FROM person WHERE email=?");
		$sth->execute(array($person->email));
		if ($sth->fetchColumn() > 0) {
			$newform['email_err'] = "That email address is already in use on the system";
			$invalid = true;
		}
	}

	if (!$invalid) {
		$present_form = false;
		$person->set_password($_POST['password']);
		if ($person->save()) {
			$person->set_persist();
		} else {
			/* This is *probably* a duplicate that we raced with. */
			$newform['overall_err'] = "Failed to create volunteer";
		}
	}
} elseif (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['email']) && isset($_POST['password'])) {
	/* Login attempt. */
	$person_id = local_authenticate($_POST['email'], $_POST['password']);

	$error_detail = "Invalid email address or password";

	if ($person_id) {
		$person = new Person($person_id);
		if ($person->is_valid()) {
			$person->set_persist();
			$present_form = false;
		}
	}
}

if ($present_form) {
	echo $g_twig->render('nonmember.html', array(
		'error_detail' => $error_detail,
		'newform' => isset($newform) ? $newform : null,
	));
} else {
	header("Location: " . $volunteer_url, true, 302);
}
