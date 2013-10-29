<?php 
class page_batches extends Page {
	
	public function get_required_rights() { return array("consult_students_list"); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/page_header.js");
		$this->onload("new page_header('batches_header');");
		$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		$all_spe = SQLQuery::create()->select("Specialization")->execute();
		
		require_once("component/data_model/page/utils.inc");
		?>
		<div id='batches_header' icon='/static/students/batch_32.png' title="Batches & Classes">
			<?php if ($can_edit) {?>
			<div class='button' onclick="create_new_batch();"><img src='<?php echo theme::$icons_16["add"];?>'/> Create New Batch</div>
			<?php }?>
			<div style="display:inline-block">
				List of specializations: 
				<?php
				$first = true;
				foreach ($all_spe as $spe) {
					if ($first) $first = false; else echo ", ";
					$span_id = $this->generate_id();
					echo "<span id='".$span_id."'></span>";
					datamodel_cell($this, $span_id, $can_edit, "Specialization", "name", $spe["id"], null, $spe["name"]);
					if ($can_edit) {
						echo "<img src='".theme::$icons_10["remove"]."' style='vertical-align:bottom;padding-left:2px;padding-right:2px;cursor:pointer;' title='Remove specialization'";
						$used = SQLQuery::create()->select("AcademicClass")->where("specialization",$spe["id"])->count("nb")->execute_single_value();
						if ($used == 0)
							echo " onclick='remove_specialization(".$spe["id"].");stopEventPropagation(event);return false;'";
						else
							echo " onclick=\"error_dialog('You cannot remove this specialization because it is still used by ".$used." classes');stopEventPropagation(event);return false;\"";
						echo "/>";
					}
				}
				if ($can_edit) { 
				?>
				<img class='button' src='<?php echo theme::$icons_16["add"];?>' style='vertical-align:bottom' onclick='create_specialization();' title='Create a new specialization'/>
				<?php } ?>
			</div>
		</div>
<?php 
		$batches = SQLQuery::create()->select("StudentBatch")->order_by("StudentBatch","start_date",false)->execute();
		foreach ($batches as $batch) {
			echo "<div id='batch_".$batch["id"]."' class='collapsable_section' style='margin:2px'>";
			echo "<div class='collapsable_section_header' style='padding:1px'>";
			$span_id = $this->generate_id();
			echo "<span id='".$span_id."'></span>";
			datamodel_cell($this, $span_id, $can_edit, "StudentBatch", "name", $batch["id"], null, $batch["name"]);
			if ($can_edit) {
				echo "<img src='".theme::$icons_16["remove"]."' style='vertical-align:bottom;padding-left:3px;cursor:pointer;' onclick='remove_batch(".$batch["id"].");stopEventPropagation(event);return false;' title='Remove batch'/>";
			}
			echo "</div>";
			echo "<div class='collapsable_section_content' style='padding:3px'>";
			echo "<span style='padding-right:5px'>";
			$span_id = $this->generate_id();
			echo "<span id='".$span_id."'>Integration Date: ";
			datamodel_cell($this, $span_id, $can_edit, "StudentBatch", "start_date", $batch["id"], null, $batch["start_date"]);
			echo "</span></span>";
			echo "<span style='padding-right:5px'>";
			$span_id = $this->generate_id();
			echo "<span id='".$span_id."'>Graduation Date: </span>";
			datamodel_cell($this, $span_id, $can_edit, "StudentBatch", "end_date", $batch["id"], null, $batch["end_date"]);
			echo "</span></span>";
			$students = SQLQuery::create()->select("Student")->where("batch",$batch["id"])->execute();
			$nb_in = 0; $nb_out = 0;
			foreach ($students as $s)
				if ($s["exclusion_date"] === null) $nb_in++; else $nb_out++;
			echo "<a href='/dynamic/students/page/batch_list?batch=".$batch["id"]."'>".$nb_in." student(s)</a>";
			if ($nb_out > 0) echo "<a href=''>".$nb_out." excluded</a>";

			// retrieve perdios, classes per period, and calculate max number of classes
			$periods = SQLQuery::create()->select("AcademicPeriod")->where("batch",$batch["id"])->order_by("AcademicPeriod", "start_date", true)->execute();
			$max_classes = 0;
			foreach ($periods as &$period) {
				$period_classes = SQLQuery::create()->select("AcademicClass")->where("period", $period["id"])->order_by("AcademicClass", "specialization", true)->order_by("AcademicClass","name")->execute();
				$period["classes"] = $period_classes;
				if (count($period_classes) > $max_classes) $max_classes = count($period_classes);
			}
			
			?>
			<style type='text/css'>
			.periods {
				margin-top: 3px;
				margin-bottom: 2px;
				border: 2px solid black;
				border-collapse: collapse;
				border-spacing: 0;
			}
			.periods tr, .periods td, .periods th {
				border: 1px solid black;
			}
			.periods th {
				background-color: #D0D0E0;
			}
			.periods td.class_name {
				text-align: center;
				font-weight: bold;
				background-color: #D0E0D0;
			}
			.periods td.skip {
				background-color: #F0F0F0;
			}
			</style>
			<?php 
			echo "<table class='periods'>";
			echo "<tr><th>Period</th><th>Start<br/>End</th><th colspan=".($max_classes == 0 ? 1 : $max_classes).">Classes</th>".($can_edit?"<th></th>":"")."</tr>";
			$classes = array();
			foreach ($periods as &$period) {
				$period_classes = $period["classes"];
				// check if classes are the same as the previous period
				$same = count($classes) == count($period_classes);
				if ($same) {
					foreach ($period_classes as $pc) {
						$found = false;
						foreach ($classes as $c) if ($pc["name"] == $c["name"]) { $found = true; break; }
						if (!$found) { $same = false; break; }
					}
				}
				if (!$same) {
					// not the same: add a line with specializations and classes names
					$specializations = array();
					foreach ($period_classes as $pc)
						if ($pc["specialization"] <> null && !in_array($pc["specialization"], $specializations))
							array_push($specializations, $pc["specialization"]);
					if (count($specializations) > 0) {
						$spe = SQLQuery::create()->select("Specialization")->where_in("Specialization", "id", $specializations)->execute();
						$list = array();
						foreach ($period_classes as $pc)
							if (!isset($list[$pc["specialization"]]))
								$list[$pc["specialization"]] = array($pc);
							else
								array_push($list[$pc["specialization"]], $pc);
						echo "<tr>";
						echo "<td colspan=2 rowspan=2 class='skip'></td>"; // skip period name and dates
						$period_classes = array();
						foreach ($list as $spe_id=>$spe_classes) {
							foreach ($spe_classes as $c) array_push($period_classes, $c);
							echo "<td class='class_name' colspan=".count($spe_classes).">";
							foreach ($spe as $s) if ($s["id"] == $spe_id) { echo $s["name"]; break; }
							echo "</td>";
						}
						echo "<td rowspan=2 class='skip'></td>"; // skip actions
						echo "</tr>";
					}
					echo "<tr>";
					if (count($specializations) == 0)
						echo "<td colspan=2 class='skip'></td>"; // skip period name and dates
					foreach ($period_classes as $pc) {
						if ($can_edit) {
							$id = $this->generate_id();
							echo "<td id='$id' class='class_name'></td>";
							$this->onload("classname_field('$id',".json_encode($pc["name"]).",".$pc["id"].");");
						} else
							echo "<td class='class_name'>".htmlentities($pc["name"])."</td>";
					}
					if (count($specializations) == 0)
						echo "<td class='skip'></td>"; // skip actions
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
				echo "<td rowspan=2>"; // period name, on 2 rows for dates
				$span_id = $this->generate_id();
				echo "<span id='".$span_id."'></span>";
				datamodel_cell($this, $span_id, $can_edit, "AcademicPeriod", "name", $period["id"], null, $period["name"]);
				echo "</td>";
				echo "<td>"; // start date
				$span_id = $this->generate_id();
				echo "<span id='".$span_id."'></span>";
				datamodel_cell($this, $span_id, $can_edit, "AcademicPeriod", "start_date", $period["id"], null, $period["start_date"]);
				echo "</td>";
				// class list
				if (count($classes) == 0)
					echo "<td rowspan=2 colspan=".($max_classes == 0 ? 1 : $max_classes)."></td>";
				else {
					foreach ($classes as $class) {
						echo "<td rowspan=2>";
						$nb_students = SQLQuery::create()->select("StudentClass")->where("class",$class["id"])->count()->execute_single_value();
						echo "<a href='todo'>".$nb_students." student".($nb_students > 1 ? "s" : "")."</a>";
						echo "</td>";
					}
					$rem = $max_classes - count($classes);
					if ($rem > 0) echo "<td colspan=".$rem."></td>";
				}
				// action cell
				if ($can_edit) {
					echo "<td rowspan=2>";
					echo "<div class='button' onclick='add_class(".$period["id"].")'><img src='/static/application/icon.php?main=/static/students/batch_16.png&small=".theme::$icons_10["add"]."&where=right_bottom'/> Add class</div>";
					echo "<div class='button' onclick='remove_period(".$period["id"].");stopEventPropagation(event);return false;'><img src='".theme::$icons_16["remove"]."' style='vertical-align:bottom;padding-left:3px;cursor:pointer;'/> Remove period</div>";
					echo "</td>";
				}
				echo "</tr>";
				echo "<tr>";
				echo "<td>"; // end date
				$span_id = $this->generate_id();
				echo "<span id='".$span_id."'></span>";
				datamodel_cell($this, $span_id, $can_edit, "AcademicPeriod", "end_date", $period["id"], null, $period["end_date"]);
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

require_once("component/data_model/page/table_datadisplay_edit.inc");
table_datadisplay_edit($this, "StudentBatch", null, null, "create_new_batch_table");
table_datadisplay_edit($this, "AcademicPeriod", null, null, "create_academic_period_table");
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
function remove_batch(batch_id) {
	confirm_dialog("Are you sure you want to remove this batch including all its content ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"StudentBatch",row_key:batch_id},function(res){
			unlock_screen(lock);
			if (!res) return;
			location.reload();
		});
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
function remove_period(period_id) {
	confirm_dialog("Are you sure you want to remove this academic period and its content ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"AcademicPeriod",row_key:period_id},function(res){
			unlock_screen(lock);
			if (!res) return;
			location.reload();
		});
	});
}
function create_specialization() {
	input_dialog(theme.icons_16.add,"Create Specialization","Name of the specialization","",100,
		function(name){
			if (name.length == 0)
				return "Please enter a name";
			if (!name.checkVisible())
				return "Please enter a name with visible characters";
			var spe = [<?php
			$first = true;
			foreach ($all_spe as $spe) {
				if ($first) $first = false; else echo ",";
				echo json_encode($spe["name"]);
			}  
			?>];
			for (var i = 0; i < spe.length; ++i)
				if (spe[i].toLowerCase() == name.toLowerCase())
					return "This specialization already exists";
			return null;
		},function(name){
			if (!name) return;
			var lock = lock_screen();
			service.json("data_model","save_entity",{table:"Specialization",field_name:name},function(res){
				unlock_screen(lock);
				if (!res) return;
				location.reload();
			});
		}
	);
}
function remove_specialization(spe_id) {
	confirm_dialog("Are you sure you want to remove this specialization ?",function(yes){
		if (!yes) return;
		var lock = lock_screen();
		service.json("data_model","remove_row",{table:"Specialization",row_key:spe_id},function(res){
			unlock_screen(lock);
			if (!res) return;
			location.reload();
		});
	});
}
function add_class(period_id) {
	require("popup_window.js",function(){
		var p = new popup_window("New class", "/static/application/icon.php?main=/static/students/batch_16.png&small="+theme.icons_10.add+"&where=right_bottom","");
		var frame = p.setContentFrame("/dynamic/students/page/new_class?period="+period_id);
		var w = window;
		p.addOkCancelButtons(function(){
			if (!getIFrameWindow(frame).validate()) return;
			getIFrameWindow(frame).submit(function(ok){
				if (ok) location.reload();
			});
		});
		p.show();
	});
}
function classname_field(container_id, class_name, class_id) {
	var not_edit, edit;
	var f = new field_text(class_name, false, {max_length:100,min_length:1});
	document.getElementById(container_id).appendChild(f.getHTMLElement());
	
	not_edit = function(class_name) {
		f.setData(class_name);
		f.setOriginalData(class_name);
		f.setEditable(false);
		var e = f.getHTMLElement();
		e.title = "Click to rename the class";
		e.onmouseover = function() { this.style.textDecoration = 'underline'; };
		e.onmouseout = function() { this.style.textDecoration = 'none'; };
		e.onclick = function() {
			edit(class_name);
		};
	};
	edit = function(class_name) {
		service.json("data_model","lock_row",{table:"AcademicClass",row_key:class_id},function(res) {
			var lock_id = res.lock;
			window.database_locks.add_lock(lock_id);
			f.setData(class_name);
			f.setOriginalData(class_name);
			f.setEditable(true);
			var e = f.getHTMLElement();
			e.title = "";
			e.onmouseover = null;
			e.onmouseout = null;
			e.onclick = null;
			f.input.onblur = function() {
				var name = f.getCurrentData();
				if (name == class_name) { not_edit(name); return; }
				var lock = lock_screen();
				service.json("data_model","save_entity",{table:"AcademicClass",key:class_id,field_name:name,lock:lock_id},function(res) {
					unlock_screen(lock);
					location.reload();
				});
			};
			f.input.focus();
		});
	};
	not_edit(class_name);
}
</script>
		<?php
	}
	
}
?>