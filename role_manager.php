<?php

require_once "model.php";

class RoleManager
{
    public function log_as_admin_or_not()
    {
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

        $msg = "You are not logged as an administrator.";

        if (!isset($_SESSION["current_user_id"])) {
            die($msg);
        }

        $user = (new Model())->get_user_from_id($_SESSION["current_user_id"]);

        if (!$user) {
            die($msg);
        }

        if (!isset($user["role"]) || $user["role"] !== "admin") {
            die($msg);
        }
    }
}