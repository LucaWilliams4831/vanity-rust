<?php

$config = require_once('../../../config.php');
if (isset($_GET['steam'])) {
   $discord_url = "https://discord.com/api/oauth2/authorize?client_id=1040087531850575873&redirect_uri=https%3A%2F%2Fvanity-rust.com%2Fassets%2Fphp%2Fdiscord%2Fprocess-oauth.php&response_type=code&scope=identify%20guilds&state=". $_GET['steam'];
   header("Location: $discord_url");
   exit();
} else {
   header("Location: " . $config['SiteLink'], true, 303);
   die();
}

?>