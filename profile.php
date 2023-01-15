<?php
	$config = require_once('config.php');
   $connection = require_once('./assets/php/db.php');

   require_once('assets/php/utilitys.php');
   require_once('assets/php/steam/steam-info.php');
   require_once('assets/php/notifications.php');

   $playerid = '';
   if (isset($_GET['player'])) {
		$playerid = $_GET['player'];
   }

   if ($playerid == '') {
      header("Location: " . $config['SiteLink'], true, 303);
      die();
   }

   $query = "SELECT * FROM players WHERE steamid = '$playerid'";
	$result = mysqli_query($connection, $query);
   $linkedAccounts = mysqli_fetch_assoc($result);

   if (mysqli_num_rows($result) <= 0) {
      header("Location: " . $config['SiteLink'], true, 303);
      exit();
   }

   $json = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $config['SteamAPIKey'] . "&steamids=" . $playerid);
   $response = json_decode($json,true);
   if (count($response['response']['players']) <= 0) {
      header("Location: " . $config['SiteLink'], true, 303);
      exit();
   }
   
   $player = $response['response']['players'][0];
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <base href="<?php echo $config['SiteLink'] ?>">
   <meta charset="utf-8" />
   <link rel="icon" href="./assets/favicon.ico" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />
   <meta name="theme-color" content="#000000" />
   <meta name="description" content="Vanity Rust is a Community focused Rust Server targeted for Clan PvP." />
   <link rel="stylesheet" href="./assets/fontawesome/css/all.min.css">
   <link rel="stylesheet" href="./assets/css/styles.css" />
   <title>Vanity Rust - Official Site</title>
</head>

