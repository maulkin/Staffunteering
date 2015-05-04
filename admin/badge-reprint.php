<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');
require_once('person.inc.php');
require_once('person-festival.inc.php');

if (!isset($_POST['person']) || !preg_match('/^\d+$/', $_POST['person'])) {
	http_response_code(400);
	exit(0);
}

$p = new Person($_POST['person']);

if (!isset($_POST['festival']) || !preg_match('/^\S+$/', $_POST['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_POST['festival']);
}

if (!$p || !$f) {
	http_response_code(404);
	exit(0);
}

$pf = new PersonFestival($p, $f);

$pf->badge_reprint();
$pf->save();

echo json_encode(true);
