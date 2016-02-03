<?php

require_once('b41.inc.php');

function token_generate($src, $data, $duration)
{
	token_purge();

	$token = b41_encode(openssl_random_pseudo_bytes(16));
	$data = json_encode($data);

	$sth = db_prepare("INSERT INTO token (token, src, data, expiry) VALUES (?, ?, ?, date_add(now(), interval ? minute))");
	$sth->execute(array($token, $src, $data, $duration));

	return $token;
}

function token_lookup($src, $token)
{
	if (!b41_check($token, 24))
		return NULL;

	$sth = db_prepare("SELECT data FROM token WHERE src=? AND token=? AND expiry > now()");
	$sth->execute(array($src, $token));
	$data = $sth->fetchColumn();

	if ($data)
		return json_decode($data);
	return NULL;
}

function token_delete($src, $token)
{
	$sth = db_prepare("DELETE FROM token WHERE src=? AND token=?");
	$sth->execute(array($src, $token));
}

function token_purge()
{
	$sth = db_prepare("DELETE FROM token WHERE expiry < now()");
	$sth->execute();
}
