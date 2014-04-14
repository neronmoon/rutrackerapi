<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 14.04.14
 * Time: 13:00
 */


include_once 'Rutracker.php';

$r = new Rutracker('neronmoon', '5588898');

//$r->findUser('neronmoon');
$r->search([
    "term" => "Пингвины мистера Поппера",
    "order_by" => "seeders"
]);