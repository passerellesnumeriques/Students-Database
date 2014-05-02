<?php
class page_roles extends Page {
	
	public function get_required_rights() { return array("manage_roles"); }
	
	protected function execute() {
		$this->add_javascript("/static/widgets/wizard.js");
		$this->add_javascript("/static/javascript/validation.js");

		$roles = SQLQuery::create()
			->select("Role")
			->field('id')
			->field('name')
			->join("Role","UserRole",array("id"=>"role"))
			->field("UserRole","user")
			->count("nb_users")
			->groupBy("Role","id")
			->orderBy("Role","name",true)
			->execute();
?>
<div class='page_title'>
	<img src="/static/user_management/role_32.png"/>
	Roles
</div>
<div style='background-color:white;padding:10px'>
	<table rules='all' style='border:1px solid black;margin:5px'>
		<tr>
			<th>Role</th>
			<th>Users</th>
			<th>Actions</th>
		</tr>
		<?php foreach ($roles as $role) {?>
		<tr>
			<td><?php echo $role["name"];?></td>
			<td align=right><?php echo $role["user"] == null ? 0 : $role["nb_users"];?></td>
			<td>
				<img src='<?php echo theme::$icons_16["edit"];?>' title="Rename" style='cursor:pointer' onclick="rename_role(<?php echo $role["id"];?>,'<?php echo $role["name"];?>');"/>
				<img src='/static/user_management/access_list.png' title="Access Rights" style='cursor:pointer' onclick="location='role_rights?role=<?php echo $role["id"];?>';"/>
				<img src='<?php echo theme::$icons_16["remove"];?>' title="Remove" style='cursor:pointer' onclick="remove_role(<?php echo $role["id"];?>,'<?php echo $role["name"];?>',<?php echo $role["user"] == null ? 0 : $role["nb_users"]?>);"/>
			</td>
		</tr>
		<?php }?>
	</table>
</div>
<div class='page_footer'>
	<button class="action" onclick="new_role();"><img src='<?php echo theme::make_icon("/static/user_management/role.png",theme::$icons_10["add"]);?>'/> New Role</button>
</div>
	
<script type='text/javascript'>
var existing_roles = [<?php
$first = true;
foreach ($roles as $role) {
	if ($first) $first = false; else echo ",";
	echo json_encode($role["name"]);
}
?>];
function new_role() {
	input_dialog(
		theme.build_icon("/static/user_management/role.png",theme.icons_10.add),
		"New Role",
		"Role Name",
		"",
		100,
		function(name) {
			if (name.trim().length == 0)
				return "Please enter a name";
			// check the name does not exist yet
			for (var i = 0; i < existing_roles.length; ++i)
				if (name.trim().toLowerCase() == existing_roles[i].toLowerCase())
					return "A role already exists with this name";
			return null;		
		},function(name) {
			if (!name) return;
			name = name.trim();
			var lock = lock_screen(null, "Creating role "+name);
			service.json("user_management","create_role",{name:name},function(result){
				unlock_screen(lock);
				if (result && result.id)
					location.href = '/dynamic/user_management/page/role_rights?role='+result.id;
			});
		}
	);
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
			service.json("data_model","save_cell",{table:'Role',column:'name',row_key:'id',value:new_name,lock:null},function(result) {
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
