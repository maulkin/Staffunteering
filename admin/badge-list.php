<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_POST['festival']) || !preg_match('/^\S+$/', $_POST['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_POST['festival']);
}

if (!$f) {
	http_response_code(404);
	exit(0);
}

$sth = db_prepare("SELECT * FROM badge WHERE festival=?");
$sth->execute([$f->id]);

echo json_encode($sth->fetchAll(PDO::FETCH_OBJ));
