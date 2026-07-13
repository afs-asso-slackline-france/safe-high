<?php 
/*** 

-- bdd deploye --
CREATE DATABASE safe_high;
USE safe_high;


--- Sql tables deploy ---

CREATE TABLE zone (
    id INT NOT NULL AUTO_INCREMENT,
    label VARCHAR(300) NULL,
    description TEXT NULL,
    max_altitude INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    coords VARCHAR(2000) NOT NULL UNIQUE,
    statut VARCHAR(300) NOT NULL,
    user_id INT NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;


CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    login VARCHAR(300) NOT NULL UNIQUE,
    password VARCHAR(300) NOT NULL,
    role VARCHAR(300) NOT NULL,
    PRIMARY KEY(id)
) ENGINE=InnoDB;


CREATE TABLE workers (
    id INT PRIMARY KEY,
    running TINYINT(1) NOT NULL,
    last_run DATETIME NULL
);

--- GENERATE DATA TO START ---
INSERT INTO `users` (`id`, `login`, `password`, `role`) VALUES (NULL, 'admin', 'af57fddf963baad6a0b2dfe79b383401', 'admin');
INSERT INTO `workers` (`id`, `running`, `last_run`) VALUES (1, '0', '');
**/

class Model
{
	public function pdo_connect():Object {
		$pdo = new PDO(
			"mysql:host=localhost;dbname=safe_high;charset=utf8mb4",
			"root",
			"",
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
			]
		);
		
		return $pdo;
		
	}
	
	public function get_all_zones($pdo): array {
		$stmt = $pdo->prepare("SELECT * FROM zone");
		$stmt->execute(); 
		$zones = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if ($zones) {
			return $zones;	
		} else {
			return array();
		}
	}
	
	public function get_zone_from_coords($pdo, $coords): array {
		$sql = "SELECT * FROM zone";
		$stmt = $pdo->prepare("SELECT * FROM zone WHERE coords=:coords");
		$stmt->execute(['coords' => $coords]); 
		$zones = $stmt->fetch();
		
		if ($zones) {
			return $zones;	
		} else {
			return array();
		}
	}
	
	public function get_zones_for_specific_user($user_id): array {
		$pdo = self::pdo_connect();
		
		$sql = "SELECT * FROM zone";
		$stmt = $pdo->prepare("SELECT * FROM zone WHERE user_id=:user_id");
		$stmt->execute(['user_id' => $user_id]); 
		$zones = $stmt->fetchAll();
		
		if ($zones) {
			return $zones;	
		} else {
			return array();
		}
	}
	
    public function insert_zone($result): void {
		require_once "role_manager.php";
		$roleManager = new roleManager();
		$roleManager->log_as_admin_or_not();
		session_write_close();
		
		
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		
		$pdo = self::pdo_connect();
		
		if (count(self::get_zone_from_coords($pdo, $result['coords'])) == 0) {

			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$sql = "INSERT INTO zone (
						label,
						description,
						max_altitude,
						start_date,
						end_date,
						coords,
						`user_id`,
						statut
					) VALUES (
						:label,
						:description,
						:max_altitude,
						:start_date,
						:end_date,
						:coords,
						:user_id,
						:statut
					)";

			$stmt = $pdo->prepare($sql);

			$stmt->execute([
				':label' => $result['name'],
				':description' => $result['zone_description'],
				':max_altitude' => (int)$result['max_altitude'],
				':start_date' => str_replace('T', ' ', $result['start_date']),
				':end_date' => str_replace('T', ' ', $result['end_date']),
				':coords' => $result['coords'], // JSON sous forme de chaîne
				':user_id' => $_SESSION["current_user_id"],                  // À remplacer par l'ID de l'utilisateur connecté
				':statut' => 'Setting up .. '      // Ou 'Brouillon', 'Validée', etc.
			]);
			
		echo json_encode(["success" => 1, "msg" => "This zone has been well declared." ]);
		} else {
			echo json_encode(["success" => 0, "msg" => "This zone has been well declared." ]); 
		}
				
	}
	
	public function get_status_from_coords($coords_list): void {

		$pdo = self::pdo_connect();

		header('Content-Type: application/json');

		$to_update_statut = [];

		foreach ($coords_list as $cl) {

			foreach ($cl as $c) {

				$zone = self::get_zone_from_coords($pdo, $c);

				if ($zone) {
					$to_update_statut[$zone['coords']] = $zone['statut'];
				}
			}
		}

		echo json_encode($to_update_statut, JSON_PRETTY_PRINT);
	}
	
	public function delete_specific_zone($coords): string {
		require_once "role_manager.php";
		$roleManager = new roleManager();
		$roleManager->log_as_admin_or_not();
		session_write_close();
		
		try {
			$pdo = self::pdo_connect();
			
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$sql = "DELETE FROM zone WHERE coords = :coords";

			$stmt = $pdo->prepare($sql);
			$stmt->execute([
				'coords' => $coords
			]);

			if(!$stmt->rowCount()) 
			{
				return "Deletion failed";
			} else {
				return "Deletion ok";
			}
		}
		catch (PDOException $e) {
			return "Erreur PDO : " . $e->getMessage();
		} catch (Exception $e) {
			return "Erreur : " . $e->getMessage();
		}
		
	}
	
	public function update_status($pdo, $id, $status_msg): void {
		require_once "role_manager.php";
		$roleManager = new roleManager();
		$roleManager->log_as_admin_or_not();
		session_write_close();
		
		$sql = "UPDATE zone
				SET statut = :status
				WHERE id = :id";

		$stmt = $pdo->prepare($sql);

		$stmt->execute([
			':status' => $status_msg,
			':id'     => $id
		]);
	}
	
	public function login($login, $mdp): array {
		$pdo = self::pdo_connect();
		$stmt = $pdo->prepare("SELECT * FROM users WHERE login=:login AND password=:mdp");
		$stmt->execute(['login' => $login, 'mdp' => $mdp]); 
		$user = $stmt->fetch();
		
		if ($user) {
			return $user;
		} else {
			return array();
		}
	}

	public function get_user_from_id($id): array {
			$pdo = self::pdo_connect();
			$stmt = $pdo->prepare("SELECT * FROM users WHERE id=:id");
			$stmt->execute(['id' => $id]); 
			$user = $stmt->fetch();
			
			if ($user) {
				return $user;
			} else {
				return array();
			}
		}
}
