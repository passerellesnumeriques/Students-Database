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
		['Users.domain','Users.username','People.first_name','People.last_name','Role.name'],
		function (list) {
			list.grid.setSelectable(true);
			var assign_roles = document.createElement("DIV");
			assign_roles.className = "button disabled";
			assign_roles.innerHTML = "<img src='/static/user_management/role.png'/> Assign roles";
			assign_roles.func = function() {
				// TODO
			};
			list.addHeader(assign_roles);
			list.grid.onselect = function(selection) {
				if (!selection || selection.length == 0) {
					assign_roles.className = "button disabled";
					assign_roles.onclick = null;
				} else {
					assign_roles.className = "button";
					assign_roles.onclick = assign_roles.func;
				}
			};
		}
	);
}
</script>
<?php 
	}
		
}
?>