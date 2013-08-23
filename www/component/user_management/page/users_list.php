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
		['Users.domain','Users.username','People.first_name','People.last_name'],
		function (list) {
			list.grid.setSelectable(true);
		}
	);
}
</script>
<?php 
	}
		
}
?>