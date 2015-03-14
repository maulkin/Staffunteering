<?php

require_once('record.inc.php');

class Festival extends Record {
	const TABLE = 'festival';

	public function __construct($id=null)
	{
		parent::__construct($id);
		if ($this->data) {
			$sth = db_prepare("SELECT flagname,id,description,comment FROM festival_flag INNER JOIN flag ON festival_flag.flag=flag.id WHERE festival_flag.festival=?");
			$sth->execute(array($id));
			$this->data['flags'] = array_map('reset', $sth->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC));

			$this->data['sessions'] = [
				'setup' => [],
				'open' => [],
				'takedown' => [],
				];
			$sth = db_prepare("SELECT * FROM session WHERE festival=?");
			$sth->execute(array($id));
			while ($session = $sth->fetch(PDO::FETCH_OBJ)) {
				$day = strftime('%Y-%m-%d', strtotime($session->start));
				if (!isset($this->data['sessions'][$session->sgroup][$day]))
					$this->data['sessions'][$session->sgroup][$day] = [];

				$this->data['sessions'][$session->sgroup][$day][] = [
					'id' => $session->id,
					'tag' => 'sess_' . $session->id,
					'start' => $session->start,
					'end' => $session->end,
					];
			}
		}
	}

	public static function from_tag($tag)
	{
		$sth = db_prepare("SELECT id FROM festival WHERE tag=?");
		$sth->execute(array($tag));
		$id = $sth->fetchColumn();

		return $id ? new Festival($id) : null;
	}

	public static function current()
	{
		return static::from_tag(ServerConfig::CURRENT_FESTIVAL);
	}
}
