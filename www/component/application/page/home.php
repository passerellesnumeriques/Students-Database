<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
TODO: home page<br/><br/>
<a href="/dynamic/user_management/page/home">User Management</a><br/>
<a href="/dynamic/excel/page/test">Excel</a><br/>
<a href="/dynamic/data_import/page/build_excel_import?import=create_template">Create Excel Import Template</a><br/>
<a href="/dynamic/calendar/page/calendars">Calendars</a><br/>

<?php 		
	}
	
}
?>