<?php

require_once('admin-header.inc.php');

$sth = db_query("SELECT id,name,parent FROM job");
echo json_encode(array_map('reset', $sth->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC)));
