<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_POST['person']) || !preg_match('/^\d+$/', $_POST['person'])) {
	http_response_code(400);
	exit(0);
}
$person = $_POST['person'];

if (!isset($_POST['festival']) || !preg_match('/^\S+$/', $_POST['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_POST['festival']);
}

if (!$f) {
	http_response_code(404);
	exit(0);
}


$sth = db_prepare("UPDATE person SET state='approved' WHERE id=?");
$sth->execute([$person]);

$sth = db_prepare("UPDATE person_festival SET state='approved' WHERE person=? AND festival=?");
$sth->execute(array($person, $f->id));

echo json_encode(true);
