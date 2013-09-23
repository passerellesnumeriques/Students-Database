<?php 
class page_home extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
?>
TODO: home page<br/><br/>
<a href="/dynamic/user_management/page/home">User Management</a><br/>
<a href="/dynamic/excel/page/test">Excel</a><br/>
<a href="/dynamic/data_import/page/build_excel_import?import=create_template">Create Excel Import Template</a><br/>
<a href="/dynamic/geography/page/test_geography">Geography</a><br/>
<a href="/dynamic/calendar/page/calendars">Calendars</a><br/>
<a href="/dynamic/students/page/batches">Batches</a><br/>

<div id='test_tree'></div>
<script type='text/javascript'>
require("tree.js",function(){
	var t = new tree('test_tree');
	t.addColumn(new TreeColumn(""));
	var cebu = new TreeItem([new TreeCell("Cebu")]); t.addItem(cebu);
	var cebu_city = new TreeItem([new TreeCell("Cebu City")]); cebu.addItem(cebu_city);
	var talamban = new TreeItem([new TreeCell("Talamban")]); cebu_city.addItem(talamban);
	var banilad = new TreeItem([new TreeCell("Banilad")]); cebu_city.addItem(banilad);
	var apas = new TreeItem([new TreeCell("Apas")]); cebu_city.addItem(apas);
	var oslob = new TreeItem([new TreeCell("Oslob")]); cebu.addItem(oslob);
	var negros = new TreeItem([new TreeCell("Negros")]); t.addItem(negros);
	var san_carlos = new TreeItem([new TreeCell("San Carlos")]); negros.addItem(san_carlos);
});
</script>

<?php 		
	}
	
}
?>