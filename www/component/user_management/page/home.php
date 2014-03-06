<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array("consult_user_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('user_management_page');");
?>
<div id='user_management_page' icon='/static/user_management/user_management_32.png' title='User Management' page='users_list'>
	<div text="Users" link="/dynamic/user_management/page/users_list" icon='/static/user_management/user_list.png' tooltip="List of users, assign roles"></div>
	<div text="Roles" link="/dynamic/user_management/page/roles" icon='/static/user_management/role.png' tooltip="Manage roles and their rights"></div>
</div>
<?php 
	}
	
}
?>