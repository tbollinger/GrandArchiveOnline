<?php
include_once DOC_ROOT . './AccountSessionAPI.php';
include_once DOC_ROOT . '../Libraries/HTTPLibraries.php';

SetHeaders();

ClearLoginSession();

$response = new stdClass();
$response->message = "Logout successful";
echo(json_encode($response));

exit;

?>
