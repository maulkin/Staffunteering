<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');
require_once('person.inc.php');
require_once('person-festival.inc.php');

if (!isset($_GET['person']) || !preg_match('/^\d+$/', $_GET['person'])) {
	http_response_code(400);
	exit(0);
}

$p = new Person($_GET['person']);

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

if (!$p || !$f) {
	http_response_code(404);
	exit(0);
}

$pf = new PersonFestival($p, $f);

/* Return specific elements. TODO: add more. */
$p_filter = [
	"id" => $p->id,
	"name" => $p->name,
	"email" => $p->email,
	"address" => $p->address
	];

echo json_encode([
	"person" => $p_filter,
	"sessions" => $pf->sessions,
	"flags" => $pf->flags,
	"jobs" => $pf->jobs
	]);
