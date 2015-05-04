<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

if (isset($_GET['session']) && preg_match('/^\d+$/', $_GET['session'])) {
	$s = intval($_GET['session']);
} else {
	$s = null;
}

if (!$f || !$s) {
	http_response_code(404);
	exit(0);
}

$sth = db_prepare("SELECT p.id AS person_id,p.name AS name,badgename,membership FROM person p INNER JOIN person_festival pf ON p.id=pf.person INNER JOIN pf_session USING (person,festival) WHERE pf.festival=? AND pf_session.session=? AND pf.state='approved' ORDER BY p.name;");
$sth->execute(array($f->id, $s));

echo json_encode($sth->fetchAll(PDO::FETCH_OBJ));
