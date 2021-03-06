<?php

date_default_timezone_set('Europe/London');
mb_internal_encoding('UTF-8');
error_reporting(E_ALL | E_STRICT | E_DEPRECATED | E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);

require_once('vendor/autoload.php');
set_include_path('./include');

require_once('server-config.inc.php');
require_once('db.inc.php');

$g_twig = new Twig_Environment(new Twig_Loader_Filesystem('templates'));

require_once('person.inc.php');
require_once('festival.inc.php');

$g_person = Person::from_persist();
$g_festival = Festival::current();

$g_twig->addGlobal('person', $g_person);
$g_twig->addGlobal('festival', $g_festival);

if (ServerConfig::SERVER_NAME) {
	$g_twig->addGlobal('baseurl', ServerConfig::SERVER_NAME . ServerConfig::BASE_URL);
}

header("Content-Type: text/html; charset=utf-8");
