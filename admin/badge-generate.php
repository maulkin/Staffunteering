<?php

require_once('admin-header.inc.php');
require_once('festival.inc.php');

if (!isset($_GET['festival']) || !preg_match('/^\S+$/', $_GET['festival'])) {
	$f = Festival::current();
} else {
	$f = Festival::from_tag($_GET['festival']);
}

/* Does the logo image exist? */
if (!stat("../logos/{$f->tag}.png")) {
	http_response_code(404);
	exit(0);
}

$badge_set = strftime('%Y%m%d-%H%M%S-') . sprintf("%05x", (microtime(true)*1000000)%1000000);

/* Get volunteer list in to a suitable format. */
db_begin();
$sth = db_prepare("UPDATE person_festival SET badge_set=? WHERE badge_printed=0 AND festival=? AND state='approved'");
$sth->execute([$badge_set, $f->id]);
if ($sth->rowCount() == 0) {
	http_response_code(204);
	exit(0);
}

$sth = db_prepare("SELECT person.name AS real_name, person.badgename AS badge_name, job.double_sided AS double_sided, job.name AS job FROM person INNER JOIN person_festival pf ON person.id=pf.person LEFT JOIN pf_job USING (person,festival) LEFT JOIN job ON pf_job.job=job.id WHERE pf.badge_set=? ORDER BY job.double_sided DESC,person.badgename");
$sth->execute([$badge_set]);

$badge_list = tempnam(sys_get_temp_dir(), "volcsv");
$pdf = tempnam(sys_get_temp_dir(), "volpdf");

$badge_file = fopen($badge_list, 'w');
while ($entry = $sth->fetch(PDO::FETCH_OBJ)) {
	$row = [$entry->badge_name, NULL, $entry->job];
	if ($entry->double_sided) {
		$row[1] = $entry->real_name;
	}
	if (!$entry->job) {
		$row[2] = "Volunteer";
	}
	fputcsv($badge_file, $row);
}
fclose($badge_file);

exec("/usr/bin/env python ../tools/badgegen.py --festival-name=\"{$f->name}\" --festival-logo=../logos/{$f->tag}.png --staff-format=csv $badge_list $pdf");

unlink($badge_list);

header("Content-type: application/pdf");
header("Content-Disposition: attachment; filename=badges-{$badge_set}.pdf");
readfile($pdf);
unlink($pdf);

$sth = db_prepare("UPDATE person_festival SET badge_printed=1 WHERE badge_set=?");
$sth->execute([$badge_set]);
db_commit();

