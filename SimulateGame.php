<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start();
include DOC_ROOT . "WriteLog.php";
include DOC_ROOT . "GameLogic.php";
include DOC_ROOT . "GameTerms.php";
include DOC_ROOT . "Libraries/SHMOPLibraries.php";
include DOC_ROOT . "Libraries/StatFunctions.php";
include DOC_ROOT . "Libraries/UILibraries.php";
include DOC_ROOT . "Libraries/PlayerSettings.php";
include DOC_ROOT . "Libraries/NetworkingLibraries.php";
include DOC_ROOT . "AI/CombatDummy.php";
include DOC_ROOT . "AI/PlayerMacros.php";
include DOC_ROOT . "Libraries/HTTPLibraries.php";
require_once("Libraries/CoreLibraries.php");
include_once DOC_ROOT . "./includes/dbh.inc.php";
include_once DOC_ROOT . "./includes/functions.inc.php";
include_once DOC_ROOT . "APIKeys/APIKeys.php";
ob_end_clean();

$gameName = $_GET["gameName"];

if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}

$gameName = "r" . $gameName;

$filename = "./Games/" . $gameName . "/commandfile.txt";
$handler = fopen($filename, "r");
$filesize = filesize($filename);
$commands = explode("\r\n", fread($handler, $filesize));
fclose($handler);

$playerID = $commands[0][0];

copy("./Games/" . $gameName . "/startGamestate.txt", "./Games/" . $gameName . "/gamestate.txt");

include DOC_ROOT . "ParseGamestate.php";
$otherPlayer = $currentPlayer == 1 ? 2 : 1;
$skipWriteGamestate = false;
$mainPlayerGamestateStillBuilt = 0;
$makeCheckpoint = 0;
$makeBlockBackup = 0;
$MakeStartTurnBackup = false;
$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
$conceded = false;
$randomSeeded = false;
$afterResolveEffects = [];
$animations = [];

echo(count($commands) . "<BR>");
for($k=0; $k<count($commands); ++$k)
{
  $skipWriteGamestate = false;
  $makeCheckpoint = 0;
  $makeBlockBackup = 0;
  $MakeStartTurnBackup = false;


  $command = explode(" ", $commands[$k]);
  if(count($command) < 5) break;
  if($command[5] == "") $command[5] = [];
  else $command[5] = explode("|", $command[5]);
  $playerID = $command[0];
  BuildMyGamestate($playerID);
  echo($command[0] . "&nbsp;" . $command[1] . "&nbsp;" . $command[2] . "&nbsp;" . $command[3] . "&nbsp;" . $command[4] . "<BR>");

  $mode = $command[1];
  ProcessInput($command[0], $command[1], $command[2], $command[3], $command[4], $command[5], true);

  ProcessMacros();
  CacheCombatResult();

  $defPlayer = $mainPlayer == 1 ? 2 : 1;
  DoGamestateUpdate();


  if (!$skipWriteGamestate) {
      UpdateGameState($playerID);
      WriteGamestate();
    }

  if ($makeCheckpoint) MakeGamestateBackup();
  if ($makeBlockBackup) MakeGamestateBackup("preBlockBackup.txt");
  if ($MakeStartTurnBackup) MakeStartTurnBackup();

  if($mode == 10000) ParseGamestate();
}


//include DOC_ROOT . "WriteGamestate.php";

?>

Something is wrong with the XAMPP installation :-(
