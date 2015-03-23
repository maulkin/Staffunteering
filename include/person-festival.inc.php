<?php

require_once('record.inc.php');

class PersonFestival extends Record {
	private $person;
	private $festival;

	public function __construct($person, $festival)
	{
		$this->person = $person;
		$this->festival = $festival;

		if ($person) {
			$sth = db_prepare("SELECT * FROM person_festival WHERE person=? AND festival=?");
			$sth->execute(array($person->id, $festival->id));
			$this->data = $sth->fetch(PDO::FETCH_ASSOC);

			if ($this->data) {
				/* Get flags and session data */
				$sth = db_prepare("SELECT flag FROM pf_flag WHERE person=? AND festival=?");
				$sth->execute(array($person->id, $festival->id));
				$this->data['flags'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

				$sth = db_prepare("SELECT session FROM pf_session WHERE person=? AND festival=?");
				$sth->execute(array($person->id, $festival->id));
				$this->data['sessions'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
			}
		}

		if (!$this->data) {
			/* No existing data */
			$this->data = [
				'flags' => [],
				'sessions' => [],
			];
		}
	}

	public function save()
	{
		if ($this->person && $this->festival) {
			db_begin();

			$sth = db_prepare("SELECT * FROM person_festival WHERE person=? AND festival=?");
			$sth->execute(array($this->person->id, $this->festival->id));
			if (!$sth->fetch()) {
				$sth = db_prepare("INSERT INTO person_festival (person, festival) VALUES (?, ?)");
				$sth->execute(array($this->person->id, $this->festival->id));
			}

			/* Update all sessions. */
			$sth = db_prepare("DELETE FROM pf_session WHERE person=? AND festival=?");
			$sth->execute(array($this->person->id, $this->festival->id));

			$sth = db_prepare("INSERT INTO pf_session (person, festival, session) VALUES (?, ?, ?)");
			foreach ($this->data['sessions'] as $session) {
				$sth->execute(array($this->person->id, $this->festival->id, $session));
			}

			/* Update all flags. */
			$sth = db_prepare("DELETE FROM pf_flag WHERE person=? AND festival=?");
			$sth->execute(array($this->person->id, $this->festival->id));
			$sth = db_prepare("INSERT INTO pf_flag (person, festival, flag) VALUES (?, ?, ?)");
			foreach ($this->data['flags'] as $flag) {
				$sth->execute(array($this->person->id, $this->festival->id, $flag));
			}

			$sth = db_prepare("UPDATE person_festival SET jobprefs=?, quals=?, notes=? WHERE person=? AND festival=?");
			$sth->execute(array($this->data['jobprefs'], $this->data['quals'], $this->data['notes'], $this->person->id, $this->festival->id));

			db_commit();
		}
	}

	public function clear_sessions()
	{
		$this->data['sessions'] = array();
		$this->dirty['sessions'] = true;
	}

	public function add_session($session_id)
	{
		if (!in_array($session_id, $this->data['sessions'])) {
			$this->data['sessions'][] = $session_id;
			$this->dirty['sessions'] = true;
		}
	}

	public function clear_flags()
	{
		$this->data['flags'] = array();
		$this->dirty['flags'] = true;
	}

	public function add_flag($flag_id)
	{
		if (!in_array($flag_id, $this->data['flags'])) {
			$this->data['flags'][] = $flag_id;
			$this->dirty['flags'] = true;
		}
	}
}
