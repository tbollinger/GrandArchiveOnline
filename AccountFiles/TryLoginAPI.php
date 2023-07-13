<?php
include_once DOC_ROOT . './AccountSessionAPI.php';

include_once DOC_ROOT . '../Assets/patreon-php-master/src/OAuth.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/API.php';
include_once DOC_ROOT . '../Assets/patreon-php-master/src/PatreonDictionary.php';
include_once DOC_ROOT . '../includes/functions.inc.php';
include_once DOC_ROOT . '../includes/dbh.inc.php';
include_once DOC_ROOT . '../Libraries/HTTPLibraries.php';

SetHeaders();


if (!IsUserLoggedIn()) {
  if (isset($_COOKIE["rememberMeToken"])) {
    loginFromCookie();
  }
}

$response = new stdClass();
$response->isUserLoggedIn = IsUserLoggedIn();
if($response->isUserLoggedIn)
{
  $response->loggedInUserID = LoggedInUser();
  $response->loggedInUserName = LoggedInUserName();
}

echo(json_encode($response));

exit;

?>
