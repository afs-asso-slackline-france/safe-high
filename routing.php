<?php
		//For dev environement only
		header("Access-Control-Allow-Origin: http://localhost:5173");
		header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type");
		//---------------------------//
		
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			http_response_code(200);
			exit;
		}
		
		require_once "controller.php";
		$route = $_GET['route'] ?? '';
		
        switch ($route) {
            case 'declare_zone':
                (new Controller())->declare_zone();
				break;
			case 'get_status':
                (new Controller())->get_status_from_coords();
				break;
			case 'get_zones_for_specific_user':
                (new Controller())->get_zones_for_specific_user();
				break;
			case 'delete_specific_zone':
                (new Controller())->delete_specific_zone();
				break;
			case 'login':
                (new Controller())->login();
				break;
			case 'dashboard':
                (new Controller())->access_dashboard();
				break;
		/*	case 'declare_safe_sky_zone':
				if (!defined('APP_ENTRY')) {
					http_response_code(403);
					exit('Forbidden');
				}		
				(new Controller())->declare_safe_sky_zone();
				break;
*/
            default:
                http_response_code(404);
                echo "404";
        }
