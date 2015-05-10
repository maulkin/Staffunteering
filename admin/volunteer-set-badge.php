<?php

require_once('admin-header.inc.php');
require_once('person.inc.php');
require_once('festival.inc.php');
require_once('person-festival.inc.php');

if (!isset($_POST['person']) || !preg_match('/^\d+$/', $_POST['person'])) {
	http_response_code(400);
	exit(0);
}

if (!isset($_POST['name']) || !strlen(trim($_POST['name']))) {
	http_response_code(400);
	exit(0);
}

$f = Festival::current();
$p = new Person($_POST['person']);

if (!$p || !$f) {
	http_response_code(404);
	exit(0);
}

$p->approved_badgename = $_POST['name'];
$p->save();

$pf = new PersonFestival($p, $f);
$pf->badge_reprint();
$pf->save();

echo json_encode(true);
