<?php
$config = require_once('../../../config.php');
$connection = require_once('../db.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_GET['code'])){
   echo 'no code';
   exit();
}

$discord_code = $_GET['code'];

$payload = [
    'code'=> $discord_code,
    'client_id'=>'1040087531850575873',
    'client_secret'=>'s5cHSjqcE6DuxbanCo3eTXOSeH804l7y',
    'grant_type'=>'authorization_code',
    'redirect_uri'=>'https://vanity-rust.com/assets/php/discord/process-oauth.php',
    'scope'=>'identify%20guids',
];

$payload_string = http_build_query($payload);
$discord_token_url = "https://discordapp.com/api/oauth2/token";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $discord_token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$result = curl_exec($ch);

if(!$result){
   echo curl_error($ch);
}

$result = json_decode($result,true);
$access_token = $result['access_token'];

$discord_users_url = "https://discordapp.com/api/users/@me";
$header = array("Authorization: Bearer $access_token", "Content-Type: application/x-www-form-urlencoded");

$ch = curl_init();
curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
curl_setopt($ch, CURLOPT_URL, $discord_users_url);
curl_setopt($ch, CURLOPT_POST, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$result = curl_exec($ch);
$result = json_decode($result, true);

$query = "SELECT steamid FROM players WHERE steamid = '". $_GET['state'] . "'";
$qResult = mysqli_query($connection, $query);

if (mysqli_num_rows($qResult) > 0) {
    $updateDiscord = "UPDATE players SET discordid = '". $result['id'] ."' WHERE steamid = '". $_GET['state']. "'";
    if (!$connection->query($updateDiscord)) {
       echo "Error: " . $sql . "<br>" . $connection->error;
    }
}

header("Location: " . $config['SiteLink'] . "profile/" . $_GET['state'], true, 303);
exit();