<?php

class Record implements JsonSerializable {
	protected $data;
	protected $dirty;
	protected $id;

	public function __construct($id=null)
	{
		if ($id) {
			$sth = db_prepare("SELECT * FROM " . static::TABLE . " WHERE id=?");
			$sth->execute(array($id));
			$this->data = $sth->fetch(PDO::FETCH_ASSOC);
			if ($this->data) {
				$this->id = $id;
			}
		} else {
			$this->data = array();
		}
		$this->dirty = array();
	}

	public function jsonSerialize()
	{
		return $this->data;
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

	public function is_valid()
	{
		return $this->id > 0;
	}

	public function is_dirty()
	{
		return count($this->dirty) > 0;
	}

	public function __isset($field)
	{
		return array_key_exists($field, $this->data);
	}

	public function __get($field)
	{
		return array_key_exists($field, $this->data) ? $this->data[$field] : NULL;
	}

	public function __set($field, $value)
	{
		if ($field == 'id')
			return;

		if (!array_key_exists($field, $this->data) || ($value != $this->data[$field])) {
			$this->data[$field] = $value;
			$this->dirty[$field] = $value;
		}
	}
}
