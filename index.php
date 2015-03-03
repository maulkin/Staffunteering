<?php

require_once('header.inc.php');
require_once('festival.inc.php');

$festival = Festival::current();

echo $g_twig->render('home.html', array('festival'=>$festival));
