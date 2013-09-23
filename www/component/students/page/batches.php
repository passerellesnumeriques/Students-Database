<?php 
class page_batches extends Page {
	
	public function get_required_rights() { return array("consult_students_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new page_header('batches_header');");
		$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		
		?>
		<div id='batches_header' icon='/static/students/batch_32.png' title="Batches & Classes">
			<?php if ($can_edit) {?>
			<div class='button' onclick="create_new_batch();"><img src='<?php echo theme::$icons_16["add"];?>'/> Create New Batch</div>
			<?php }?>
		</div>
<?php 
		$this->add_javascript("/static/data_model/editable_cell.js");
		$batches = SQLQuery::create()->select("StudentBatch")->order_by("StudentBatch","start_date",false)->execute();
		foreach ($batches as $batch) {
			echo "<div id='batch_".$batch["id"]."' class='collapsable_section' style='margin:2px'>";
			echo "<div class='collapsable_section_header' style='padding:1px'>";
			if ($can_edit) {
				$span_id = $this->generate_id();
				echo "<span id='".$span_id."'></span>";
				$this->onload("new editable_cell('".$span_id."','StudentBatch','name',".$batch["id"].",'field_text',{max_length:100},".json_encode($batch["name"]).");");
			} else
				echo $batch["name"];
			echo "</div>";
			echo "<div class='collapsable_section_content'>";
			echo "<span style='padding-right:5px'>";
			$span_id = $this->generate_id();
			echo "<span id='".$span_id."'>Integration Date: ";
			if ($can_edit)
				$this->onload("new editable_cell('".$span_id."','StudentBatch','start_date',".$batch["id"].",'field_date',null,".json_encode($batch["start_date"]).");");
			else 
				echo $batch["start_date"];
			echo "</span></span>";
			echo "<span style='padding-right:5px'>";
			$span_id = $this->generate_id();
			echo "<span id='".$span_id."'>Graduation Date: </span>";
			if ($can_edit)
				$this->onload("new editable_cell('".$span_id."','StudentBatch','end_date',".$batch["id"].",'field_date',null,".json_encode($batch["end_date"]).");");
			else
				echo $batch["end_date"];
			echo "</span></span>";
			$students = SQLQuery::create()->select("Student")->where("batch",$batch["id"])->execute();
			$nb_in = 0; $nb_out = 0;
			foreach ($students as $s)
				if ($s["excludion_date"] === null) $nb_in++; else $nb_out++;
			echo "<a href=''>".$nb_in." student(s)</a>";
			if ($nb_out > 0) echo "<a href=''>".$nb_out." excluded</a>";
			
			echo "<table class='all_borders'>";
			echo "<tr><th>Period</th><th>Start<br/>End</th><th colspan=1>Classes</th></tr>";
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$batch["id"])->order_by("AcademicPeriod", "start_date", true)->execute();
			$classes = array();
			foreach ($periods as $period) {
				$period_classes = SQLQuery::create()->select("AcademicClass")->where("period", $period["id"])->order_by("AcademicClass", "specialization", true)->order_by("AcademicClass","name")->execute();
				$same = count($classes) == count($period_classes);
				if ($same) {
					foreach ($period_classes as $pc) {
						$found = false;
						foreach ($classes as $c) if ($pc["name"] == $c["name"]) { $found = true; break; }
						if (!$found) { $same = false; break; }
					}
				}
				if (!$same) {
					$specializations = array();
					foreach ($period_classes as $pc)
						if (!in_array($pc["specialization"], $specializations))
							array_push($specializations, $pc["specialization"]);
					if (count($specializations) > 0) {
						$spe = SQLQuery::create()->select("Specialization")->where_in("Specialization", "id", $specializations);
						$list = array();
						foreach ($period_classes as $pc)
							if (!isset($list[$pc["specialization"]]))
								$list[$pc[$specialization]] = array($pc);
							else
								array_push($list[$pc[$specialization]], $pc);
						echo "<tr>";
						echo "<td colspan=2></td>";
						$period_classes = array();
						foreach ($list as $spe_id=>$spe_classes) {
							foreach ($spe_classes as $c) array_push($period_classes, $c);
							echo "<td colspan=".count($spe_classes).">";
							foreach ($spe as $s) if ($s["id"] == $spe_id) { echo $s["name"]; break; }
							echo "</td>";
						}
						echo "</tr>";
						echo "<tr>";
					}
					echo "<tr>";
					echo "<td colspan=2></td>";
					foreach ($period_classes as $pc)
						echo "<td>".$pc["name"]."</td>";
					echo "</tr>";
					$classes = $period_classes;					
				} else {
					$list = array();
					foreach ($classes as $c) {
						foreach ($period_classes as $pc)
							if ($c["name"] == $pc["name"]) { array_push($list, $pc); break; }
					}
					$classes = $list;
				}
				echo "<tr>";
				echo "<td rowspan=2>";
				if ($can_edit) {
					$span_id = $this->generate_id();
					echo "<span id='".$span_id."'></span>";
					$this->onload("new editable_cell('".$span_id."','AcademicPeriod','name',".$period["id"].",'field_text',{max_length:100},".json_encode($period["name"]).");");
				} else
					echo $period["name"];
				echo "</td>";
				echo "<td>";
				if ($can_edit) {
					$span_id = $this->generate_id();
					echo "<span id='".$span_id."'></span>";
					$this->onload("new editable_cell('".$span_id."','AcademicPeriod','start_date',".$period["id"].",'field_date',null,".json_encode($period["start_date"]).");");
				} else
					echo $period["start_date"];
				echo "</td>";
				foreach ($classes as $class) {
					echo "<td rowspan=2>";
					echo "</td>";
				}
				echo "</tr>";
				echo "<tr>";
				echo "<td>";
				if ($can_edit) {
					$span_id = $this->generate_id();
					echo "<span id='".$span_id."'></span>";
					$this->onload("new editable_cell('".$span_id."','AcademicPeriod','end_date',".$period["id"].",'field_date',null,".json_encode($period["end_date"]).");");
				} else
					echo $period["end_date"];
				echo "</td>";
				echo "</tr>";
			}
			echo "</table>";
			echo "<div class='button' onclick='new_academic_period(".$batch["id"].")'><img src='".theme::$icons_16["add"]."' style='vertical-align:bottom'/> Add Academic Period (quarter, semester...)</div>";
			echo "</div>";
			echo "</div>";
			$this->onload("new collapsable_section('batch_".$batch["id"]."');");
		}
		echo "</table>";

require_once("component/data_model/page/entity_edit.inc");
create_entity_edition_table($this, "StudentBatch", null, "create_new_batch_table");
create_entity_edition_table($this, "AcademicPeriod", null, "create_academic_period_table");
?>
<script type='text/javascript'>
function create_new_batch() {
	var container = document.createElement("DIV");
	var error_div = document.createElement("DIV");
	error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
	var error_text = document.createElement("SPAN");
	error_text.style.color = "red";
	error_div.appendChild(error_text);
	error_div.style.visibility = "hidden";
	error_div.style.visibility = "absolute";
	container.appendChild(error_div);

	require("popup_window.js",function(){
		var popup = new popup_window("Create New Batch", "/static/students/batch_16.png", container);
		var table = new create_new_batch_table(container, function(error) {
			if (error) {
				error_text.innerHTML = error;
				error_div.style.visibility = 'visible';
				error_div.style.position = 'static';
				popup.disableButton('ok');
			} else {
				error_div.style.visibility = 'hidden';
				error_div.style.position = 'absolute';
				popup.enableButton('ok');
			}
		});
		popup.addOkCancelButtons(function(){
			popup.freeze();
			table.save(function(id){
				if (id) { popup.close(); location.reload(); return; }
				popup.unfreeze();
			});
		});
		popup.show(); 
	});
}
function new_academic_period(batch_id) {
	var container = document.createElement("DIV");
	var error_div = document.createElement("DIV");
	error_div.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> ";
	var error_text = document.createElement("SPAN");
	error_text.style.color = "red";
	error_div.appendChild(error_text);
	error_div.style.visibility = "hidden";
	error_div.style.visibility = "absolute";
	container.appendChild(error_div);

	require("popup_window.js",function(){
		var popup = new popup_window("Create New Academic Period", theme.icons_16.add, container);
		var table = new create_academic_period_table(container, function(error) {
			if (error) {
				error_text.innerHTML = error;
				error_div.style.visibility = 'visible';
				error_div.style.position = 'static';
				popup.disableButton('ok');
			} else {
				error_div.style.visibility = 'hidden';
				error_div.style.position = 'absolute';
				popup.enableButton('ok');
			}
		});
		popup.addOkCancelButtons(function(){
			popup.freeze();
			table.save(function(id){
				if (id) { popup.close(); location.reload(); return; }
				popup.unfreeze();
			},{batch:batch_id});
		});
		popup.show(); 
	});
}
</script>
		<?php
	}
	
}
?>