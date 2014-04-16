<?php
/**
 * Created by PhpStorm.
 * User: 1
 * Date: 14.04.14
 * Time: 13:00
 */


include_once 'Rutracker.php';

$r = new Rutracker('<login>', '<pass>');

$r->findUser('<login>');

$r->search([
    "term" => "<term>",
]);