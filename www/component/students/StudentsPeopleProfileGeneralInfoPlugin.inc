<?php 
require_once("component/people/PeopleProfileGeneralInfoPlugin.inc");
class StudentsPeopleProfileGeneralInfoPlugin implements PeopleProfileGeneralInfoPlugin {
	
	public function getPriority() { return 100; }
	public function getIcon() { return "/static/students/student_16.png"; }
	public function getName() { return "Student"; }
	
	public function isValidFor($people_id, $types) { return in_array("student", $types); }

	public function prepareRequestForSection(&$q, $people_id) {
		$alias = $q->getTableAlias("Student");
		if ($alias == null) {
			$alias = $q->generateTableAlias();
			$people_alias = $q->getTableAlias("People");
			$q->join($people_alias, "Student", array("id"=>"people"), $alias);
		}
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->internalGetTable("Student");
		$display = $table->getDisplayHandler(null);
		require_once("component/data_model/DataPath.inc");
		$path = new DataPath_Table($table, null);
		$res = array();
		foreach ($display->getDisplayableData() as $data) {
			$aliases = $data->buildSQL($q, $path, array());
			$res[$data->getDisplayName()] = $aliases["data"];
		}
		$q->field("Student", "exclusion_date", "exclusion_date");
		$q->field("Student", "batch", "student_batch");
		return $res;
		
	}
	public function generateSection(&$page, $people_id, $row, $prepareRequest, $q) {
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->internalGetTable("Student");
		$display = $table->getDisplayHandler(null);
		$can_edit = PNApplication::$instance->user_management->has_right("manage_batches");
		?>
		<table>
			<?php
			$fields = array();
			foreach ($display->getDisplayableData() as $data) {
				echo "<tr>";
				echo "<td>";
				echo htmlentities($data->getDisplayName(),ENT_QUOTES,"UTF-8");
				echo "</td>";
				$id = $page->generateID();
				echo "<td id='$id'></td>";
				array_push($fields, array($data, $id));
				echo "</td>";
				echo "</tr>";
			}  
			?>
		</table>
		<script type='text/javascript'>
		function build_fields() {
			<?php
			require_once("component/data_model/page/utils.inc");
			foreach ($fields as $f) {
				$data = $f[0];
				$id = $f[1]; 
				$value = $row[$prepareRequest[$data->getDisplayName()]];
				datamodel_datadisplay($page, $data, $people_id, $value, null, $id, $can_edit);
			}
			?>
		}
		build_fields();
		</script>
		<?php 
		if ($can_edit) {
			$batch = PNApplication::$instance->curriculum->getBatch($row["student_batch"]);
			if ($row["exclusion_date"] == null) {
				echo "<div class='button' onclick='exclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])."'/> Exclude</div>";
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
						var p = new popup_window("Exclude <?php echo $row[$q->getFieldAlias("People","first_name")]." ".$row[$q->getFieldAlias("People","last_name")];?>", "<?php echo theme::make_icon("/static/students/student_16.png", theme::$icons_10["forbidden"])?>",content);
						p.addOkCancelButtons(function(){
							if (date.hasError() || reason.hasError()) return;
							p.freeze("Excluding student");
							service.json("students","exclude_student",{student:<?php echo $people_id;?>,date:date.getCurrentData(),reason:reason.getCurrentData()},function(res) {
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
				echo "<div class='button' onclick='unexclude();'><img src='".theme::make_icon("/static/students/student_16.png", theme::$icons_10["add"])."'/> Back after exclusion</div>";
				?>
				<script type='text/javascript'>
				function unexclude() {
					confirm_dialog("Are you sure you want this student back in PN ?",function(yes) {
						if (!yes) return;
						var lock = lock_screen(null, "Put <?php echo $row[$q->getFieldAlias("People","first_name")]." ".$row[$q->getFieldAlias("People","last_name")];?> back in PN !");
						service.json("students","exclude_student",{student:<?php echo $people_id;?>,date:null,reason:null},function(res) {
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
?>