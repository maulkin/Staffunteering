<?php

require_once('header.inc.php');

$festival = [
	"nonmembers" => false,
];

echo $g_twig->render('home.html', array('festival'=>$festival));
