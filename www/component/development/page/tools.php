<?php 
class page_tools extends Page {
	
	public function get_required_rights() {
		return array();
	}
	
	protected function execute() {
?>
<a href="reset_db">Reset All Databases</a><br/>
<a href="reset_db?dev=yes">Reset DEV Domain: Database and insert test data</a><br/>
<a href="check_code">Check code</a><br/>
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