<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

$sth = db_prepare("SELECT COUNT(*) FROM person_festival WHERE badge_printed=0 AND festival=? AND state='approved'");
$sth->execute([$f->id]);

echo json_encode($sth->fetchColumn(0));
