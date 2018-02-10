# GrabGmail

GrabGmail is a PHP library that fetch email from given Gmail Account. You can fetch matching emails from "To" or "From" email address

Faker requires PHP >= 5.6

## Installation

```sh
composer install
```


## Basic Usage

<?php
// require the GrabGmail autoloader

require_once __DIR__ . '/vendor/autoload.php'; 

use GrabGmail\Gmail;

$obj = new Gmail();
$obj->email = [Your Gmail Address];
$obj->password = [Your Password];



/* Fetch matching emails from "From email ID" */
$obj->from = [Email Address];
$obj->getMessages();


/* Fetch matching emails from "To email ID" */
$obj->from = [Email Address];
$obj->getMessages();