<?php 
class page_map extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		// get students list
		require_once("component/students_groups/page/TreeFrameSelection.inc");
		$students_ids = TreeFrameSelection::getStudentsIds();
		$this->requireJavascript("contact_map.js");
?>
<div id='container' style='width:100%;height:100%;'>
</div>
<script type='text/javascript'>
new contact_map('container','Map of Students','people',<?php echo json_encode($students_ids);?>,['Family']);
</script>
<?php 
	}
	
}
?>