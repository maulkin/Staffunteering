<?php

date_default_timezone_set('Europe/London');
mb_internal_encoding('UTF-8');
error_reporting(E_ALL | E_STRICT | E_DEPRECATED | E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);

require_once('../vendor/autoload.php');
set_include_path('../include');

require_once('server-config.inc.php');
require_once('db.inc.php');

require_once('user.inc.php');

$g_user = User::from_persist();

if (isset($admin_html)) {
	header("Content-Type: text/html; charset=utf-8");
} else {
	header("Content-Type: application/json; charset=utf-8");
	if (!$g_user) {
		http_response_code(401);
		header("WWW-Authenticate: FormBased");
		echo(json_encode(null));
		exit(0);
	}
}