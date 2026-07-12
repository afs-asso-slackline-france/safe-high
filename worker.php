<?php
/*** ATTENTION BRICOLAGE : ***/
//Si on a accès à la machine faire un cron en appelant sur fichier toute les minutes.
//Et enlever le while(true)
//
// Ca sera plus propre evitera de faire sauter la session_cache_expire

//controller
require_once "controller.php";

// Connexion à la BDD
require_once "model.php";
$pdo = (new Model())->pdo_connect();


//A mettre en commentaire en cas de cron//
/**/if (isset($argv[1])) {
/**/    session_id($argv[1]);
/**/}
/**/session_start();
/**/require_once "role_manager.php";
/**/$roleManager = new roleManager();
/**/$roleManager->log_as_admin_or_not();
/**/session_write_close();

/**/while (true) {
//FIN A mettre en commentaire en cas de cron FIN//
    $stmt = $pdo->query("SELECT running FROM workers WHERE id = 1");
    $running = (bool)$stmt->fetchColumn();

    if (!$running) {
        exit;
    }

    stream_all($pdo);

    $pdo->exec("UPDATE workers SET last_run = NOW() WHERE id = 1");
	
	echo "Did loop at ".date('d/m/y H:i');
    sleep(60);
	
//A mettre en commentaire en cas de cron//
/**/}
//A mettre en commentaire en cas de cron FIN//

function stream_all($pdo) {

	$zones = (new Model())->get_all_zones($pdo);

	/******************/
	//coords traitement 

	foreach ($zones as $i => $z) {
		$coords = json_decode($z["coords"], true);

		array_walk_recursive($coords, function (&$value) {
			$value = (float) $value;
		});

		foreach ($coords as &$point) {
			$lat = $point[0];
			$lon = $point[1];

			$point[0] = $lon;
			$point[1] = $lat;
		}

		unset($point);

		if ($coords[0] !== end($coords)) {
			$coords[] = $coords[0];
		}

		// On garde toute la zone
		$zones[$i]["coords"] = $coords;
	}
	//-------------------//
	/********************/

	$error = false;

	try {
		(new Controller())->declare_safe_sky_zone($zones);
	} catch (Throwable $e) {
		$error = true;
		echo $e;
		foreach ($zones as $z) {
			(new Model())->update_status(
				$pdo,
				$z["id"],
				"!! STREAM ERROR !! (" . date('d/m/y H:i') . ")"
			);
		}
	}
	
	if ($error == false) {
		foreach ($zones as $z) {
			// La zone est dans l'interval de début et de fin.
			if ((new DateTime($z["start_date"]) <= new DateTime()) && (new DateTime($z["end_date"]) >= new DateTime())) {
				(new Model())->update_status(
					$pdo,
					$z["id"],
					"Streaming at (" . date('d/m/y H:i') . ")"
				);
			} elseif (new DateTime($z["start_date"]) > new DateTime()) {
				(new Model())->update_status(
					$pdo,
					$z["id"],
					"Waiting Start Date"
				);
				
			} elseif (new DateTime($z["end_date"]) < new DateTime()) {
				(new Model())->update_status(
					$pdo,
					$z["id"],
					"XXX THE STREAM IS OVER XXX"
				);
				
			}
		}
	}

	error_log("Traitement en cours...");
}