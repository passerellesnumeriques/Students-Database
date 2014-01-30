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
	new Excel('test_excel', function(xl) {
		xl.addSheet('Test',null,50,50,function(sheet){
			sheet.getCell(2,5).setValue("Hello");
			sheet.getColumn(3).setWidth(50);
			sheet.getColumn(4).setWidth(200);
			sheet.getRow(6).setHeight(30);
			sheet.getRow(7).setHeight(10);
	});
	});
}
</script>
<?php
	}
	
}
?>