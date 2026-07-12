<?php

require_once "model.php";
require_once "util/declare.php";

class Controller
{
    public function declare_zone(): void {
		(new Model())->insert_zone($_POST);
		//var_dump( json_decode($_POST["coords"]));
       // echo json_encode($_POST);
    }
	
	public function get_status_from_coords(): void {
		$data = json_decode(file_get_contents("php://input"), true);

		//header('Content-Type: application/json');
		//echo json_encode($data, JSON_PRETTY_PRINT);
		(new Model())->get_status_from_coords($data);
		//var_dump( json_decode($_POST["coords"]));
       // echo json_encode($_POST);
    }
	
	public function get_zones_for_specific_user(): void {
		$data = json_decode(file_get_contents("php://input"), true);

		$user_id = $data['user_id'] ?? null;
		$zones = (new Model())->get_zones_for_specific_user(intval($user_id));
		
		header('Content-Type: application/json');
		echo json_encode([
			'result' => $zones
		]);
	}
	
	public function delete_specific_zone():void {
		$data = json_decode(file_get_contents("php://input"), true);
		$coords = $data['coords'] ?? null;
		
		header('Content-Type: application/json');		
		echo json_encode((new Model())->delete_specific_zone($coords));
	}
	
	public function login():void {
		
		if($user = (new Model())->login($_POST["username"], md5($_POST["password"]))) {
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION["current_user_id"]=$user["id"];
			header("Location: routing.php?route=dashboard");
			exit();			
		} else {
			die("Invalid user/password");
		}
	}
	
	public function access_dashboard():void {
		header("Location: dashboard.php");
		
	}
	
	public function declare_safe_sky_zone($zones): void {
		date_default_timezone_set('Europe/Paris');
		require_once "role_manager.php";
		$roleManager = new roleManager();
		$roleManager->log_as_admin_or_not();
		session_write_close();
/*
		if (!defined('APP_ENTRY')) {
			http_response_code(403);
			exit('Forbidden');
		}
*/
		$features = array();

		foreach ($zones as $z) {	
			if (new DateTime() >= new DateTime($z["start_date"]) && new DateTime() < new DateTime($z["end_date"])) {
				$features[] = [
					"type" => "Feature",
					"properties" => [
						"id" => "zone-highline-" . $z["id"],
						"call_sign" => "Highline_Spot_". $z["id"],
						"last_update" => time(),
						"max_altitude" => $z["max_altitude"],
						"remarks" => $z["description"]
					],
					"geometry" => [
						"type" => "Polygon",
						"coordinates" => [
							$z["coords"]
						]
					]
				];
			}
		}


		$safeSkyAdvisory = [
			"type" => "FeatureCollection",
			"features" => $features
		];

		$client = new SafeSkyClient("sk_test_2bfa43bb2c2a285918d8c63d1c5100bf781ff856aa7a47e7");
		$response = $client->sendAdvisory($safeSkyAdvisory);
		var_dump($response);
	}
}