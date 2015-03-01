<?php

require_once('record.inc.php');

class Person extends Record {
	const TABLE = 'person';

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
