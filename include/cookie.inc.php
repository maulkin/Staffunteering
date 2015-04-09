<?php

/* Store an id value in a cookie. */
function idcookie_set($id, $name, $key, $expiry_days)
{
	$expiration = time() + 86400*$expiry_days;
	$data = sprintf('%08x.%08x.%08x', mt_rand(), $id, $expiration);
	$mac = hash_hmac('sha256', $data, $key);

	setcookie($name, $data . '.' . $mac, $expiration, ServerConfig::BASE_URL);
}

function idcookie_clear($name)
{
	setcookie($name, '', time() - 86400, ServerConfig::BASE_URL);
}

function idcookie_check($name, $key)
{
	if (!array_key_exists($name, $_COOKIE))
		return null;

	$cookie = $_COOKIE[$name];
	if (strlen($cookie) != 91)
		return null;

	$data = substr($cookie, 0, 26);
	$cmac = substr($cookie, 27, 64);
	$mac = hash_hmac('sha256', $data, $key);
	if ($mac != $cmac)
		return null;

	/* Get bits and check expiration time. */
	if (sscanf($data, '%08x.%08x.%08x', $junk, $id, $expiration) != 3)
		return null;

	if ($expiration < time())
		return null;

	return $id;
}