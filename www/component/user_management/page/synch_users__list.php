<?php 
class page_synch_users__list extends Page {
	
	public function get_required_rights() { return array("manage_users"); }
	
	public function execute() {
		$domain = $_GET["domain"];
		$token = $_GET["token"];
		echo "<div style='background-color:white;padding:10px'>";
		$as = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		$list = $as->getUserList($token);
		if ($list === null) {
			echo "The authentication system of ".$domain." does not support synchronization";
		} else {
			$current = SQLQuery::create()->select("Users")->whereValue("Users","domain",$domain)->execute();
			for ($i = 0; $i < count($list); $i++) {
				$username = $list[$i]["username"];
				for ($j = 0; $j < count($current); $j++) {
					$un = $current[$j]["username"];
					if ($un == $username) {
						// match found
						array_splice($current, $j, 1);
						array_splice($list, $i, 1);
						$i--;
						break;
					}
				}
			}
			if (count($current) > 0) {
				echo "The following users do not exist anymore in ".$domain.":<ul>";
				foreach ($current as $user) {
					echo "<li>";
					echo $user["username"];
					echo "</li>";
				}
				echo "</ul>";
			}
			if (count($list) > 0) {
				echo "The following users exist in ".$domain." but not yet in the software:<ul>";
				foreach ($list as $user) {
					echo "<li>";
					echo $user["username"];
					echo "</li>";
				}
				echo "</ul>";
			}
			if (count($list) == 0 && count($current) == 0) {
				echo "All users match between ".$domain." system and this software.";
			}
		}
		echo "</div>";
	}
	
}
?>