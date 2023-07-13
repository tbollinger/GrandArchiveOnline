<?php

include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/API.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once DOC_ROOT . '../Database/ConnectionManager.php';
include_once DOC_ROOT . './AccountDatabaseAPI.php';

if (isset($_POST["submit"])) {

  $username = $_POST["userID"];
  $password = $_POST["password"];
  $rememberMe = isset($_POST["rememberMe"]);
  try {
    AttemptPasswordLogin($username, $password, $rememberMe);
  } catch (\Exception $e) { }
} else {
	echo("Login failed; please check your username and password.");
  exit();
}