<body>
   <div class="navbar-container">
      <img class="navbar-logo" src="./assets/images/VanityLogo.png" alt="" />
      <ul class="navbar-links">
         <li class='navbar-option'><a href="/">HOME</a></li>
         <li class='navbar-option'><a class="active" href="/">CLANS</a></li>
         <li class='navbar-option'><a href="https://store.vanity-rust.com/" target="#">SHOP</a></li>
         <?php 
            if ($steamid != '') {
               echo "<li class='navbar-option'>";
                  echo "<a href='/profile/". $steamid ."'>PROFILE</a>";
               echo "</li>";
            }
         ?>
         <li class='navbar-option'><a href="/">LEADERBOARD</a></li>
      </ul>
      <div class="navbar-profile-wrapper">
         <?php 
            $notificationIcon = $hasInvites ? './assets/images/notification-alert.png' : './assets/images/notification.png';
            if (isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
               echo "<div class='navbar-profile-container'>";
                  echo "<div class='navbar-notification-wrapper'>";
                     echo "<img id='notification' class='navbar-notification-icon' src='". $notificationIcon ."'></img>";
                     echo "<div class='navbar-notification-container'>";
                        if ($hasInvites) {
                           $index = 0;
                           foreach($invitesPlayer as $invite) {
                              echo "<div class='navbar-notification'>";
                                 echo "<h1>You have recieved a clan invite from <span>". $invite['cname'] ."</span>, would you like to join the clan?</h1>";
                                 echo "<form class='notification-buttons' method='post' action='". htmlspecialchars($_SERVER["PHP_SELF"]) . "'>";
                                    $buttonClass = 'notification-button approve';
                                    if (!empty($clan)) 
                                       $buttonClass .= ' disabled';
                                       
                                    echo "<button type='submit' name='accept' class='". $buttonClass ."' value='". $index ."'>Accept Invite</button>";
                                    echo "<button type='submit' name='reject' class='notification-button decline' value='". $index ."'>Reject Invite</button>";
                                 echo "</form>";
                              echo "</div>";
                              $index++;
                           }
                        } else {
                           echo "<h1>You do not have any invites.</h1>";
                        }
                     echo "</div>";
                  echo "</div>";
                  echo "<div class='navbar-profile-data'>";
                     echo "<div style='display: flex; align-items: center;'>";
                        echo "<img class='navbar-profile-avatar' src=". $avatar ."></img>";
                        echo "<h1>". $displayname ."</h1>";
                        echo "</div>";
                     echo "<div class='navbar-profile-options'>";
                        echo "<a class='navbar-profile-option logout' href='assets/php/steam/logout.php'>LOGOUT</a>";
                        echo "</div>";
                     echo "</div>";
               echo "</div>";
            } else {
               echo "<a class='navbar-login-button' href='assets/php/steam/init-steam.php'><i class='fa-solid fa-arrow-right-to-bracket'></i>LOGIN</a>";
            }
            ?>
      </div>
   </div>
   <main class="profile-homepage-container">
      <section class='profile-userinfo-wrapper'>
         <img class='profile-user-avatar' <?php echo "src='" . $player['avatarfull'] . "'" ?> />
         <div class='profile-userinfo-container'>
            <h1><?php echo $player['personaname'] ?></h1>
            <p><?php echo $player['steamid'] ?></p>
         </div>
      </section>

      <h1 class='clanspage-header'>Current <span>Clan</span></h1>
      <section class='profile-currentclan-container'>
         <?php
            $clanQuery = "SELECT * FROM clans WHERE members LIKE '%" . $playerid . "%'";
            $clanResult = mysqli_query($connection, $clanQuery);
            $clan = mysqli_fetch_assoc($clanResult);

            if (mysqli_num_rows($clanResult) <= 0) {
               echo "<img class='profile-currentclan-clanavatar' src='assets/images/noavatar.png'/>";
               echo "<div class='profile-currentclaninfo-container'>";
                  echo "<h1 class='profile-currentclan-clanname'>NO CLAN</h1>";
               echo "</div>";
            } else {
               echo "<img class='profile-currentclan-clanavatar' src='". $clan['avatar']  ."'/>";
               echo "<div class='profile-currentclaninfo-container'>";
                  echo "<h1 class='profile-currentclan-clanname'>". $clan['cname'] ."</h1>";
                  echo "<p class='profile-currentclan-clanbio'>". $clan['bio'] ."</p>";
               echo "</div>";
               echo "<a href='/clan/". strtolower(str_replace(' ', '-', $clan['cname'])) ."'>View Clan</a>";
            }
         ?>
      </section>

      <h1 class='clanspage-header'>Current <span>Accounts</span></h1>
      <section class='profile-linkedaccounts-container'>
         <a class='profile-account steam'
            <?php echo "href='https://steamcommunity.com/profiles/". $player['steamid'] . "'"?> target="_blank">
            <img class='profile-linked-background' src='assets/images/steam-background.png' />
            <?php
               echo "<img class='profile-linked-avatar' src='". $player['avatarmedium'] ."' />";
               echo "<div class='profile-linked-headings'>";
                  echo "<h1 class='profile-linked-name'>". $player['personaname'] ."</h1>";
                  echo "<p class='profile-linkedsteam-status'>LINKED</p>";
               echo "</div>";
            ?>
         </a>

         <?php
            $href = '';
            if ($playerid == $steamid) {
               $href = "href='assets/php/discord/init-oauth.php?steam=". $player['steamid'] ."'";
            }
            
            if (empty($linkedAccounts['discordid'])) {
               echo "<a class='profile-account discord'" . $href .">";
                  echo "<img class='profile-linked-background' src='assets/images/discord-background.png' />";
                  echo "<div class='profile-linked-headings'>";
                     echo "<h1 class='profile-linked-name'>N/A</h1>";
                     echo "<p class='profile-unlinkeddiscord-status'>NOT LINKED</p>";
                  echo "</div>";
               echo "</a>";
            } else {
               $discord_user_url = "https://discordapp.com/api/users/". $linkedAccounts['discordid'];
               $header = array("Authorization: Bot MTA0MDA4NzUzMTg1MDU3NTg3Mw.GIZFzf.n7kuhvmcM98Yq1ZK7ggb8ECsJh3kiA_611MuhA", "Content-Type: application/x-www-form-urlencoded");
               
               $ch = curl_init();
               curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
               curl_setopt($ch, CURLOPT_URL, $discord_user_url);
               curl_setopt($ch, CURLOPT_POST, false);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
               curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
               
               $result = curl_exec($ch);
               $result = json_decode($result, true);

               echo "<a class='profile-account discord' target='_blank' href='https://discord.com/users/". $linkedAccounts['discordid'] ."'>";
                  echo "<img class='profile-linked-background' src='assets/images/discord-background.png' />";
                  echo "<img class='profile-linked-avatar' src='https://cdn.discordapp.com/avatars/". $linkedAccounts['discordid']. "/" . $result['avatar'] ."' />";
                  echo "<div class='profile-linked-headings'>";
                     echo "<h1 class='profile-linked-name'>". $result['username'] ."</h1>";
                     echo "<p class='profile-linkeddiscord-status'>LINKED</p>";
                  echo "</div>";
               echo "</a>";
            }
         ?>
   </main>
</body>

</html>