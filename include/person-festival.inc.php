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
				/* Get flags, session and job data */
				$sth = db_prepare("SELECT flag FROM pf_flag WHERE person=? AND festival=?");
				$sth->execute(array($person->id, $festival->id));
				$this->data['flags'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

				$sth = db_prepare("SELECT session FROM pf_session WHERE person=? AND festival=?");
				$sth->execute(array($person->id, $festival->id));
				$this->data['sessions'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);

				$sth = db_prepare("SELECT job FROM pf_job WHERE person=? AND festival=?");
				$sth->execute(array($person->id, $festival->id));
				$this->data['jobs'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
			}
		}

		if (!$this->data) {
			/* No existing data */
			$this->data = [
				'flags' => [],
				'sessions' => [],
				'jobs' => [],
			];
		}
	}

	private function save_list($target, $list)
	{
		$sth = db_prepare("DELETE FROM pf_${target} WHERE person=? AND festival=?");
		$sth->execute(array($this->person->id, $this->festival->id));
		$sth = db_prepare("INSERT INTO pf_${target} (person, festival, ${target}) VALUES (?, ?, ?)");
		foreach ($list as $item) {
			$sth->execute(array($this->person->id, $this->festival->id, $item));
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

			/* Update all session, list and job allocations. */
			$this->save_list('session', $this->data['sessions']);
			$this->save_list('flag', $this->data['flags']);
			$this->save_list('job', $this->data['jobs']);

			$sth = db_prepare("UPDATE person_festival SET jobprefs=?, quals=?, notes=?, badge_printed=0 WHERE person=? AND festival=?");
			$sth->execute(array($this->data['jobprefs'], $this->data['quals'], $this->data['notes'], $this->person->id, $this->festival->id));

			db_commit();
		}
	}

	private function clear_list($list_name)
	{
		$this->data[$list_name] = array();
		$this->dirty[$list_name] = true;
	}

	public function add_to_list($list_name, $item)
	{
		if (!in_array($item, $this->data[$list_name])) {
			$this->data[$list_name][] = $item;
			$this->dirty[$list_name] = true;
		}
	}

	public function drop_from_list($list_name, $item)
	{
		if (($key = array_search($item, $this->data[$list_name])) !== false) {
			unset($this->data[$list_name][$key]);
			$this->dirty[$list_name] = true;
		}
	}

	public function clear_sessions()
	{
		$this->clear_list('sessions');
	}

	public function add_session($id)
	{
		$this->add_to_list('sessions', $id);
	}

	public function clear_flags()
	{
		$this->clear_list('flags');
	}

	public function add_flag($id)
	{
		$this->add_to_list('flags', $id);
	}

	public function clear_jobs()
	{
		$this->clear_list('jobs');
	}

	public function add_job($id)
	{
		$this->add_to_list('jobs', $id);
	}

	public function drop_job($id)
	{
		$this->drop_from_list('jobs', $id);
	}
}
