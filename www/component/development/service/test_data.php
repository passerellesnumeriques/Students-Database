<?php 
class service_test_data extends Service {
	
	public function get_required_rights() { return array(); }
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	public function execute(&$component) {
		$domain = $_POST["domain"];
		
		$db_conf = include("conf/local_db");
		require_once("DataBaseSystem_".$db_conf["type"].".inc");
		$db_system_class = "DataBaseSystem_".$db_conf["type"];
		$db_system = new $db_system_class;
		$res = $db_system->connect($db_conf["server"], $db_conf["user"], $db_conf["password"]);
		if ($res <> DataBaseSystem::ERR_OK) {
			switch ($res) {
				case DataBaseSystem::ERR_CANNOT_CONNECT_TO_SERVER: PNApplication::error("Unable to connect to the database server"); break;
				case DataBaseSystem::ERR_INVALID_CREDENTIALS: PNApplication::error("Invalid credentials to connect to the database server"); break;
				default: PNApplication::error("Unknown result when connecting to the database server"); break;
			}
		} else {
			set_time_limit(240);
 			$db_system->execute("USE students_".$domain);
			$roles_id = $this->create_roles($db_system, $this->roles[$domain]);
			$this->create_users($db_system, $domain, $this->users[$domain], $roles_id);
		}		
	}
	
	private $roles = array(
		"Dev"=>array(
			"Staff"=>array("consult_user_list"=>true),
			"Student"=>array(),
			"Alumni"=>array(),
			"General Manager"=>array(),
			"Education Manager"=>array(),
			"Educator"=>array(),
			"Training Manager"=>array(),
			"SNA"=>array("edit_user_rights"=>true),
			"Selection Manager"=>array(),
			"Selection Officer"=>array(),
			"External Relations Manager"=>array(),
			"External Relations Officer"=>array(),
			"Finance Manager"=>array(),
		),
		"Test"=>array(),
		"PNP"=>array(),
		"PNV"=>array(),
		"PNC"=>array(),
	);
	private $users = array(
		"Dev"=>array(
			array("Helene", "Huard", "F", array("General Manager","Staff")),
			array("Julie", "Tardieu", "F", array("Staff", "Education Manager", "Educator")),
			array("Eduard", "Bucad", "M", array("Staff", "Educator")),
			array("Marian", "Lumapat", "F", array("Staff", "Educator")),
			array("Fatima", "Tiah", "F", array("Staff", "Educator")),
			array("Stanley", "Vasquez", "M", array("Staff", "Educator")),
			array("Kranz", "Serino", "F", array("Staff", "Educator")),
			array("Guillaume", "Le Cousin", "M", array("Staff", "Training Manager", 0)),
			array("Jovanih", "Alburo", "M", array("Staff", "SNA")),
			array("Rosalyn", "Minoza", "F", array("Staff", "Selection Manager")),
			array("Isadora", "Gerona", "F", array("Staff", "Selection Officer")),
			array("Sandy", "De Veyra", "M", array("Staff", "External Relations Manager")),
			array("Ann", "Labra", "F", array("Staff", "External Relations Officer")),
			array("Jeanne", "Salve", "F", array("Staff", "Finance Manager")),
			array("Rhey", "Laurente", "M", array("Alumni")),
			array("X", "Y", "F", array("Student")),
		),
		"Test"=>array(),
		"PNP"=>array(),
		"PNV"=>array(),
		"PNC"=>array(),
	);
	
	private function create_roles(&$db_system, $roles) {
		// create roles
		$roles_id = array();
		foreach ($roles as $role_name=>$role_rights) {
			$db_system->execute("INSERT INTO Role (name) VALUES ('".$role_name."')");
			$roles_id[$role_name] = $db_system->get_insert_id();
		}
		// assign rights to roles
		foreach ($roles as $role_name=>$role_rights) {
			$role_id = $roles_id[$role_name];
			foreach ($role_rights as $right_name=>$right_value) {
				$db_system->execute("INSERT INTO RoleRights (`role_id`,`right`,`value`) VALUES ('".$role_id."','".$right_name."','".$right_value."')");
			}
		}
		return $roles_id;
	}
	
	private function create_users(&$db_system, $domain, $users, $roles_id) {
		foreach ($users as $user) {
			$db_system->execute("INSERT INTO People (first_name,last_name,sex) VALUES ('".$user[0]."','".$user[1]."','".$user[2]."')");
			$people_id = $db_system->get_insert_id();
			$username = str_replace(" ","-", strtolower($user[0]).".".strtolower($user[1]));
			$db_system->execute("INSERT INTO Users (domain,username) VALUES ('".$domain."','".$username."')");
			$db_system->execute("INSERT INTO UserPeople (username,people) VALUES ('".$username."',".$people_id.")");
			foreach ($user[3] as $role) {
				$db_system->execute("INSERT INTO UserRole (domain,username,role_id) VALUES ('".$domain."','".$username."',".($role === 0 ? 0 : $roles_id[$role]).")");
			}
		}
		
	}
	
}
?>