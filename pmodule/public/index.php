<?php
require '../vendor/autoload.php';

use drt\dispatch;
use action\home;

define('ROOT_PATH', dirname(__FILE__) . '/');
define('APP_PATH', dirname(ROOT_PATH) );

echo "start  ";

//var_dump(class_exists('dispatch'));
//(new home)->index();

(new dispatch)->run();

echo "  end";


