<?php

class Record {
	protected $data;
	protected $dirty;
	protected $id;

	public function __construct($id=null)
	{
		if ($id) {
			$sth = db_prepare("SELECT * FROM " . static::TABLE . " WHERE id=?");
			$sth->execute(array($id));
			$this->data = $sth->fetch(PDO::FETCH_ASSOC);
			$this->id = $id;
		}
		$this->dirty = array();
	}

	public function save()
	{
		if (!count($this->dirty))
			return;

		$updates = array();
		foreach($this->dirty as $field=>$value) {
			$updates[] = $field . '=' . db_quote($value);
		}
		if ($this->id) {
			$sth = db_prepare("UPDATE " . static::TABLE . " SET " . implode(',', $updates) . " WHERE id=?");
			if ($sth->execute(array($this->id))) {
				$this->dirty = array();
				return true;
			}
		} else {
			if (db_query("INSERT INTO " . static::TABLE . " SET " . implode(',', $updates))) {
				$this->id = db_last_insert_id();
				$this->dirty = array();
				return true;
			}
		}
		return false;
	}

	public function is_dirty()
	{
		return count($this->dirty) > 0;
	}

	public function __get($field)
	{
		return array_key_exists($field, $this->data) ? $this->data['field'] : NULL;
	}

	public function __set($field, $value)
	{
		if (($field == 'id') || (!array_key_exists($field, $this->data)))
			return;

		if ($value != $this->data[$field]) {
			$this->data[$field] = $value;
			$this->dirty[$field] = $value;
		}
	}
}
