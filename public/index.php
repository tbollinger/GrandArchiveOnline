<?php
// TODO: Fix include path hacks.
// Required for now until we can clean up paths from include statements.
define('DOC_ROOT', $_SERVER['DOCUMENT_ROOT'] . '/../');

// Load environment variables to eliminate some globals.
require_once(DOC_ROOT . "/vendor/autoload.php");
$dotenv = Dotenv\Dotenv::createImmutable(DOC_ROOT);
$dotenv->load();

$dotenv->required('DELETE_GAMES')->isBoolean();

// TODO: Create filesystem abstraction layer for Games until we move them to the DB.
$gameStorage = new League\Flysystem\Local\LocalFilesystemAdapter(DOC_ROOT . '/Games');
$games = new League\Flysystem\Filesystem($gameStorage);

function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    exit();
}

$file = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Set a default homepage if request URI is empty.
if ($_SERVER['REQUEST_URI'] == '/') {
    $file = '/MainMenu.php';
}

include(DOC_ROOT . $file);

exit();


