<?php
	$config = require_once('config.php');
   $connection = require_once('./assets/php/db.php');
   require_once('assets/php/utilitys.php');
   require_once('assets/php/steam/steam-info.php');

   $clan = require_once('assets/php/getClan.php');
   if (!isset($_SESSION['logged_in']) && empty($_SESSION['logged_in']) || count($clan) > 0) {
      header("Location: " . $config['SiteLink'], true, 303);
      die();
   };

   $errors = [];
   $clanName = $clanBio = $clanColor = "";
   require_once('assets/php/notifications.php');

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['createclan'])) {
      $clanName = clean_input($_POST['cname']);
      $clanBio = clean_input($_POST['bio']);
      $clanColor = clean_input($_POST['color']);

      if (!empty($clanName)) {
         if (!preg_match("/^[a-zA-Z-' ]*$/", $clanName)) {
            $errors[] = "Clan name can only contain (A-Z & Space) Characters.";
         }

         $badWords = check_badwords(strtolower($clanName));
         if (!empty($badWords)) {
            $errors[] = "Clan name has a bad word found in it: " . $badWords;
         }

         $findClanSQL = "SELECT cname FROM clans WHERE cname = '" . $clanName . "'";
         $findClanData = mysqli_query($connection, $findClanSQL);
         $findClanCount = mysqli_num_rows($findClanData);
         
         if ($findClanCount > 0) {
            $errors[] = "There is currently already another clan with that name, please pick a new name and try again.";
         }
      } else {
         $errors[] = "Please enter a clan name.";
      }

      if (empty($clanBio)) {
         $errors[] = "Please enter a clan bio.";
      }

      if (!empty($clanColor)) {
         if (!preg_match("/^#([0-9a-f]{3}){1,2}$/i", $clanColor)) {
            $errors[] = "The clan color is not a valid hex color.";
         }
      } else {
         $errors[] = "Please enter a valid clan hex color.";
      }

      $fileDestination = "assets/images/test-avatar.png";
      if (!$_FILES['clanAvatarSelect']['name'] == "") {
         $file = $_FILES['clanAvatarSelect'];
         $fileName = $file['name'];
         $fileNameNew = time() . '_' . $file['name'];
         $fileTmpName = $file['tmp_name'];
         $fileSize = $file['size'];
         $fileError = $file['error'];
         $fileType = $file['type'];
   
         $fileExt = explode('.', $fileName);
         $fileActualExt = strtolower(end($fileExt));
         $allowed = array('jpg', 'jpeg', 'png');
         $fileDestination = 'uploads/' . $fileNameNew;
      
         if (in_array($fileActualExt, $allowed)) {
            if ($fileError === 0) {
               if ($fileSize < 1000000) {
                  if (!move_uploaded_file($fileTmpName, $_SERVER['DOCUMENT_ROOT']. '/' . $fileDestination)) {
                     $errors[] = "Could not upload your avatar at this time, please try again later.";
                  }
               } else {
                  $errors[] = "Your file is too big!";
               }
            } else {
               $errors[] = "There was an error uploading your avatar.";
            }
         } else {
            $errors[] = "You cannot upload files of this type!";
         }
      }

      if (empty($errors)) {
         $createClan = "INSERT INTO clans (cname, bio, color, avatar, ownerid, members, invites) VALUES ('" . $clanName . "','". $clanBio . "','". $clanColor . "','". $fileDestination . "','" . $steamid . "','" . "[" . $steamid . "]" . "','" . '[]' . "')";

         if ($connection->query($createClan) === TRUE) {
            header("Location: " . $config['SiteLink'], true, 303);
            die();
         } else {
            echo "Error: " . $sql . "<br>" . $connection->error;
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
   <main class="clans-create-wrapper">
      <h1 class="clanspage-header">
         CLAN <span>CREATION</span>
      </h1>

      <?php
         if (!empty($errors)) {
            echo "<div class='clans-create-error'>";
               echo "<h1>" . $errors[0] . "</h1>";
            echo "</div>";
         }
      ?>

      <form method="post" class="clans-create-container" enctype="multipart/form-data"
         action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
         <div class="clans-create-avatarsection">
            <img id="clanAvatar" src="./assets/images/test-avatar.png" class="clans-create-avatar" alt="" />
            <label>(Avatar size is 200px x 200px)</label>
            <input type="file" name="clanAvatarSelect" id="clanAvatarSelect" onchange="displayImage(this)"
               style="display: none;" accept=".png, .jpg, .jpeg" />
            <button type="button" class="clans-create-avatarupload" onclick="triggerClick()">SELECT AVATAR</button>
         </div>
         <div class="clans-create-infosection">
            <div class="clans-create-header">
               <label>
                  CLAN NAME <span>REQUIRED</span>
               </label>
               <input type="text" name="cname" value="<?php echo htmlspecialchars($clanName) ?>" placeholder="Vanity" />
            </div>
            <div class="clans-create-header">
               <label>
                  CLAN BIO <span>REQUIRED</span>
               </label>
               <textarea rows="6" cols="35" type="text" name="bio"
                  placeholder="Lorem ispum dolor sit amet"><?php echo htmlspecialchars($clanBio)?></textarea>
            </div>
            <div class="clans-create-header">
               <label>CLAN COLOR <span>REQUIRED</span></label>
               <input class="color-selector" type="text" name="color" value="<?php echo htmlspecialchars($clanColor) ?>"
                  placeholder="#ffffff" />
            </div>
            <input type="submit" name="createclan" class="clans-create-sumbitclan" value="SUBMIT CLAN" />
         </div>
      </form>
   </main>

   <script type="text/javascript">
   function triggerClick() {
      document.querySelector('#clanAvatarSelect').click();
   }

   function displayImage(input) {
      if (input.files && input.files[0]) {
         var reader = new FileReader();

         reader.onload = function(e) {
            document.querySelector('#clanAvatar').setAttribute('src', e.target.result);
         }

         reader.readAsDataURL(input.files[0]);
      }
   }
   </script>
</body>