<?php

$admin_html = true;

require_once('admin-header.inc.php');

idcookie_clear(ServerConfig::ADMIN_COOKIE_NAME);

header("Location: " . ServerConfig::BASE_URL, true, 302);
