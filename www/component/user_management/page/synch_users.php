<?php 
class page_synch_users extends Page {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function execute() {
		$input = json_decode($_POST["input"], true);
		$domain = $input["domain"];
		$token = $input["token"];
		echo "<div style='background-color:white;padding:10px'>";
		$as = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		try {
			$list = $as instanceof AuthenticationSystem_UserList ? $as->getUserList($token) : null;
		} catch (Exception $e) {
			$list = $e;
		}
		if ($list instanceof Exception) {
			echo toHTML($list->getMessage());
		} else if ($list === null) {
			echo "The authentication system of ".$domain." does not support synchronization";
		} else {
			$current = SQLQuery::create()->select("Users")->whereValue("Users","domain",$domain)->execute();
			$current_internal = SQLQuery::create()->bypassSecurity()->select("InternalUser")->field("username")->executeSingleField();
			// match between current users, and list from authentication system
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
			// remove the internal users
			for ($i = 0; $i < count($current); $i++) {
				for ($j = 0; $j < count($current_internal); ++$j) {
					if ($current_internal[$j] == $current[$i]["username"]) {
						array_splice($current_internal, $j, 1);
						array_splice($current, $i, 1);
						$i--;
						break;
					}
				}
			}
			if (count($current) > 0) {
				echo "<form name='removed_users' onsubmit='return false;'>";
				echo "The following users do not exist anymore in ".$domain.":<ul>";
				foreach ($current as $user) {
					echo "<li>";
					echo "<b>".toHTML($user["username"])."</b>";
					echo "<br/>";
					echo "<input type='radio' value='keep' name='".$user["username"]."' checked='checked'/> Keep it<br/>";
					echo "<input type='radio' value='remove' name='".$user["username"]."'/> Remove it (information about it will be kept, be this user won't be able to login anymore)<br/>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			// check if internal users became present in authentication system
			$internal_to_as = array();
			for ($i = 0; $i < count($list); $i++) {
				if (in_array($list[$i]["username"], $current_internal)) {
					array_push($internal_to_as, $list[$i]);
					array_splice($list, $i, 1);
					$i--;
				}
			}
			if (count($internal_to_as) > 0) {
				echo "<form name='internal_to_as' onsubmit='return false;'>";
				echo "The following users are currently internal to the software, but they are now present in the authentication system:<ul>";
				foreach ($internal_to_as as $user) {
					echo "<li>";
					echo "<b>".toHTML($user["username"])."</b>";
					echo "<br/>";
					echo "<input type='radio' value='keep_internal' name='".$user["username"]."' checked='checked'/> Keep it internal<br/>";
					echo "<input type='radio' value='move_to_as' name='".$user["username"]."'/> Use the authentication system now, and remove it from internal users<br/>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			$users_info = array();
			if (count($list) > 0) {
				$people_types = PNApplication::$instance->people->getSupportedPeopleTypes();
				echo "<form name='new_users' onsubmit='return false'>";
				echo "The following users exist in ".$domain." but not yet in the software:<ul>";
				foreach ($list as $user) {
					echo "<li>";
					echo "<b>".toHTML($user["username"])."</b>";
					if (isset($user["info"])) {
						if (isset($user["info"]["People"])) {
							if (isset($user["info"]["People"]["first_name"]) && isset($user["info"]["People"]["last_name"])) {
								$fullname = $user["info"]["People"]["first_name"];
								if (isset($user["info"]["People"]["middle_name"]))
									$fullname .= " ".$user["info"]["People"]["middle_name"];
								$fullname .= $user["info"]["People"]["last_name"];
								echo " (".$fullname.")";
							}
						}
					}
					if (isset($user["groups"]) && count($user["groups"]) > 0) {
						echo " member of ";
						for ($i = 0; $i < count($user["groups"]); $i++) {
							if ($i > 0) echo ",";
							echo toHTML($user["groups"][$i]);
						}
					}
					echo "<br/>";
					if (isset($user["info"])) {
						if (isset($user["info"]["People"])) {
							if (isset($user["info"]["People"]["first_name"]) && isset($user["info"]["People"]["last_name"])) {
								$q = PNApplication::$instance->people->searchPeopleByFirstAndLastName($user["info"]["People"]["first_name"], $user["info"]["People"]["last_name"]);
								PNApplication::$instance->user_management->joinUserToPeople($q);
								$q->whereNull("Users", "username");
								$match = $q->execute();
								foreach ($match as $row) {
									echo "<input type='radio' name='".$user["username"]."' value='link_".$row["id"]."' onclick=\"if (this.checked) { if (this._already) { this.checked=''; this._already=false; } else { var list=this.form.elements['".$user["username"]."']; for(var i=0;i<list.length;++i)list[i]._already=false; this._already=true; } }\"/> Create user and link with existing people: ".DataModel::get()->getTable("People")->getRowDescription($row)."<br/>";
								}
							}
						}
					}
					array_push($users_info, array("username"=>$user["username"],"info"=>@$user["info"]));
					echo "<input type='radio' name='".$user["username"]."' value='create_user' onclick=\"if (this.checked) { if (this._already) { this.checked=''; this._already=false; } else { var list=this.form.elements['".$user["username"]."']; for(var i=0;i<list.length;++i)list[i]._already=false; this._already=true; } }\"/> Create as a new ";
					echo "<select name='type_".$user["username"]."'>";
					foreach ($people_types as $type) {
						if (!$type->isStandalone()) continue;
						echo "<option value=".json_encode($type->getId()).($type->getId() == "user" ? " selected='selected'" : "").">".toHTML($type->getName())."</option>";
					}
					echo "</select>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			if (count($list) == 0 && count($current) == 0) {
				echo "All users match between ".$domain." system and this software.";
			}
		}
		echo "</div>";
		?>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
<?php if ($list <> null) {?>
var users_info = <?php echo json_encode($users_info);?>;

function process_removed_users(ondone) {
	if (typeof document.forms["removed_users"] == 'undefined') { ondone(); return; }
	var form = document.forms["removed_users"];
	var users = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (!e.checked) continue;
		if (e.value == "keep") continue;
		users.push(e.name);
	}
	if (users.length == 0) { ondone(); return; }
	var next = function() {
		if (users.length == 0) { ondone(); return; }
		var username = users[0];
		popup.setFreezeContent("Removing user "+username);
		users.splice(0,1);
		service.json("user_management","remove_user",{domain:<?php echo json_encode($domain);?>,token:<?php echo json_encode($token);?>,username:username},function(res) {
			next();
		});
	};
	next();
}
function process_internal_to_as(ondone) {
	if (typeof document.forms["internal_to_as"] == 'undefined') { ondone(); return; }
	var form = document.forms["internal_to_as"];
	var users = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (!e.checked) continue;
		if (e.value == "keep_internal") continue;
		users.push(e.name);
	}
	if (users.length == 0) { ondone(); return; }
	var next = function() {
		if (users.length == 0) { ondone(); return; }
		var username = users[0];
		popup.setFreezeContent("Moving user "+username+" from internal to authentication system of <?php echo $domain;?>");
		users.splice(0,1);
		service.json("user_management","internal_to_authentication_system",{domain:<?php echo json_encode($domain);?>,token:<?php echo json_encode($token);?>,username:username},function(res) {
			next();
		});
	};
	next();
}
function process_new_users(ondone) {
	if (typeof document.forms["new_users"] == 'undefined') { ondone(); return; }
	var form = document.forms["new_users"];
	var to_create = [];
	var to_link = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (e.nodeName != "INPUT" || e.type != "radio") continue;
		if (!e.checked) continue;
		if (e.value == "create_user") {
			var type = form.elements["type_"+e.name].value;
			var username = e.name;
			var user = null;
			for (var j = 0; j < users_info.length; ++j) if (users_info[j].username == username) { user = users_info[j]; break; }
			var tc = null;
			for (var j = 0; j < to_create.length; ++j)
				if (to_create[j].type == type) { tc = to_create[j]; break; }
			if (tc == null) {
				tc = {type:type,users:[]};
				to_create.push(tc);
			}
			tc.users.push(user);
		} else if (e.value.substr(0,5) == "link_") {
			to_link.push({username:e.name,people_id:e.value.substr(5)});
		}
	}
	popup.freeze();
	var next_to_create = function(index) {
		if (index == to_create.length) { popup.close(); return; }
		popup.setFreezeContent("Creation of new users");
		window.top.require("popup_window.js",function() {
			var p = new window.top.popup_window('New User', null, "");
			var type = to_create[index].type != 'user' ? to_create[index].type+",user" : "user";
			var precreated = [];
			for (var i = 0; i < to_create[index].users.length; ++i) {
				var u = to_create[index].users[i];
				var pc = [];
				if (typeof u.info.People.first_name != 'undefined')
					pc.push({category:"Personal Information",data:"First Name",value:u.info.People.first_name});
				if (typeof u.info.People.middle_name != 'undefined')
					pc.push({category:"Personal Information",data:"Middle Name",value:u.info.People.middle_name});
				if (typeof u.info.People.last_name != 'undefined')
					pc.push({category:"Personal Information",data:"Last Name",value:u.info.People.last_name});
				pc.push({category:"User",data:"Username",value:u.username,forced:true});
				pc.push({category:"User",data:"Domain",value:<?php echo json_encode($domain);?>,forced:true});
				precreated.push(pc);
			}
			var frame = p.setContentFrame(
				"/dynamic/people/page/popup_create_people?types="+encodeURIComponent(type)+"&multiple="+(precreated.length > 1 ? "true" : "false")+"&ondone=continue_create_users&oncancel=continue_create_users",
				null,
				{
					precreated: (precreated.length == 1 ? precreated[0] : precreated)
				}
			);
			frame.continue_create_users = function() { next_to_create(index+1); };
			p.show();
		});
	};
	var next_to_link = function(index) {
		if (index == to_link.length) { next_to_create(0); return; }
		var u = to_link[index];
		popup.setFreezeContent("Creation of user "+u.username);
		service.json("user_management","create_user",{
			authentication_system: <?php echo json_encode($domain);?>,
			username: u.username,
			people_id: u.people_id
		},function(res){
			next_to_link(index+1);
		});
	};
	next_to_link(0);
}

popup.addOkButton(function() {
	popup.freeze();
	process_removed_users(function() {
		process_internal_to_as(function() {
			process_new_users(function() {
				popup.close();
			});
		});
	});
});
<?php } ?>
popup.addCancelButton();
</script>
		<?php 
	}
	
}
?>