<?php 
class page_test extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/excel/excel.js");
		$this->onload("test_excel();");
?>
<div style='width:100%;height:100%' id='test_excel'></div>
<script type='text/javascript'>
function test_excel() {
	var x = new Excel('test_excel', function() {
		x.addSheet('Test');
	});
}
</script>
<?php
	}
	
}
?>