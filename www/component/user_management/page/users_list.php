<?php 
class page_users_list extends Page {
	
	public function getRequiredRights() { return array("consult_user_list"); }
	
	public function execute() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_users_list();");
?>
<div style='width:100%;height:100%;overflow:hidden;position:absolute;top:0px;left:0px;display:flex;flex-direction:column;'>
	<div id='users_list' style='flex:1 1 auto;'></div>
	<?php if (PNApplication::$instance->user_management->has_right("manage_users")) {?>
	<div class='page_footer' style='flex:none'>
		<button class='action' onclick='synchUsers();'><img src='<?php echo theme::$icons_16["_import"];?>'/> Synchronize Users</button>
		<button class='action green' onclick='newUser();'><img src='<?php echo theme::make_icon("/static/user_management/user_16.png",theme::$icons_10["add"]);?>'/> New User</button>
	</div>
	<?php }?>
</div>
<script type='text/javascript'>
function init_users_list() {
	window.list = new data_list(
		'users_list',
		'Users', null,
		['User.Domain','User.Username','Personal Information.First Name','Personal Information.Last Name','User.Roles','User.Last connection'],
		null,
		100,
		'Personal Information.Last Name',true,
		function (list) {
			window.list = list;
			list.grid.makeScrollable();
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
			var nb_selected = document.createElement("SPAN");
			nb_selected.innerHTML = "0";
			nb_selected.style.marginRight = "4px";
			nb_selected.style.marginLeft = "3px";
			var nb_selected_container = document.createElement("SPAN");
			nb_selected_container.appendChild(nb_selected);
			nb_selected_container.appendChild(document.createTextNode("selected:"));
			list.addHeader(nb_selected_container);
			var assign_roles = document.createElement("BUTTON");
			assign_roles.disabled = "disabled";
			assign_roles.className = "flat";
			assign_roles.innerHTML = "<img src='"+theme.build_icon("/static/user_management/role.png",theme.icons_10.add)+"'/> Assign roles";
			assign_roles.onclick = function() {
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
						var sel = list.grid.getSelectionByRowId();
						for (var i = 0; i < sel.length; ++i)
							users.push(list.getTableKeyForRow("Users", sel[i]));
						service.json("user_management","assign_roles",{users:users,roles:roles_id},function(result){
							window.top.status_manager.remove_status(status);
							list.grid.endLoading();
							if (result)
								list.reloadData();
						});
					});
					p.show();
				});
			};
			list.addHeader(assign_roles);
			var unassign_roles = document.createElement("BUTTON");
			unassign_roles.disabled = "disabled";
			unassign_roles.className = "flat";
			unassign_roles.innerHTML = "<img src='"+theme.build_icon("/static/user_management/role.png",theme.icons_10.remove)+"'/> Unassign roles";
			unassign_roles.onclick = function() {
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
						var sel = list.grid.getSelectionByRowId();
						for (var i = 0; i < sel.length; ++i)
							users.push(list.getTableKeyForRow("Users", sel[i]));
						service.json("user_management","unassign_roles",{users:users,roles:roles_id},function(result){
							window.top.status_manager.remove_status(status);
							list.grid.endLoading();
							if (result)
								list.reloadData();
						});
					});
					p.show();
				});
			};
			list.addHeader(unassign_roles);
			var remove = document.createElement("BUTTON");
			remove.className = "flat";
			remove.innerHTML = "<img src='"+theme.build_icon("/static/user_management/user_16.png",theme.icons_10.remove)+"'/> Remove";
			remove.onclick = function() {
				confirm_dialog("Are you sure you want to remove those users from this software ?",function(yes) {
					if (!yes) return;
					var users = [];
					var sel = list.grid.getSelectionByRowId();
					for (var i = 0; i < sel.length; ++i)
						users.push(list.getTableKeyForRow("Users", sel[i]));
					var locker = lock_screen(null, "Removing "+users.length+" user"+(users.length>1?"s":""));
					service.json("user_management","remove_users",{users:users},function(res) {
						unlock_screen(locker);
						list.reloadData();
					});
				});
			};
			list.addHeader(remove);
			list.grid.onselect = function(selection) {
				if (!selection || selection.length == 0) {
					nb_selected.innerHTML = "0";
					assign_roles.disabled = "disabled";
					unassign_roles.disabled = "disabled";
					remove.disabled = "disabled";
				} else {
					nb_selected.innerHTML = ""+selection.length;
					assign_roles.disabled = "";
					unassign_roles.disabled = "";
					remove.disabled = "";
				}
				layout.changed(list.header);
			};
			<?php }?>

			list.makeRowsClickable(function(row){
				if (typeof row.row_id == 'undefined') return;
				window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id),null,95,95);
			});
		}
	);
}
function synchUsers() {
	popup_frame(theme.icons_16._import, "Synchronize Users", "/dynamic/user_management/page/domain_auth", {feature:"AuthenticationSystem_UserList",url:"/dynamic/user_management/page/synch_users"}, null, null, function(frame,popup){
		popup.onclose = function() { window.list.reloadData(); };
	});
}
function newUser() {
	popup_frame(theme.build_icon("/static/user_management/user_16.png",theme.icons_10.add),"New User","/dynamic/user_management/page/new_user",null,null,null,function(frame,popup) {
		popup.onclose = function() { window.list.reloadData(); };
	});
}
</script>
<?php 
	}
		
}
?>