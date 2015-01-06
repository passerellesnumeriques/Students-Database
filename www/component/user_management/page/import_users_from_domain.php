<?php 
class page_import_users_from_domain extends Page {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function execute() {
		$domain = $_GET["domain"];
		echo "<div style='background-color:white;padding:5px;'>";
		if (!file_exists("conf/$domain.remote")) {
			echo "Remote access to $domain is not configured.";
		} else {
			if (!file_exists("data/domains_synch/$domain/latest_recover")) {
				echo "Domain $domain has never been synchronized, so we don't have a list of users.";
			} else {
				$db = SQLQuery::getDataBaseAccessWithoutSecurity();
				global $db_config;
				$db->selectDatabase($db_config["prefix"].$domain);
				$res = $db->execute("SELECT username FROM Users WHERE domain='".$db->escapeString($domain)."'");
				$users = array();
				while (($row = $db->nextRowArray($res)) <> null)
					array_push($users, $row[0]);
				$db->selectDatabase($db_config["prefix"].PNApplication::$instance->current_domain);
				$existing = SQLQuery::create()->bypassSecurity()->select("Users")
					->whereValue("Users", "domain", $domain)
					->field("username")
					->executeSingleField();
				$new_users = array();
				foreach ($users as $u) {
					$found = false;
					for ($i = count($existing)-1; $i >= 0; $i--) {
						if ($existing[$i] == $u) {
							array_splice($existing, $i, 1);
							$found = true;
							break;
						}
					}
					if (!$found) array_push($new_users, $u);
				}
				if (count($new_users) == 0 && count($existing) == 0) {
					echo "All users from $domain are already in ".PNApplication::$instance->current_domain."<br/>";
				} else {
					echo "<div class='page_section_title'>New Users</div>";
					if (count($new_users) == 0) {
						echo "All users from $domain are already in ".PNApplication::$instance->current_domain."<br/>";
					} else {
						echo "Select the users to import:<br/>";
						foreach ($new_users as $username) {
							echo "<input type='checkbox' user_type='new' username=".json_encode($username)."/> ".toHTML($username)."<br/>";
						}
					}
					echo "<div class='page_section_title'>Users removed in $domain</div>";
					if (count($existing) == 0) {
						echo "All users from $domain present in ".PNApplication::$instance->current_domain." still exist in domain $domain.<br/>";
					} else {
						echo "The following users, previously imported from $domain, do not exist anymore in $domain.<br/>";
						echo "Select the ones you want to remove:<br/>";
						foreach ($existing as $username) {
							echo "<input type='checkbox' user_type='old' username=".json_encode($username)."/> ".toHTML($username)."<br/>";
						}
					}
					?>
					<br/>
					<button class='action' onclick='synch();'>Continue</button>
					<script type='text/javascript'>
					function synch() {
						var new_users = [];
						var remove_users = [];
						var checkboxes = document.getElementsByTagName("INPUT");
						for (var i = 0; i < checkboxes.length; ++i) {
							if (!checkboxes[i].checked) continue;
							if (!checkboxes[i].hasAttribute("username")) continue;
							var username = checkboxes[i].getAttribute("username");
							if (checkboxes[i].getAttribute("user_type") == "new")
								new_users.push(username);
							else
								remove_users.push(username);
						}
						if (new_users.length == 0 && remove_users.length == 0) {
							alert("You didn't select any user");
							return;
						}
						var popup = window.parent.getPopupFromFrame(window);
						var msg = "";
						if (new_users.length > 0)
							msg = "Importing "+new_users.length+" user"+(new_users.length > 1 ? "s" : "");
						if (remove_users.length > 0) {
							if (msg.length > 0) msg += " and ";
							msg += "Removing "+remove_users.length+" user"+(remove_users.length > 1 ? "s" : "");
						}
						popup.freeze(msg);
						service.json("user_management","import_users_from_domain",{domain:<?php echo json_encode($domain);?>,new_users:new_users,remove_users:remove_users},function(res) {
							popup.close();
						});
					}
					</script>
					<?php 
				}
			}
		}
		echo "</div>";
	}
	
}
?>