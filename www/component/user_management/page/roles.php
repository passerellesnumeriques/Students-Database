<?php
class page_roles extends Page {
	
	public function getRequiredRights() { return array("manage_roles"); }
	
	protected function execute() {
		$this->addJavascript("/static/widgets/wizard.js");
		$this->addJavascript("/static/javascript/validation.js");

		$roles = SQLQuery::create()
			->select("Role")
			->field('id')
			->field('name')
			->field('builtin')
			->join("Role","UserRole",array("id"=>"role"))
			->field("UserRole","user")
			->count("nb_users")
			->groupBy("Role","id")
			->orderBy("Role","name",true)
			->execute();
		
		require_once("component/data_model/page/utils.inc");
?>
<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;position:absolute;top:0px;left:0px;'>
<div class='page_title' style='flex:none'>
	<img src="/static/user_management/role_32.png"/>
	Roles
</div>
<div style='padding:10px;flex:1 1 auto;'>
	<table rules='all' style='border:1px solid black;margin:5px;background-color:white;'>
		<tr>
			<th>Role</th>
			<th>Users</th>
			<th>Actions</th>
		</tr>
		<?php foreach ($roles as $role) {?>
		<tr>
			<td style='padding:0px 2px;'>
				<?php datamodel_cell_here($this, true, "Role", "name", $role["id"], $role["name"], null);?>
			</td>
			<td align=right><?php echo $role["user"] == null ? 0 : $role["nb_users"];?></td>
			<td>
				<button class='flat icon' title="Access Rights" onclick="location='role_rights?role=<?php echo $role["id"];?>';"><img src='/static/user_management/access_list.png'/></button>
				<?php if (!$role["builtin"]) {?>
				<button class='flat icon' title="Remove" onclick="remove_role(<?php echo $role["id"];?>,'<?php echo $role["name"];?>',<?php echo $role["user"] == null ? 0 : $role["nb_users"]?>);"><img src='<?php echo theme::$icons_16["remove"];?>'/></button>
				<?php } ?>
			</td>
		</tr>
		<?php }?>
	</table>
</div>
<div class='page_footer' style='flex:none'>
	<button class="action green" onclick="new_role();"><img src='<?php echo theme::make_icon("/static/user_management/role.png",theme::$icons_10["add"]);?>'/> New Role</button>
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
function new_role() {
	inputDialog(
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

function remove_role(id,name,nb_users) {
	confirmDialog("Are you sure you want to remove the role <i>"+name+"</i><br/>and unassign "+nb_users+" "+(nb_users>1?"users":"user")+" ?",function(confirmed){
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
