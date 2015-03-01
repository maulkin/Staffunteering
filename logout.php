<?php

require_once('header.inc.php');
require_once('person.inc.php');

Person::remove_persist();

header("Location: " . ServerConfig::BASE_URL, true, 302);