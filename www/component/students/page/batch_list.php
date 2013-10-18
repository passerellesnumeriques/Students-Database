<?php 
class page_batch_list extends Page {
	
	public function get_required_rights() { return array("consult_students_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_batch_list();");
		$batch = SQLQuery::create()->select("StudentBatch")->where("id",$_GET["batch"])->execute_single_row();
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
			var import_students = document.createElement("DIV");
			import_students.className = "button";
			import_students.innerHTML = "<img src='"+theme.icons_16.import+"' style='vertical-align:bottom'/> Import Students";
			import_students.onclick = function() {
				require("data_import.js",function(){
					new data_import(
						document.body,
						"Student",
						[
						 	"Student.people>People.*"
						],
						[{table:'Student',column:'batch',value:<?php echo $_GET["batch"];?>}],
						"Students for Batch: <?php echo $batch["name"]?>"
					);
				});
			};
			list.addHeader(import_students);
		}
	);
}
</script>
<?php 
	}
		
}
?>