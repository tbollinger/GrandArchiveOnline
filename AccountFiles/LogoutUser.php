<?php
include_once DOC_ROOT . './AccountSessionAPI.php';

ClearLoginSession();

header("location: ../MainMenu.php");
exit;
?>
