<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

if (!$f) {
	http_response_code(404);
	exit(0);
}

$sth = db_prepare("CALL festival_incoming(?)");
$sth->execute(array($f->id));

echo json_encode($sth->fetchAll(PDO::FETCH_OBJ));
