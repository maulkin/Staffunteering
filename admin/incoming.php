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

$sth = db_prepare("SELECT p.id AS person_id,p.name AS name,badgename,membership,jobprefs,quals,notes FROM person p INNER JOIN person_festival pf ON p.id=pf.person WHERE pf.festival=? AND pf.state='incoming' ORDER BY p.name;");
$sth->execute(array($f->id));

echo json_encode($sth->fetchAll(PDO::FETCH_OBJ));
