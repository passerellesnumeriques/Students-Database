<?php
class page_roles extends Page {
	
	public function get_required_rights() { return array("manage_roles"); }
	
	protected function execute() {
		$this->add_javascript("/static/widgets/page_header.js");
		$this->add_javascript("/static/widgets/wizard.js");
		$this->add_javascript("/static/javascript/validation.js");
		$this->onload("new page_header('roles_header');");

		$roles = SQLQuery::create()->select("Role")->field('id')->field('name')->field("UserRole","username")->join("Role","UserRole",array("id"=>"role_id"))->count("nb_users")->group_by("Role","id")->order_by("Role","name",true)->execute();
?>
		<div id='roles_header' icon="/static/user_management/role_32.png" title="Roles">
			<div class='button' onclick="new wizard('new_role_wizard').launch()"><img src='<?php echo theme::$icons_16["add"];?>'/> New Role</div>
		</div>
		<table rules='all' style='border:1px solid black;margin:5px'>
			<tr>
				<th>Role</th>
				<th>Users</th>
				<th>Actions</th>
			</tr>
			<?php foreach ($roles as $role) {?>
			<tr>
				<td><?php echo $role["name"];?></td>
				<td align=right><?php echo $role["username"] == null ? 0 : $role["nb_users"];?></td>
				<td>
					<img src='<?php echo theme::$icons_16["edit"];?>' title="Rename" style='cursor:pointer' onclick="rename_role(<?php echo $role["id"];?>,'<?php echo $role["name"];?>');"/>
					<img src='/static/user_management/access_list.png' title="Access Rights" style='cursor:pointer' onclick="location='role_rights?role=<?php echo $role["id"];?>';"/>
					<img src='<?php echo theme::$icons_16["remove"];?>' title="Remove" style='cursor:pointer' onclick="remove_role(<?php echo $role["id"];?>,'<?php echo $role["name"];?>',<?php echo $role["username"] == null ? 0 : $role["nb_users"]?>);"/>
				</td>
			</tr>
			<?php }?>
		</table>
		
<div id='new_role_wizard' class='wizard'
	title="New Role"
	icon="<?php echo theme::$icons_16["add"];?>"
	finish="new_role_finish"
>
	<div class='wizard_page'
		title='Role'
		icon='/static/user_management/role_32.png'
		validate="new_role_validate"
	>
		<form name='new_role_wizard' onsubmit='return false'>
			Role Name <input type='text' size=30 maxlength=100 name='role_name' onkeyup="wizard_validate(this)"/>
			<span class='validation_message' id='role_name_validation'></span>
		</form>
	</div>
</div>
		
<script type='text/javascript'>
var existing_roles = [<?php
$first = true;
foreach ($roles as $role) {
	if ($first) $first = false; else echo ",";
	echo json_encode($role["name"]);
}
?>];
function new_role_validate(wizard,handler) {
	var form = document.forms['new_role_wizard'];
	var name = form.elements['role_name'];
	var ok = true;
	// check name not empty, and does not exist yet
	if (name.value.length == 0) {
		validation_error(name, "Cannot be empty");
		ok = false;
	} else {
		// check the name does not exist yet
		for (var i = 0; i < existing_roles.length; ++i)
			if (name.value == existing_roles[i]) {
				ok = false;
				validation_error(name, "The role "+name+" already exists");
			}
		if (ok)
			validation_ok(name);
	}
	wizard.resize();
	handler(ok);
}
function new_role_finish(wizard) {
	var form = document.forms['new_role_wizard'];
	var name = form.elements['role_name'].value;
	service.json("user_management","create_role",{name:name},function(result){
		if (result && result.id)
			location.href = '/dynamic/user_management/page/role_rights?id='+result.id;
	},true);
}

function rename_role(id,name) {
	input_dialog(
		"/static/user_management/role.png",
		"Rename role",
		"Role name",
		name,
		100,
		function(new_name) {
			if (new_name.length == 0)
				return "Cannot be empty";
			// check the name does not exist yet
			if (new_name == name) return null;
			for (var i = 0; i < existing_roles.length; ++i)
				if (new_name == existing_roles[i])
					return "A role already exists with this name";
			return null;
		},function(new_name) {
			if (new_name == null) return;
			if (name == new_name) return;
			service.json("user_management","rename_role",{id:id,name:new_name},function(result) {
				if (result) location.reload();
			}, true);
		}
	);
}

function remove_role(id,name,nb_users) {
	confirm_dialog("Are you sure you want to remove the role <i>"+name+"</i><br/>and unassign "+nb_users+" "+(nb_users>1?"users":"user")+" ?",function(confirmed){
		if (!confirmed) return;
		service.json("user_management","remove_role",{id:id},function(result) {
			if (result) location.reload();
		}, true);
	});
}
</script>		
<?php
	}
	
}
