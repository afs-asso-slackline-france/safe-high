<?php 
	require_once "role_manager.php";
	$roleManager = new roleManager();
	$roleManager->log_as_admin_or_not();
	session_write_close();
?>

<html>
<head> </head>
<body>

</body>
<h3>if there is no cron available</h3>
<a href="start_process.php">LAUNCH worker.</a><br />
<a href="stop_process.php">STOP worker</a>
</html>

