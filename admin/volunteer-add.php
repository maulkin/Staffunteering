<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');
require_once('person.inc.php');
require_once('person-festival.inc.php');

$p = new Person();

if (isset($_POST['name'])) {
	$p->name = trim($_POST['name']);
}

if (isset($_POST['email']) && strlen(trim($_POST['email']))) {
	$p->email = trim($_POST['email']);
}

if (isset($_POST['address']) && strlen(trim($_POST['address']))) {
	$p->address = trim($_POST['address']);
}

if (isset($_POST['membership']) && preg_match("/^\d+$/", $_POST['membership'])) {
	$p->membership = trim($_POST['membership']);
}

if (isset($_POST['badgename'])) {
	$p->badgename = trim($_POST['badgename']);
	$p->approved_badgename = trim($_POST['badgename']);
}

if (!strlen($p->name)) {
	http_response_code(400);
	echo("Name must not be empty");
	exit(0);
}

if (!strlen($p->badgename)) {
	$p->badgename = $p->name;
	$p->approved_badgename = $p->name;
}

if ($p->email) {
	$sth = db_prepare("SELECT COUNT(*) FROM person WHERE email=?");
	$sth->execute(array($p->email));
	if ($sth->fetchColumn() > 0) {
		http_response_code(409);
		echo("Email already in use");
		exit(0);
	}
}

if ($p->membership) {
	$sth = db_prepare("SELECT COUNT(*) FROM person WHERE membership=?");
	$sth->execute(array($p->membership));
	if ($sth->fetchColumn() > 0) {
		http_response_code(409);
		echo("Member already registered");
		exit(0);
	}
}

$p->state = 'approved';

if (!$p->save()) {
	http_response_code(409);
	echo("Unexpected item in the badging area");
	exit(0);
}

/* Now sign them up immediately. */
$f = Festival::current();
$pf = new PersonFestival($p, $f);
$pf->state = 'approved';
$pf->save();

echo json_encode(true);
