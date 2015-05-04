<?php

require_once('../include/local_auth.inc.php');

if ($argc != 2)
	exit(0);

echo local_gethash($argv[1]);
echo "\n";

