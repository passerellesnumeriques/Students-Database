<?php 
class page_tools extends Page {
	
	public function getRequiredRights() {
		return array();
	}
	
	protected function execute() {
?>
<a href="reset_db">Reset All Databases</a><br/>
<a href="reset_db?dev=yes">Reset DEV Domain: Database and insert test data</a><br/>
<a href="clean_storage">Clean storage</a><br/>
<a href="export_storage">Export storage</a><br/>
<a href="check_code">Check code</a><br/>
<a href="export_data">Export Data</a><br/>
<br/>
<a href="insert_1000000_fake_students">Insert 1 000 000 fake students</a><br/>
<br/>
<a href="/">Back to application</a>
<br/>
<br/>
Server variable:<br/><pre>
<?php
echo htmlentities(var_export($_SERVER), true);
echo "</pre>";
	}
	
}
?>