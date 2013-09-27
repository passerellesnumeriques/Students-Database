<?php 
class page_batch_list extends Page {
	
	public function get_required_rights() { return array("consult_students_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_batch_list();");
?>
<div style='width:100%;height:100%' id='batch_list'>
</div>
<script type='text/javascript'>
function init_batch_list() {
	new data_list(
		'batch_list',
		'Student',
		['People.first_name','People.last_name'],
		function (list) {
		}
	);
}
</script>
<?php 
	}
		
}
?>