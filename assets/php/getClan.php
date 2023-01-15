<?php
   $myClanSQL = "SELECT cname FROM clans WHERE members LIKE '%" . $steamid . "%'";
   $myClanData = mysqli_query($connection, $myClanSQL);
   $myClan = mysqli_fetch_assoc($myClanData);
   return ($myClan == null ? [] : $myClan);
?>