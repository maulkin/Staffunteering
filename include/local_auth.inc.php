<?php

function local_authenticate($email, $password)
{
	/* Does the user exist? */
	$sth = db_prepare("SELECT id,pwhash FROM person WHERE membership IS null AND email=?");
	$sth->execute(array($email));
	$p = $sth->fetch(PDO::FETCH_OBJ);

	if ($p) {
		$test = crypt($password, $p->pwhash);
		return ($test == $p->pwhash) ? $p->id : null;
	} else {
		/* Avoid timing shenanigans, but ignore string comparison time here. */
		crypt($password, ServerConfig::LOCAL_AUTH_DUMMY_HASH);
		return null;
	}
}

function local_gethash($password)
{
	/* Create salt as required by crypt */
	if (function_exists('mcrypt_create_iv')) {
		$salt = $mcrypt_create_iv(16);
	} elseif (function_exists('openssl_random_pseudo_bytes')) {
		$salt = openssl_random_pseudo_bytes(16);
	}

	$salt = strtr(base64_encode($salt), '+', '.');
	$salt = '$2y$10$' . $salt;

	return crypt($password, $salt);
}
