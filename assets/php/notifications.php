<?php
   $invitesPlayerQuery = "SELECT cname, members, invites FROM clans WHERE invites LIKE '%" . $steamid . "%'";
   $invitesData = mysqli_query($connection, $invitesPlayerQuery);
   $invitesPlayer = mysqli_fetch_all($invitesData, MYSQLI_ASSOC);
   $hasInvites = mysqli_num_rows($invitesData);
   
   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept'])) {
      $inClanQuery = "SELECT * FROM clans WHERE members LIKE '%" . $steamid . "%'";
      $inClanResult = mysqli_query($connection, $inClanQuery);

      if (mysqli_num_rows($inClanResult) <= 0) {
         $invitedClan = $invitesPlayer[$_POST['accept']];
         $members = strToArray($invitedClan['members']);
         $members[] = $steamid;
   
         $accept = "UPDATE clans SET invites = '" . buildStringArray(array_diff(strToArray($invitedClan['invites']), array($steamid))) . "', members='". buildStringArray($members) ."' WHERE cname = '" . $invitedClan['cname'] . "'";
   
         if ($connection->query($accept)) {
            header("Location: ../", true, 301);
            die();
         } else {
            echo "error: " . $connection->error;
         }
      } else {
         header("Location: ./", true, 301);
         exit();
      }
   }

   if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reject'])) {
      $invitedClan = $invitesPlayer[$_POST['reject']];
      $reject = "UPDATE clans SET invites = '" . buildStringArray(array_diff(strToArray($invitedClan['invites']), array($steamid))) . "' WHERE cname = '" . $invitedClan['cname'] . "'";

      if ($connection->query($reject)) {
         header("Location: ../", true, 301);
         die();
      } else {
         echo "error: " . $connection->error;
      }
   }
?>