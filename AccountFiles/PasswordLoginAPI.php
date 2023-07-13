<?php
include_once DOC_ROOT . './AccountSessionAPI.php';

include_once DOC_ROOT . '../Assets/patreon-php-master/src/OAuth.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/API.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once DOC_ROOT . '../includes/functions.inc.php';
include_once DOC_ROOT . '../includes/dbh.inc.php';
include_once DOC_ROOT . '../Database/ConnectionManager.php';
include_once DOC_ROOT . './AccountDatabaseAPI.php';
include_once DOC_ROOT . '../Libraries/HTTPLibraries.php';

SetHeaders();
$response = new stdClass();

$_POST = json_decode(file_get_contents('php://input'), true);

if($_POST == NULL) {
  $response->error = "Parameters were not passed";
  echo json_encode($response);
  exit;
}

$username = $_POST["userID"];
$password = $_POST["password"];
$rememberMe = isset($_POST["rememberMe"]);

try {
  PasswordLogin($username, $password, $rememberMe, true);
} catch (\Exception $e) {
}

$response->isUserLoggedIn = IsUserLoggedIn();
if ($response->isUserLoggedIn) {
  $response->loggedInUserID = LoggedInUser();
  $response->loggedInUserName = LoggedInUserName();
}

echo (json_encode($response));

exit;
