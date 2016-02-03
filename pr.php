<?php

require_once('header.inc.php');
require_once('person.inc.php');
require_once('token.inc.php');

$form = [];
$present_form = true;
$message = null;
$rawmessage = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$qs = $_SERVER['QUERY_STRING'];

	$data = token_lookup('pw_reset', $qs);
	if ($data) {
		/* Present form. */
		$form['token'] = $qs;
	} else {
		/* Expired or invalid token. */
		$message = "The password reset request is invalid or has expired.";
		$present_form = false;
	}
} elseif (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['token']) && isset($_POST['password']) && isset($_POST['password_c'])) {
	$valid = true;

	/* Check passwords match. */
	if ($_POST['password'] != $_POST['password_c']) {
		$form['password_err'] = "Passwords do not match";
		$valid = false;
	} elseif (!preg_match("/^\S{6,}$/", $_POST['password'])) {
		$form['password_err'] = "Password must be at least six characters";
		$valid = false;
	}

	if ($valid) {
		/* Form fields match. */
		$present_form = false;

		$data = token_lookup('pw_reset', $_POST['token']);

		if (!$data) {
			/* Expired or invalid token. */
			$message = "The password reset request is invalid or has expired.";
		} else {
			/* Success. */
			$person = new Person($data->id); /* TODO: missing person? */
			$person->set_password($_POST['password']);
			if ($person->save()) {
				$rawmessage = "<p>Password successfully updated. You can now <a href=\"nonmember.php\">login</a>.</p>";
			} else {
				$message = "Something went wrong updating your password. Whatever it was, trying again is unfortunately unlikely to help.";
			}

			token_delete('pw_reset', $_POST['token']);
		}
	}
}

if ($present_form) {
	echo $g_twig->render('nonmember-password-reset.html', array(
		'form' => $form,
	));
} else {
	echo $g_twig->render('message.html', array(
		'message' => $message,
		'rawmessage' => $rawmessage,
	));
}
