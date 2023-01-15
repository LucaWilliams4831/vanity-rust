<?php
	$config = require_once('config.php');
   $connection = require_once('assets/php/db.php');
   require_once('assets/php/utilitys.php');
   require_once('assets/php/steam/steam-info.php');

   if (isset($_GET['clan'])) {
		$clanname = str_replace('-', ' ', $_GET['clan']);
	} else {
      header("Location: " . $config['SiteLink'], true, 303);
      exit();
   }

   $clanQuery = "SELECT * FROM clans WHERE cname = '$clanname'";
	$clanData = mysqli_query($connection, $clanQuery);

   if (mysqli_num_rows($clanData) <= 0) {
      header("Location: " . $config['SiteLink'], true, 303);
      exit();
   }
   
   $clan = mysqli_fetch_assoc($clanData);

   $isClanOwner = ($clan['ownerid'] == $steamid && $clan['ownerid'] != "") ? true : false;
   $isClanCoOwner = ($clan['coownerid'] == $steamid && $clan['coownerid'] != "") ? true : false;

   $members = strToArray($clan['members']);
   $invites = strToArray($clan['invites']);
   if (empty($invites)) {
      $invites = [];
   }
   
   $inviteID = "";
   $errors = [];
   require_once('assets/php/notifications.php');
   
   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendinvite'])) {
      $inviteID = clean_input($_POST['steam-member-id']);

      if (empty($inviteID)) {
         $errors[] = "Steam ID field cannot be left empty.";
      }

      if (!is_numeric($inviteID)) {
         $errors[] = "Please input a valid steam id in the input field.";
      }

      if (strlen($inviteID) < 17 || strlen($inviteID) > 17) {
         $errors[] = "The Steam ID must be 17 digits long.";
      }

      if (in_array($inviteID, $invites)) {
         $errors[] = "This player has already been invited";
      }

      if (in_array($inviteID, $members)) {
         $errors[] = "This player is already apart of the clan.";
      }

      if (!$isClanOwner && !$isClanCoOwner) {
         $errors[] = "You do not have permission to invite people to the clan.";
      }

      if (empty($errors)) {
         $invites[] = $inviteID;
         $inviteMember = "UPDATE clans SET invites = '" . buildStringArray($invites) . "' WHERE cname = '" . $clan['cname'] . "'";

         if ($connection->query($inviteMember) === TRUE) {
            header("Refresh: 0");
            die();
         } else {
            $errors[] = $connection->error;
         }
      }
   }

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['revokeinvite'])) {
      if ($isClanOwner || $isClanCoOwner) {
         $inviteMember = "UPDATE clans SET invites = '" . buildStringArray(array_splice($invites, 1, 1)) . "' WHERE cname = '" . $clan['cname'] . "'";

         if ($connection->query($inviteMember) === TRUE) {
            header("Refresh: 0", true, 301);
            die();
         } else {
            $errors[] = $connection->error;
         }
      }
   }

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['leaveclan'])) {
      $leaveQuery = "UPDATE clans SET members = '" . buildStringArray(array_diff($members, array($steamid))) . "' WHERE cname = '" . $clan['cname'] . "'";

      if ($connection->query($leaveQuery) === TRUE) {
         
         if ($isClanCoOwner) {
            $removeCoOwner = "UPDATE clans SET coownerid = '' WHERE cname = '" . $clan['cname'] . "'";
            if ($connection->query($removeCoOwner) === TRUE) {
               header("Location: " . $config['SiteLink'], true, 303);
               die();
            } else {
               $errors[] = $connection->error;
            }
         } else {
            header("Location: " . $config['SiteLink'], true, 303);
            die();
         }
      } else {
         $errors[] = $connection->error;
      }
   }

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['deleteclan'])) {
      if ($isClanOwner || $isClanCoOwner) {
         $deleteQuery = "DELETE FROM clans WHERE cname = '" . $clan['cname'] . "'";

         if ($connection->query($deleteQuery) === TRUE) {
            header("Location: " . $config['SiteLink'], true, 303);
            die();
         } else {
            $errors[] = $connection->error;
         }
      }
   }
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
   <main class="clans-view-wrapper">
      <img class="clans-view-avatar" src='<?php echo $config['SiteLink'] . $clan['avatar'] ?>' alt="" />
      <h1 class="clanspage-header"><?php echo $clan['cname'] ?></h1>
      <h1 class="clans-view-bio"><?php echo $clan['bio'] ?></h1>
      <section class="clans-view-container">
         <div class="clans-view-members-header">
            <h1>10 WIPE PASSES REMAINING</h1>
         </div>
         <div class="clans-view-members-container">
            <?php 
               $json = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $config['SteamAPIKey'] . "&steamids=" . stripStringArray($clan['members']));
               $steamMembers = json_decode($json,true);
               
               foreach (sortArray($steamMembers['response']['players'], $members) as $member) {
                  echo "<div class='clans-view-member'>";
                     echo "<div class='clans-view-membername'>";
                        echo "<img class='clans-view-memberavatar' src='" . $member['avatar'] . "' alt='' />";
                        echo "<h1><a href='/profile/" . $member['steamid'] . "'>". $member['personaname'] . "</a></h1>";
                        echo "<div class='clans-view-membericons'>";
                           if ($member['steamid'] == $clan['ownerid']) {
                              echo "<img class='clans-view-icon' src='assets/images/goldcrown.png' alt='' />";
                           }

                           if ($clan['coownerid'] != '' && $member['steamid'] == $clan['coownerid']) {
                              echo "<img class='clans-view-icon' src='assets/images/silvercrown.png' alt='' />";
                           }
                        echo "</div>";
                     echo "</div>";
                     echo "<a class='clans-view-steamprofile' href='https://steamcommunity.com/profiles/" . $member['steamid'] . "' target='_blank' rel='noreferrer'>
                        STEAM PROFILE
                     </a>";
                  echo "</div>";
               }
            ?>
         </div>
         <?php 
            if ($isClanOwner || $isClanCoOwner) {
               echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . '?'. str_replace('+', '%20', http_build_query($_GET)) . "' class='clans-view-invitelist-container'>";
                  echo "<h1 class='clans-view-invitelist-header'>PENDING INVITES</h1>";
                  echo "<div class='clans-view-invitelist'>";
                     if (stripStringArray($clan['invites']) != "") { 
                        $json = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $config['SteamAPIKey'] . "&steamids=" . stripStringArray($clan['invites']));
                        $inviteSteams = json_decode($json, true);
                        
                        foreach (sortArray($inviteSteams['response']['players'], $invites) as $invite) {
                           echo "<div class='clans-view-member'>";
                              echo "<div class='clans-view-membername'>";
                                 echo "<img class='clans-view-memberavatar' src='" . $invite['avatar'] . "' alt='' />";
                                 echo "<h1>" . $invite['personaname'] . "</h1>";
                                 echo "<div class='clans-view-membericons'>";
                                    if ($invite['steamid'] == $clan['ownerid']) {
                                       echo "<img class='clans-view-icon' src='assets/images/goldcrown.png' alt='' />";
                                    }
         
                                    if ($clan['coownerid'] != '' && $invite['steamid'] == $clan['coownerid']) {
                                       echo "<img class='clans-view-icon' src='assets/images/silvercrown.png' alt='' />";
                                    }
                                 echo "</div>";
                              echo "</div>";
                              echo "<input type='submit' name='revokeinvite' value='REVOKE INVITE' class='clans-view-revokeinvite'/>";
                           echo "</div>";
                        }
                     } else { 
                        echo "<h1 class='clans-view-noinvs'>No pending invites right now.</h1>";
                     }
                  echo "</div>";
               echo "</form>";

               echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . '?'. str_replace('+', '%20', http_build_query($_GET)) . "'class='clans-view-invite-container'>";
                  echo "<div class='clan-view-inviteheader'>";
                     echo "<h1>INVITE <span>MEMBERS</span></h1>";
                     echo "<input type='submit' name='sendinvite' value='SEND INVITE' />";
                  echo "</div>";
                  if (empty($errors)) { echo "<div class='clan-view-invitesection'>"; } else { echo "<div class='clan-view-invitesection error'>"; }
                     echo "<input type='number' name='steam-member-id' placeholder='Enter a players steam id...' maxlength='17' value='" . $inviteID . "'/>";
                     if (!empty($errors)) { echo "<h1 class='clan-view-error'>" . $errors[0] . "</h1>"; }
                  echo "</div>";
               echo "</form>";
            }

            if ($isClanOwner) {
               echo "<form class='clan-view-deletecontainer' method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . '?'. str_replace('+', '%20', http_build_query($_GET)) . "'>";
                  echo "<button class='clan-delete-button' type='submit' name='deleteclan'>DISBAND CLAN</button>";
               echo "</form>";
            } else if (in_array($steamid, $members) && !$isClanOwner) {
               echo "<form class='clan-view-deletecontainer' method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . '?'. str_replace('+', '%20', http_build_query($_GET)) . "'>";
                  echo "<button class='clan-delete-button' type='submit' name='leaveclan'>LEAVE CLAN</button>";
               echo "</form>";
            }
         ?>
      </section>
   </main>
</body>

</html>