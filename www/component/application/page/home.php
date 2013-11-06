<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
TODO: home page<br/><br/>
<a href="/dynamic/contact/page/organization_profile">Organization profile</a><br/>
<!-- 
<a href="/dynamic/excel/page/test">Excel</a><br/>
<a href="/dynamic/data_import/page/build_excel_import?import=create_template">Create Excel Import Template</a><br/>
<a href="#" onclick="post_data('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new student',root_table:'Student'});return false;">Create a student</a><br/>
<a href="#" onclick="post_data('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new people',root_table:'People'});return false;">Create a people</a><br/>
<a href="#" onclick="post_data('/dynamic/data_model/page/create_data',{icon:'/static/application/icon.php?main=/static/students/student_32.png&small='+theme.icons_16.add+'&where=right_bottom',title:'Create new staff',root_table:'StaffPosition'});return false;">Create a staff</a><br/>
 -->
 
<?php
//echo "<br/><br/>".str_replace("\n","<br/>",htmlentities(var_export($_SERVER, true))); 		
	}
	
}
?>