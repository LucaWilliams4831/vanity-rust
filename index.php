<?php
	$config = require_once('config.php');
   $connection = require_once('./assets/php/db.php');
   require_once('assets/php/utilitys.php');
   require_once('assets/php/steam/steam-info.php');
   
   $search = "";
   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
      $search = clean_input($_POST['search']);
   }

	$clanQuery = "SELECT * FROM clans WHERE cname LIKE '%" .$search. "%'";
	$clanData = mysqli_query($connection, $clanQuery);
	$clanResult = mysqli_num_rows($clanData);
   
   $clan = require_once('assets/php/getClan.php');
   require_once('assets/php/notifications.php');
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
   <main class="clans-homepage-container">
      <h1 class="clanspage-header">
         VANITY <span>CLANS</span>
      </h1>
      <div class='server-select-container'>
         <div class='server left'><img src='./assets/images/usflag.png' /> US MAIN</div>
         <div class='server center disabled'><img src='./assets/images/usflag.png' /> US 10x</div>
         <div class='server right disabled'><img src='./assets/images/euflag.png' />EU MAIN</div>
      </div>
      <form method='post' action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="clans-homepage-actions">
         <div class="clans-homepage-searchbox">
            <i class='clans-homepage-searchicon fa-solid fa-search'></i>
            <input class="clans-homepage-search" placeholder="Clan Name..." name='search'
               value="<?php echo $search ?>" />
         </div>
         <div class="clans-homepage-buttons">
            <?php 
               if (isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])) {
                  if (!empty($myClan)) {
                     echo "<a href='/clan/". strtolower(str_replace(' ', '-', $myClan['cname'])) . "' class='clans-homepage-linkbutton myclan'>
                        MY CLAN
                     </a>";
                  } else {
                     echo "<a href='/createclan' class='clans-homepage-linkbutton createclan'>
                        CREATE CLAN
                     </a>";
                  }
               }
            ?>
         </div>
      </form>
      <div class="clans-homepage-clans">
         <?php
				if ($clanResult > 0) {
               $index = 0;
               $classname = 'clans-homepage-clan left';
					while($row = mysqli_fetch_assoc($clanData)) {
                  if ($index > 0) 
                     $classname = 'clans-homepage-clan';
                     
						echo "<a href='" . $config['SiteLink'] . "clan/" . strtolower(str_replace(' ', '-', $row['cname'])) . "' class='" . $classname . "'>";
							echo "<img class='clans-homepage-clan-avatar' src='" . $config['SiteLink'] . $row['avatar'] ."' alt='' />";
							echo "<h1>" . $row['cname'] . "</h1>";
						echo "</a>";

                  $index++;
					}
				} else {
               echo "<h1>There are currently no active clans.</h1>";
            }
			?>
      </div>
   </main>
</body>

</html>