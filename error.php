<?php
	$config = require_once('config.php');
   header("Location: " . $config['SiteLink'], true, 303);
   die();
?>