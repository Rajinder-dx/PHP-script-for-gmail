<?php
require_once __DIR__ . '/vendor/autoload.php'; 
use GrabGmail\Gmail;

$obj = new Gmail();
$obj->email = "your_-email@gmail.com";
$obj->password = "password";
$obj->from = "from_email@gmail.com";
$obj->to = "to_email@gmail.com";
$obj->getMessages();

?>