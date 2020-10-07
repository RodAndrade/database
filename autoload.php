<?php 
	if(file_exists(__DIR__.'/config.php')){
		include __DIR__.'/config.php';

		if (DEBUG) {
			error_reporting(E_ALL & ~E_NOTICE);
			ini_set('display_errors', 'Off');
		} else {
			error_reporting(E_ALL & ~E_NOTICE);
			ini_set('display_errors', 'On');
		}
	
		include __DIR__.'/src/database.php';
	} else {
		die(json_encode([
			'error' => 'Please, check your config file'
		]));
	}
?>