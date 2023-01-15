<?php
   session_start();
   
   $myClan = [];
   $steamid = '';
   if(isset($_SESSION['logged_in']) && !empty($_SESSION['logged_in'])){
      $displayname = $_SESSION['userData']['name'];
      $avatar = $_SESSION['userData']['avatar'];
      $steamid = $_SESSION['userData']['steam_id'];

      $myClanSQL = "SELECT cname FROM clans WHERE members LIKE '%" . $steamid . "%'";
      $myClanData = mysqli_query($connection, $myClanSQL);
      $myClan = mysqli_fetch_assoc($myClanData);
   }
?>