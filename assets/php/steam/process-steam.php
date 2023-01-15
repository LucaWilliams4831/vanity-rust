<?php
    session_start();
    $config = require_once('../../../config.php');
    $connection = require_once('../db.php');
    
    $params = [
        'openid.assoc_handle' => $_GET['openid_assoc_handle'],
        'openid.signed'       => $_GET['openid_signed'],
        'openid.sig'          => $_GET['openid_sig'],
        'openid.ns'           => 'http://specs.openid.net/auth/2.0',
        'openid.mode'         => 'check_authentication',
    ];

    $signed = explode(',', $_GET['openid_signed']);
    
    foreach ($signed as $item) {
        $val = $_GET['openid_'.str_replace('.', '_', $item)];
        $params['openid.'.$item] = stripslashes($val);
    }

    $data = http_build_query($params);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Accept-language: en\r\n".
            "Content-type: application/x-www-form-urlencoded\r\n".
            'Content-Length: '.strlen($data)."\r\n",
            'content' => $data,
        ],
    ]);

    //get the data
    $result = file_get_contents('https://steamcommunity.com/openid/login', false, $context);

    if(preg_match("#is_valid\s*:\s*true#i", $result)){
        preg_match('#^https://steamcommunity.com/openid/id/([0-9]{17,25})#', $_GET['openid_claimed_id'], $matches);
        $steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;
        echo 'request has been validated by open id, returning the client id (steam id) of: ' . $steamID64;
    
    }else{
        echo 'error: unable to validate your request';
        exit();
    }

    $response = file_get_contents('https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.$config['SteamAPIKey'].'&steamids='.$steamID64);
    $response = json_decode($response,true);

    $userData = $response['response']['players'][0];
    $_SESSION['logged_in'] = true;
    $_SESSION['userData'] = [
        'steam_id'=>$userData['steamid'],
        'name'=>$userData['personaname'],
        'avatar'=>$userData['avatarmedium'],
    ];

    $query = "SELECT steamid FROM players WHERE steamid = '". $userData['steamid'] . "'";
    $result = mysqli_query($connection, $query);
    if (mysqli_num_rows($result) <= 0) {
        $createPlayer = "INSERT INTO players (steamid) VALUES ('" . $userData['steamid'] . "')";
        if (!$connection->query($createPlayer)) {
           echo "Error: " . $sql . "<br>" . $connection->error;
        }
    }

    $redirect_url = "../../../";
    header("Location: $redirect_url"); 
    exit();
?>