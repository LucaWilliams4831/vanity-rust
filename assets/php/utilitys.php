<?php
   function stripStringArray($str) {
      $str = str_replace("[", '', $str);
      $str = str_replace("]", '', $str);
      return $str;
   }

   function strToArray($str) {
      $str = str_replace("[", '', $str);
      $str = str_replace("]", '', $str);
      $array = explode (",", $str);
      return $array;
   }

   function buildStringArray($array) {
      // Turn array into string
      $str = implode(",", $array);

      // Remove comma in front if there
      if ($str[0] == ",")
         $str = ltrim($str, $str[0]);

      // Add [] to front and back of string
      $str = substr_replace($str, '[', 0, 0);
      $str = substr_replace($str, ']', strlen($str), 0);

      return $str;
   }

   function sortArray($arrayToSort, $sortedArray) {
      $newArray = []; 

      foreach ($sortedArray as $sortedPlayer) {
         $newArray[] = findPlayer($sortedPlayer, $arrayToSort);
      }

      return $newArray;
   }

   function findPlayer($steamId, $array) {
      foreach ($array as $player) {
         if ($player['steamid'] == $steamId) {
            return $player;
         }
      }

      return [];
   }

   function clean_input($data) {
      $data = trim($data);
      $data = stripslashes($data);
      $data = htmlspecialchars($data);
      return $data;
   }

   function check_badwords($string)
   {
      $badwords = require('./config/bad_words.php');
      $re = '/\b('.implode('|', $badwords).')\b/';
      $result = preg_match($re, $string, $match);
      return $result;
   }
?>