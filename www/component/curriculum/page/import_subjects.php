<?php 
class page_import_subjects extends Page {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function execute() {
		$batch_period_id = $_GET["period"];
		$batch_period = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","id",$batch_period_id)->executeSingleRow();
		$specializations = SQLQuery::create()->select("BatchPeriodSpecialization")->whereValue("BatchPeriodSpecialization","period",$batch_period_id)->join("BatchPeriodSpecialization","Specialization",array("specialization"=>"id"))->execute();
		$current_subjects = SQLQuery::create()->select("CurriculumSubject")->whereValue("CurriculumSubject","period",$batch_period_id)->execute();
		$categories = SQLQuery::create()->select("CurriculumSubjectCategory")->execute();
		$batches = SQLQuery::create()->select("StudentBatch")->execute();
		$periods = SQLQuery::create()->select("BatchPeriod")->execute();
		
		$available_subjects = array();
		if (count($specializations) == 0) {
			// no specialization
			$available_subjects[null] = SQLQuery::create()
				->select("CurriculumSubject")
				->whereNull("CurriculumSubject","specialization") // no specialization
				->whereNotValue("CurriculumSubject","period",$batch_period_id) // remove current period
				->join("CurriculumSubject","BatchPeriod",array("period"=>"id"))
				->whereNotValue("BatchPeriod","batch",$batch_period["batch"]) // remove current batch
				->orderBy("CurriculumSubject", "code")
				->fieldsOfTable("CurriculumSubject")
				->execute();
		} else {
			foreach ($specializations as $spe)
				$available_subjects[$spe["specialization"]] = SQLQuery::create()
					->select("CurriculumSubject")
					->whereValue("CurriculumSubject","specialization", $spe["specialization"])
					->whereNotValue("CurriculumSubject","period",$batch_period_id) // remove current period
					->join("CurriculumSubject","BatchPeriod",array("period"=>"id"))
					->whereNotValue("BatchPeriod","batch",$batch_period["batch"]) // remove current batch
					->orderBy("CurriculumSubject", "code")
					->fieldsOfTable("CurriculumSubject")
					->execute();
			$available_subjects[null] = SQLQuery::create()
				->select("CurriculumSubject")
				->whereNull("CurriculumSubject","specialization")
				->whereNotValue("CurriculumSubject","period",$batch_period_id) // remove current period
				->join("CurriculumSubject","BatchPeriod",array("period"=>"id"))
				->whereNotValue("BatchPeriod","batch",$batch_period["batch"]) // remove current batch
				->orderBy("CurriculumSubject", "code")
				->fieldsOfTable("CurriculumSubject")
				->execute();
		}
?>
<style type='text/css'>
.subjects_table {
	border-spacing: 0px;
}
.subjects_table td {
	white-space: nowrap;
	padding: 0px 2px;
}
.subjects_table tr>td:nth-child(5) {
	text-align: center;
}
</style>
<div style='background-color:white;padding:5px;overflow:visible'>
	<table class='subjects_table'>
		<tr id='header_row'>
			<th></th>
			<th>Code</th>
			<th>Name</th>
			<th>Hours</th>
			<th>Coef.</th>
			<th>From</th>
		</tr>
		<?php
		foreach ($available_subjects as $spe=>$subjects) {
			if ($spe <> null) {
				foreach ($specializations as $spec) if ($spec["id"] == $spe) { $sp = $spec; break; }
				echo "<tr><td colspan=6 style='background-color:#C0FFC0;font-weight:bold;text-align:center'>Specialization ".toHTML($sp["name"])."</td></tr>";
			} else if (count($available_subjects) > 1) {
				echo "<tr><td colspan=6 style='background-color:#C0FFC0;font-weight:bold;text-align:center'>Common (no specific specialization)</td></tr>";
			}
			foreach ($subjects as $subject) {
				$found = false;
				foreach ($current_subjects as $s) if ($s["code"] == $subject["code"]) { $found = true; break; }
				echo "<tr id='subject_".$subject["id"]."'".($found ? " style='color:#808080'" : "").">";
				echo "<td>";
				echo "<input type='checkbox'";
				if ($found) echo " disabled='disabled'";
				else echo " onchange='checkboxChanged(this);'";
				echo "/>";
				echo "</td>";
				echo "<td>".toHTML($subject["code"])."</td>";
				echo "<td>".toHTML($subject["name"])."</td>";
				echo "<td>";
				if ($subject["hours"]) {
					echo $subject["hours"]."h/";
					switch ($subject["hours_type"]) {
					case "Per week": echo "week"; break;
					case "Per period": echo "period"; break;
					}
				}
				echo "</td>";
				echo "<td>".$subject["coefficient"]."</td>";
				echo "<td>";
				foreach ($periods as $p) if ($p["id"] == $subject["period"]) { $period = $p; break; }
				foreach ($batches as $b) if ($b["id"] == $period["batch"]) { $batch = $b; break; }
				echo "Batch ".toHTML($batch["name"]).", ".toHTML($period["name"]);
				echo "</td>";
				echo "</tr>";
			}
		} 
		?>
	</table>
</div>
<script type='text/javascript'>
var subjects = <?php echo json_encode($subjects);?>;
		
var popup = window.parent.get_popup_window_from_frame(window);
popup.addIconTextButton(theme.icons_16._import, "Import Selected Subjects", "import", function() {
	popup.freeze();
	var to_import = [];
	for (var tr = document.getElementById('header_row').nextSibling; tr != null; tr = tr.nextSibling) {
		if (tr.nodeType != 1) continue;
		if (!tr.id) continue;
		if (!getCheckbox(tr).checked) continue;
		var id = tr.id.substring(8);
		for (var i = 0; i < subjects.length; ++i)
			if (subjects[i].id == id) { to_import.push(subjects[i]); break; }
	}
	if (to_import.length == 0) {
		popup.unfreeze();
		return;
	}
	popup.freeze_progress_sub("Importing subjects...", to_import.length, function(span,pb,sub) {
		var next = function() {
			if (to_import.length == 0) {
				<?php if (isset($_GET["onimport"])) echo "window.frameElement.".$_GET["onimport"]."();"?>
				popup.close();
				return;
			}
			var subject = to_import[0];
			to_import.splice(0,1);
			sub.removeAllChildren();
			sub.appendChild(document.createTextNode(subject.name));
			service.json("data_model","save_entity",{
				table: "CurriculumSubject",
				field_period: <?php echo $batch_period_id;?>,
				field_category: subject.category,
				field_specialization: subject.specialization,
				field_code: subject.code,
				field_name: subject.name,
				field_hours: subject.hours,
				field_hours_type: subject.hours_type,
				field_coefficient: subject.coefficient
			},function(res) {
				pb.addAmount(1);
				next();
			});
		};
		next();
	});
});

function getCode(tr) {
	return tr.childNodes[1].childNodes[0].nodeValue;
}
function getCheckbox(tr) {
	return tr.childNodes[0].childNodes[0];
}

function checkboxChanged(cb) {
	var this_tr = cb.parentNode.parentNode;
	var code = getCode(this_tr);
	for (var tr = document.getElementById('header_row').nextSibling; tr != null; tr = tr.nextSibling) {
		if (tr.nodeType != 1) continue;
		if (tr == this_tr) continue;
		if (!tr.id) continue;
		if (getCode(tr) != code) continue;
		tr.style.color = cb.checked ? "#808080" : "";
		getCheckbox(tr).disabled = cb.checked ? "disabled" : "";
	}
}
</script>
<?php 
	}
	
}
?>