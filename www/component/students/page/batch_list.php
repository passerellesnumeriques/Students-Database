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
			var create_student = document.createElement("DIV");
			create_student.className = "button";
			create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
			create_student.onclick = function() {
				post_data("/dynamic/people/page/create_people",{
					icon: "/static/application/icon.php?main=/static/students/student_32.png&small="+theme.icons_16.add+"&where=right_bottom",
					title: "Create New Student",
					people_type: "student",
					redirect: "/dynamic/students/page/batch_list?batch=<?php echo $_GET["batch"];?>",
					student_batch: <?php echo $_GET["batch"];?>
				});
			};
			list.addHeader(create_student);
	}
	);
}
</script>
<?php 
	}
		
}
?>