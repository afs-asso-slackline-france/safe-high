<?php

require_once "role_manager.php";
require_once "model.php";

$roleManager = new roleManager();
$roleManager->log_as_admin_or_not();

// Connexion à la BDD
$pdo = (new Model())->pdo_connect();

// Vérifie si le worker est déjà lancé
$running = (int)$pdo->query("SELECT running FROM workers WHERE id = 1")->fetchColumn();

if ($running === 1) {
    exit('Le worker est déjà en cours.');
}

// Active le worker
$pdo->exec("
    UPDATE workers
    SET running = 1
    WHERE id = 1
");

$worker = __DIR__ . DIRECTORY_SEPARATOR . 'worker.php';

if (PHP_OS_FAMILY === 'Windows') {
    $php = 'C:\\xampp\\php\\php.exe';
	
	$sessionId = session_id();
	$cmd = 'cmd /c start "" /B "' . $php . '" "' . $worker . '" "' . $sessionId . '" > NUL 2>&1"';
	
	//$cmd = 'cmd /c start "" /B "' . $php . '" "' . $worker . '" > NUL 2>&1';
	pclose(popen($cmd, 'r'));

} else {
	$php = '/usr/bin/php';

    exec(
        escapeshellarg($php) . ' ' .
        escapeshellarg($worker) . ' ' .
        escapeshellarg($sessionId) .
        ' > /dev/null 2>&1 &'
    );
}

echo "Worker démarré.";