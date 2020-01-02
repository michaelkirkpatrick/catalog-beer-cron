<?php
// Define Root
define('ENVIRONMENT', 'staging');

// Autoload Classes
spl_autoload_register(function ($class_name) {
	require_once  'classes/' . $class_name . '.class.php';
});

// Set Timezone
date_default_timezone_set('America/Los_Angeles');

$usage = new Usage();
$usage->updateUsage(0, 0);
?>