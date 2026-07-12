<?php 

require_once "model.php";
require_once "role_manager.php";

$roleManager = new roleManager();
$roleManager->log_as_admin_or_not();
session_write_close();


// Connexion à la BDD
$pdo = (new Model())->pdo_connect();

$pdo->exec("
    UPDATE workers
    SET running = 0
    WHERE id = 1
");

echo "stopping process";