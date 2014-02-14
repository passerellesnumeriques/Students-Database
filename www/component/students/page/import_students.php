<?php 
class page_import_students extends Page {
	
	public function get_required_rights() { return array("manage_batches"); }
	
	public function execute() {
		$input = json_decode($_POST["input"],true);
		require_once("component/data_model/Model.inc");
		require_once("component/people/page/import_people.inc");
		$data_list = array();
		array_push($data_list, DataModel::get()->getTable("Student")->getDataDisplay("Student", "Batch"));
		$fixed_data = array();
		if (isset($input["batch"]))
			array_push($fixed_data, array("category"=>"Student","name"=>"Batch","data"=>$input["batch"]));
		?>
		<script type='text/javascript'>
		function create_students(students, lock) {
			var get_data = function(category, data, student) {
				for (var i = 0; i < student.length; ++i)
					if (student[i].data.category == category && student[i].data.name == data)
						return student[i].value;
			};

			var errors = [];
			var next_student = function(i) {
				var s = students[i];
				var msg = "Creating Student: "+get_data("Personal Information","First Name",s)+" "+get_data("Personal Information","Last Name",s);
				set_lock_screen_content(lock,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> "+msg+"... ("+(i+1)+"/"+students.length+")");
				service.json("students","import_student",s,function(res) {
					if (!res) errors.push(get_data("Personal Information","First Name",s)+" "+get_data("Personal Information","Last Name",s));
					if (i == students.length-1) {
						if (errors.length > 0) {
							var msg = "Errors occured while importing the following students:<ul>";
							for (var j = 0; j < errors.length; ++j)
								msg += "<li>"+errors[j]+"</li>";
							msg += "</ul>";
							unlock_screen(lock);
							error_dialog(msg);
							return;
						}
						location.href = <?php echo json_encode($input["redirect"]);?>;
						return;
					}
					next_student(i+1);
				});
			};
			next_student(0);
		}
		</script>
		<?php 
		import_people(
			$this,
			"/static/application/icon.php?main=/static/students/student_32.png&small=".theme::$icons_16["add"]."&where=right_bottom",
			"Import Students",
			$data_list,
			$fixed_data,
			array(),
			"<img src='".theme::make_icon("/static/students/student_16.png",theme::$icons_10["add"])."'/> Import Students",
			"create_students"
		);
	}
	
}
?>