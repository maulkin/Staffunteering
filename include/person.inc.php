<?php

require_once('record.inc.php');
require_once('local_auth.inc.php');
require_once('cookie.inc.php');

class Person extends Record {
	const TABLE = 'person';

	public function is_member()
	{
		return array_key_exists('membership', $this->data) && ($this->data['membership'] > 0);
	}

	public function set_persist()
	{
		/* Set cookie to last 6 days. */
		idcookie_set($this->id, ServerConfig::LOGIN_COOKIE_NAME, ServerConfig::LOGIN_COOKIE_MAC_KEY, 6);
	}

	public function set_password($pw)
	{
		if ($this->is_member()) {
			return false;
		}
		$this->__set('pwhash', local_gethash($pw));
		return true;
	}

	public static function remove_persist()
	{
		idcookie_clear(ServerConfig::LOGIN_COOKIE_NAME);
	}

	public static function from_persist()
	{
		$userid = idcookie_check(ServerConfig::LOGIN_COOKIE_NAME, ServerConfig::LOGIN_COOKIE_MAC_KEY);
		return $userid ? new Person($userid) : null;
	}

	public static function from_member($membership_number, $create=false)
	{
		db_begin();
		$sth = db_prepare("SELECT id FROM person WHERE membership=?");
		$sth->execute(array($membership_number));
		$userid = $sth->fetchColumn();

		if (!$userid && $create) {
			$sth = db_prepare("INSERT INTO person (membership) VALUES (?)");
			$sth->execute(array($membership_number));
			$userid = db_last_insert_id();
		}
		db_commit();

		return $userid ? new Person($userid) : null;
	}
}
