<?php

$category = new datamodel\DataDisplayCategory("User", "/static/user_management/user_16.png");
$model->addDataCategory($category);

$display = new datamodel\TableDataDisplay("Users", $category);
$display->addDataDisplay(new datamodel\SimpleDataDisplay("domain", "Domain", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("username", "Username", true));
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

class UserDataScreen extends datamodel\GenericDataScreen {

	public function generateEndOfSection(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		if (!in_array("user", $people_types)) return;

		if ($values->hasColumnValue("UserPeople","people")) {
			// not new
			$people_id = $values->getColumnValue("UserPeople","people");
			if (PNApplication::$instance->user_management->has_right("manage_users") || $people_id == PNApplication::$instance->user_management->people_id) {
				// can edit or himself
				$user_id = $values->getColumnValue("UserPeople","user");
				$user = SQLQuery::create()->bypassSecurity()->select("Users")->whereValue("Users","id",$user_id)->executeSingleRow();
				$internal = PNApplication::$instance->user_management->isInternalUser($user["domain"], $user["username"]);
				$can_reset = false;
				if ($internal) $can_reset = true;
				else {
					$as = PNApplication::$instance->authentication->getAuthenticationSystem($user["domain"]);
					if ($as instanceof AuthenticationSystem_ResetPassword)
						$can_reset = true;
				}
				if ($can_reset) {
					echo "<button class='action important' onclick='resetPassword();'><img src='/static/user_management/reset_password.png'/> Reset Password</button>";
					?>
					<script type='text/javascript'>
					function resetPassword() {
						<?php if ($user["domain"] == PNApplication::$instance->local_domain) {?>
							popup_frame("/static/user_management/reset_password.png", "Reset Password", "/dynamic/user_management/page/reset_pass?domain=<?php echo PNApplication::$instance->local_domain;?>&username=<?php echo urlencode($user["username"]);?>");
						<?php } else { ?>
							popup_frame("/static/user_management/reset_password.png", "Reset Password", "/dynamic/user_management/page/domain_auth", {feature:"AuthenticationSystem_ResetPassword",url:"/dynamic/user_management/page/reset_pass?domain=<?php echo $user["domain"];?>&username=<?php echo urlencode($user["username"]);?>",domain:<?php echo json_encode($user["domain"]);?>});
						<?php } ?>
					}
					</script>
					<?php 
				}
			}
		}
	}
	
}
$model->addDataScreen(new UserDataScreen($display));

$display = new datamodel\TableDataDisplay("UserRole", $category);
$display->addDataDisplay(new datamodel\JoiningTableDataDisplay("Roles", "user", "role", "name"));
$display->addJoinFromAll();
$model->addTableDataDisplay($display);

$display = new datamodel\TableDataDisplay("UserPeople", $category);
$display->addFollowAll();
$display->addJoinFrom("user");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("user", $types);
});
$display->addJoinToPrimary();
$model->addTableDataDisplay($display);

?>