<?php 
/* @var $model DataModel */

$category = $model->getDataCategory("Student");

$display = new datamodel\TableDataDisplay("Student", $category);
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("batch", "name", "Batch", true, null, "center"));
$display->addDataDisplay(new datamodel\ChoiceDataDisplay("specialization", "name", "Specialization", false, null, "center"));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exclusion_reason", "Exclusion Reason", true));
$display->addDataDisplay(new datamodel\SimpleDataDisplay("exclusion_date", "Exclusion Date", true));
$display->addFollow("people");
$display->addJoinFrom("people", array("types"), function($columns) {
	$types = PNApplication::$instance->people->parseTypes($columns["types"]);
	return in_array("student", $types);
});
$model->addTableDataDisplay($display);

class StudentDataScreen extends datamodel\GenericDataScreen {
	
	public function generate(&$page, $paths, $values, $prefilled_values, $structure_name) {
		$people_types = $values->getColumnValue("People", "types");
		$people_types = PNApplication::$instance->people->parseTypes($people_types);
		if (!in_array("student", $people_types)) return;
		
		parent::generate($page, $paths, $values, $prefilled_values, $structure_name);
		if ($values->hasColumnValue("Student","people") && PNApplication::$instance->user_management->has_right("manage_batches")) {
			// not new and can edit
			// => exclude button
			$batch = PNApplication::$instance->curriculum->getBatch($values->getColumnValue("Student","batch"));
			if ($values->getColumnValue("Student","exclusion_date") == null) {
				echo "<button class='action important' onclick='exclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])."'/> Exclude</button>";
				?>
				<script type='text/javascript'>
				function exclude() {
					require(["popup_window.js",["typed_field.js",["field_date.js","field_text.js"]]],function() {
						var content = document.createElement("TABLE");
						var tr, td;
						content.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.appendChild(document.createTextNode("Date of exclusion"));
						tr.appendChild(td = document.createElement("TD"));
						var date = new field_date(null,true,{can_be_empty:false,minimum:<?php echo json_encode($batch["start_date"]);?>,maximum:<?php echo json_encode($batch["end_date"]);?>});
						td.appendChild(date.getHTMLElement());
						content.appendChild(tr = document.createElement("TR"));
						tr.appendChild(td = document.createElement("TD"));
						td.appendChild(document.createTextNode("Reason of exclusion"));
						tr.appendChild(td = document.createElement("TD"));
						var reason = new field_text(null,true,{can_be_null:false,min_length:1,max_length:5000});
						td.appendChild(reason.getHTMLElement());
						var p = new popup_window("Exclude Student", "<?php echo theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])?>",content);
						p.addOkCancelButtons(function(){
							if (date.hasError() || reason.hasError()) return;
							p.freeze("Excluding student");
							service.json("students","exclude_student",{student:<?php echo $values->getColumnValue("Student","people");?>,date:date.getCurrentData(),reason:reason.getCurrentData()},function(res) {
								if (!res) { p.unfreeze(); return; }
								location.reload();
							});
						});
						p.show();
					});
				}
				</script>
				<?php 
			} else {
				echo "<button class='action important' onclick='unexclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["add"])."'/> Back after exclusion</button>";
				?>
				<script type='text/javascript'>
				function unexclude() {
					confirm_dialog("Are you sure you want this student back in PN ?",function(yes) {
						if (!yes) return;
						var lock = lock_screen(null, "Put student back in PN !");
						service.json("students","exclude_student",{student:<?php echo $values->getColumnValue("Student","people");?>,date:null,reason:null},function(res) {
							if (!res) { unlock_screen(lock); return; }
							location.reload();
						});
						
					});
				}
				</script>
				<?php 
			}
		}
	}
}

$model->addDataScreen(new StudentDataScreen($display));

$display = new datamodel\TableDataDisplay("StudentClass", $category);
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Period", "class.period.name", "center", "class.period"));
$display->addDataDisplay(new datamodel\ReadOnlyDataDisplay("Class", "class.name", "center", "class"));
$display->addFollow("people");
$model->addTableDataDisplay($display);

?>