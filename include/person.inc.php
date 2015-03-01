<?php

require_once('record.inc.php');

class Person extends Record {
	const TABLE = 'person';

	public function is_member()
	{
		return $this->data['membership'] > 0;
	}

	public function set_persist()
	{
		$expiration = time() + 86400*42; /* 6 week cookie expiration period. */
		$data = sprintf('%08x.%08x.%08x', mt_rand(), $this->id, $expiration);
		$mac = hash_hmac('sha256', $data, ServerConfig::LOGIN_COOKIE_MAC_KEY);

		setcookie(ServerConfig::LOGIN_COOKIE_NAME, $data . '.' . $mac, $expiration, ServerConfig::BASE_URL);
	}

	public static function remove_persist()
	{
		setcookie(ServerConfig::LOGIN_COOKIE_NAME, '', time() - 86400, ServerConfig::BASE_URL);
	}

	public static function from_persist()
	{
		if (!array_key_exists(ServerConfig::LOGIN_COOKIE_NAME, $_COOKIE))
			return null;

		$cookie = $_COOKIE[ServerConfig::LOGIN_COOKIE_NAME];
		if (strlen($cookie) != 91)
			return null;

		$data = substr($cookie, 0, 26);
		$cmac = substr($cookie, 27, 64);
		$mac = hash_hmac('sha256', $data, ServerConfig::LOGIN_COOKIE_MAC_KEY);
		if ($mac != $cmac)
			return null;

		/* Get bits and check expiration time. */
		if (sscanf($data, '%08x.%08x.%08x', $junk, $userid, $expiration) != 3)
			return null;

		if ($expiration < time())
			return null;

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
