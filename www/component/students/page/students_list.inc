<?php 
abstract class page_students_list extends Page {
	
	public function get_required_rights() { return array("consult_students_list"); }
	
	public function create_list($icon, $title, $filters, $import_preset_data) {
		$this->add_javascript("/static/widgets/grid/grid.js");
		$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_students_list();");
		$container_id = $this->generate_id();
?>
<div style='width:100%;height:100%' id='<?php echo $container_id;?>'>
</div>
<script type='text/javascript'>
function init_students_list() {
	new data_list(
		'<?php echo $container_id;?>',
		'Student',
		['Personal Information.First Name','Personal Information.Last Name'],
		<?php echo $filters; ?>,
		function (list) {
			list.addTitle(<?php echo json_encode($icon).",".json_encode($title);?>);
			var import_students = document.createElement("DIV");
			import_students.className = "button";
			import_students.innerHTML = "<img src='"+theme.icons_16.import+"' style='vertical-align:bottom'/> Import Students";
			import_students.onclick = function() {
				require("data_import.js",function() {
					data_import(
						"<?php echo $container_id; ?>",
						"Student",
						<?php echo json_encode($import_preset_data); ?>,
						"Import Students"
					);
				});
			};
			list.addHeader(import_students);
			var create_student = document.createElement("DIV");
			create_student.className = "button";
			create_student.innerHTML = "<img src='/static/application/icon.php?main=/static/students/student_16.png&small="+theme.icons_10.add+"&where=right_bottom' style='vertical-align:bottom'/> Create Student";
			create_student.onclick = function() {
				var data = {
					icon: "/static/application/icon.php?main=/static/students/student_32.png&small="+theme.icons_16.add+"&where=right_bottom",
					title: "Create New Student",
					types: ["student"]
				};
				if (new_student_data)
					new_student_data(data);
				else
					data.redirect = location.href;
				post_data("/dynamic/people/page/create_people",data);
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