<?php 
class page_users_list extends Page {
	
	public function get_required_rights() { return array("consult_user_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("new data_list('users_list','Users',['Users.domain','Users.username','People.first_name','People.last_name']);");
?>
<div style='width:100%;height:100%' id='users_list'>
</div>
<?php 
	}
		
}
?>