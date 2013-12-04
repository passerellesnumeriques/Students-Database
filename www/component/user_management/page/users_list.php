<?php 
class page_users_list extends Page {
	
	public function get_required_rights() { return array("consult_user_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_users_list();");
?>
<div style='width:100%;height:100%' id='users_list'>
</div>
<script type='text/javascript'>
function init_users_list() {
	new data_list(
		'users_list',
		'Users',
		['User.Domain','User.Username','Personal Information.First Name','Personal Information.Last Name','User.Roles'],
		null,
		function (list) {
			<?php if (PNApplication::$instance->user_management->has_right("assign_role")) {?>
			var roles = [<?php
			$roles = SQLQuery::create()->select("Role")->execute();
			$first = true;
			foreach ($roles as $role) {
				if ($first) $first = false; else echo ",";
				echo "{id:".$role["id"].",name:".json_encode($role["name"])."}";
			}
			?>];
			list.grid.setSelectable(true);
			var assign_roles = document.createElement("DIV");
			assign_roles.className = "button disabled";
			assign_roles.innerHTML = "<img src='/static/user_management/role.png'/> Assign roles";
			assign_roles.func = function() {
				require("popup_window.js",function(){
					var content = document.createElement("FORM");
					var checkboxes = [];
					for (var i = 0; i < roles.length; ++i) {
						var cb = document.createElement("INPUT");
						cb.type = 'checkbox';
						cb.role_id = roles[i].id;
						content.appendChild(cb);
						content.appendChild(document.createTextNode(" "+roles[i].name));
						content.appendChild(document.createElement("BR"));
						checkboxes.push(cb);
					}
					var p = new popup_window("Assign roles","/static/user_management/role.png",content);
					p.addOkCancelButtons(function(){
						var roles_id = [];
						for (var i = 0; i < checkboxes.length; ++i)
							if (checkboxes[i].checked)
								roles_id.push(checkboxes[i].role_id);
						p.close();
						if (roles_id.length == 0) return;
						var status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Assigning roles...");
						window.top.status_manager.add_status(status);
						list.grid.startLoading();
						var users = [];
						var sel = list.grid.getSelection();
						for (var i = 0; i < sel.length; ++i)
							users.push(list.getRowData(sel[i], "Users", "id"));
						service.json("user_management","assign_roles",{users:users,roles:roles_id},function(result){
							window.top.status_manager.remove_status(status);
							list.grid.endLoading();
							if (result)
								list.reload_data();
						});
					});
					p.show();
				});
			};
			list.addHeader(assign_roles);
			var unassign_roles = document.createElement("DIV");
			unassign_roles.className = "button disabled";
			unassign_roles.innerHTML = "<img src='/static/user_management/role.png'/> Unassign roles";
			unassign_roles.func = function() {
				require("popup_window.js",function(){
					var content = document.createElement("FORM");
					var checkboxes = [];
					for (var i = 0; i < roles.length; ++i) {
						var cb = document.createElement("INPUT");
						cb.type = 'checkbox';
						cb.role_id = roles[i].id;
						content.appendChild(cb);
						content.appendChild(document.createTextNode(" "+roles[i].name));
						content.appendChild(document.createElement("BR"));
						checkboxes.push(cb);
					}
					var p = new popup_window("Unassign roles","/static/user_management/role.png",content);
					p.addOkCancelButtons(function(){
						var roles_id = [];
						for (var i = 0; i < checkboxes.length; ++i)
							if (checkboxes[i].checked)
								roles_id.push(checkboxes[i].role_id);
						p.close();
						if (roles_id.length == 0) return;
						var status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Unassigning roles...");
						window.top.status_manager.add_status(status);
						list.grid.startLoading();
						var users = [];
						var sel = list.grid.getSelection();
						for (var i = 0; i < sel.length; ++i)
							users.push(list.getRowData(sel[i], "Users", "id"));
						service.json("user_management","unassign_roles",{users:users,roles:roles_id},function(result){
							window.top.status_manager.remove_status(status);
							list.grid.endLoading();
							if (result)
								list.reload_data();
						});
					});
					p.show();
				});
			};
			list.addHeader(unassign_roles);
			list.grid.onselect = function(selection) {
				if (!selection || selection.length == 0) {
					assign_roles.className = "button disabled";
					assign_roles.onclick = null;
					unassign_roles.className = "button disabled";
					unassign_roles.onclick = null;
				} else {
					assign_roles.className = "button";
					assign_roles.onclick = assign_roles.func;
					unassign_roles.className = "button";
					unassign_roles.onclick = unassign_roles.func;
				}
			};
			<?php }?>
		}
	);
}
</script>
<?php 
	}
		
}
?>