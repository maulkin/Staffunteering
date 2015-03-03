<?php

require_once('header.inc.php');
require_once('auth.inc.php');

if (!$g_person || !$g_person->is_member()) {
	header("Location: " . ServerConfig::BASE_URL, true, 302);
	exit(1);
}

require_once('festival.inc.php');
$festival = Festival::current();

echo $g_twig->render('volunteer.html', array('festival'=>$festival));
