<?php 
class page_datamodel extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		echo "<div style='padding:10px;text-align:center;font-size:x-large'>";
		echo "Global Data Model";
		echo "</div>";
		echo "<center>";
		echo "<img src='/dynamic/documentation/service/datamodel?component=all'/>";
		echo "</center>";
	}
	
}
?>