<?php
   $connection = mysqli_connect($config['hostname'], $config['username'], $config['password'], $config['database']);
   if($connection->connect_errno) {
      header('Location: /error.php?error=noconnection');
      exit();
   }
   
   return $connection;
?>