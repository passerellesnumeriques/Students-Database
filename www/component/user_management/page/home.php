<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array("consult_user_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('user_management_page');");
?>
<div id='user_management_page' icon='/static/user_management/user_management_32.png' title='User Management' page='users_list'>
	<span class='page_menu_item'><a href="users_list" target='user_management_page_content'><img src='/static/user_management/user_list.png'/>Users</a></span>
	<span class='page_menu_item'><a href="roles" target='user_management_page_content'><img src='/static/user_management/role.png'/>Roles</a></span>
</div>
<?php 
	}
	
}
?>