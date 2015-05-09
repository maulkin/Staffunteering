<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');
require_once('person.inc.php');
require_once('person-festival.inc.php');

if (!isset($_POST['festival']) || !preg_match('/^\S+$/', $_POST['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_POST['festival']);
}

if (!$f) {
	http_response_code(404);
	exit(0);
}

if (isset($_POST['person']) && preg_match('/^\d+$/', $_POST['person'])) {
	/* Volunteer badge. */
	$p = new Person($_POST['person']);

	if ($p->is_valid()) {
		$pf = new PersonFestival($p, $f);

		$pf->badge_reprint();
		$pf->save();
	} else {
		http_response_code(404);
		exit(0);
	}
} elseif (isset($_POST['badge']) && preg_match('/^\d+$/', $_POST['badge'])) {
	/* Custom badge. */
	$sth = db_prepare("UPDATE badge SET badge_printed=0 WHERE id=?");
	$sth->execute([$_POST['badge']]);
	if (!$sth->rowCount()) {
		http_response_code(404);
		exit(0);
	}
}

echo json_encode(true);
