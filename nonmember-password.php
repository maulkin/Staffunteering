<?php

require_once('header.inc.php');
require_once('person.inc.php');
require_once('mailer.inc.php');
require_once('token.inc.php');

$volunteer_url = ServerConfig::BASE_URL . 'volunteer.php';

if ($g_person) {
	header("Location: " . $volunteer_url, true, 302);
	exit(0);
}

$form = [];
$present_form = true;
$message = null;

/* If the form has been submitted check if the email address exists. */
if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['email'])) {
	$email = $_POST['email'];
	$form['email'] = $email;
	$invalid = false;

	if (!preg_match("/^\S+@\S+$/", $email)) {
		$form['email_err'] = "An email address is required";
		$invalid = true;
	} else {
		$sth = db_prepare("SELECT id FROM person WHERE email=? AND membership IS NULL");
		$sth->execute(array($email));
		$id = $sth->fetchColumn();
		if (!$id) {
			$form['email_err'] = "That email address is not in use on the system";
			$invalid = true;
		}
	}

	if (!$invalid) {
		$present_form = false;
		$data = array('id' => $id);

		/* Generate secret token and stick in database. 2 day lifetime. */
		$token = token_generate('pw_reset', $data, 48*60);

		/* Prepare email. */
		$body = $g_twig->render('nonmember-password-email.txt', array(
			'reset_url' => ServerConfig::SERVER_NAME . ServerConfig::BASE_URL . 'pr.php?' . $token,
		));
		$subject = "Cambridge Beer Festival volunteering password reset";

		mailer_send($email, $subject, $body);
		$message = "We've sent you an email with the next step for resetting your password.";
	}
}

if ($present_form) {
	echo $g_twig->render('nonmember-password.html', array(
		'form' => $form,
	));
} else {
	echo $g_twig->render('message.html', array(
		'message' => $message,
	));
}


