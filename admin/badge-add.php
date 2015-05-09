<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

if (!isset($_POST['name']) || !strlen(trim($_POST['name']))) {
	http_response_code(400);
	exit(0);
}

if (!isset($_POST['job']) || !strlen(trim($_POST['job']))) {
	http_response_code(400);
	exit(0);
}

$name = trim($_POST['name']);
$job = trim($_POST['job']);

$sth = db_prepare("INSERT INTO badge (festival,name,job) VALUES (?, ?, ?)");
$sth->execute([$f->id, $name, $job]);

echo json_encode([
	"name"=>$name,
	"job"=>$job,
	"id"=>db_last_insert_id()
	]);
