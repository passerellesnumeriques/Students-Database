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

		if ($values->hasColumnValue("UserPeople","people") && PNApplication::$instance->user_management->has_right("manage_users")) {
			// not new and can edit
			echo "<button class='action important' onclick='resetPassword();'><img src='/static/user_management/reset_password.png'/> Reset Password</button>";
			?>
			<script type='text/javascript'>
			function resetPassword() {
				alert("TODO");
			}
			</script>
			<?php 
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