<?php

require_once('record.inc.php');
require_once('local_auth.inc.php');
require_once('cookie.inc.php');

class User extends Record {
	const TABLE = 'user';

	public function set_persist()
	{
		/* Set cookie to last 6 days. */
		idcookie_set($this->id, ServerConfig::ADMIN_COOKIE_NAME, ServerConfig::ADMIN_COOKIE_MAC_KEY, 6);
	}

	public static function remove_persist()
	{
		idcookie_clear(ServerConfig::ADMIN_COOKIE_NAME);
	}

	public static function from_persist()
	{
		$userid = idcookie_check(ServerConfig::ADMIN_COOKIE_NAME, ServerConfig::ADMIN_COOKIE_MAC_KEY);
		return $userid ? new User($userid) : null;
	}

	public function set_password($pw)
	{
		$this->__set('pwhash', local_gethash($pw));
		return true;
	}
}