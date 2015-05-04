<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

if (!isset($_GET['format'])) {
	$format = 'json';
} elseif (preg_match('/^(json|csv)$/', $_GET['format'])) {
	$format = $_GET['format'];
}

if (!$f || !isset($_GET['report'])) {
	http_response_code(400);
	exit(0);
}

$fields = [];

switch ($_GET['report']) {
	case 'flag':
		if (!isset($_GET['flag']) || !preg_match('/^\d+$/', $_GET['flag'])) {
			http_response_code(400);
			exit(0);
		}
		$sth = db_prepare("SELECT p.id AS person_id, p.name AS name, p.badgename AS badgename, p.membership AS membership, p.email AS email FROM person p INNER JOIN person_festival ON p.id=person_festival.person INNER JOIN pf_flag USING(person, festival) WHERE person_festival.festival=? AND pf_flag.flag=?;");
		$sth->execute([$f->id, $_GET['flag']]);
		$fields = ["person_id", "name", "badgename", "membership", "email"];
		break;

	default:
		http_response_code(404);
		exit(0);
		break;
}


switch ($format) {
	case 'json':
		echo json_encode($sth->fetchAll(PDO::FETCH_OBJ));
		break;

	case 'csv':
		header("Content-Type: text/csv; charset=utf-8");
		header("Content-Disposition: attachment; filename=report.csv");
		$out = fopen('php://output', 'w');
		/* Include UTF-8 BOM - keeps Excel happier. */
		fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
		fputcsv($out, $fields);
		while ($row = $sth->fetch(PDO::FETCH_NUM)) {
			fputcsv($out, $row);
		}
		fclose($out);
		break;
}