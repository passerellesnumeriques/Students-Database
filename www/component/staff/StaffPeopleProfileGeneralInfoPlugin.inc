<?php
require_once("component/people/PeopleProfileGeneralInfoPlugin.inc"); 
class StaffPeopleProfileGeneralInfoPlugin implements PeopleProfileGeneralInfoPlugin {
	
	public function getPriority() { return 100; }
	public function getIcon() { return "/static/staff/staff_16.png"; }
	public function getName() { return "PN Staff"; }
	
	public function isValidFor($people_id, $types) { return in_array("staff", $types); }
	
	public function prepareRequestForSection(&$q, $people_id) {
	}
	public function generateSection(&$page, $people_id, $row, $prepareRequest, $q) {
		require_once("page/staff_info.inc");
		$container_id = $page->generateID();
		$fct = staff_info($page, $people_id, $container_id);
		?>
		<div style='padding:5px'>
			<div id='<?php echo $container_id;?>'></div>
		</div>
		<script type='text/javascript'>
		new <?php echo $fct;?>();
		</script>
		<?php 
	}
	
}
?>