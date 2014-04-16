<?php
/**
 * Created by #ROOT.
 * to contact me use skype neronmoon
 */


include_once 'Rutracker.php';

$r = new RutrackerAPI('<login>', '<pass>');

$r->findUser('<login>');

$r->search([
    "term" => "<term>",
]);