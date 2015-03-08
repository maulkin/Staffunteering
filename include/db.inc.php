<?php

$g_db = new PDO (ServerConfig::DB_CONNECT_STRING, ServerConfig::DB_USER, ServerConfig::DB_PASSWD);
$g_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

$transaction_depth = 0;

function db_prepare($query)
{
	global $g_db;
	return $g_db->prepare($query);
}

function db_begin()
{
	global $g_db, $transaction_depth;

	if ($transaction_depth++ == 0)
		$g_db->beginTransaction();
}

function db_commit()
{
	global $g_db, $transaction_depth;

	if (--$transaction_depth == 0)
		$g_db->commit();
}

function db_rollback()
{
	global $g_db;
	$g_db->rollBack();
}

function db_last_insert_id()
{
	global $g_db;
	return $g_db->lastInsertId();
}

function db_quote($f)
{
	global $g_db;
	return $g_db->quote($f, PDO::PARAM_STR);
}
