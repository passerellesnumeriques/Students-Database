<?php 
class page_teachers extends Page {
	
	public function get_required_rights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->require_javascript("vertical_layout.js");
		$this->onload("new vertical_layout('top_container');");
		$this->require_javascript("section.js");
		theme::css($this, "section.css");
		$this->onload("section_from_html('current_teachers');");
		$this->onload("section_from_html('past_teachers');");
		$this->require_javascript("profile_picture.js");
		
		$teachers_dates = SQLQuery::create()->select("TeacherDates")->execute();
		$teachers = array();
		foreach ($teachers_dates as $td) {
			if (!isset($teachers[$td['people']]))
				$teachers[$td['people']] = array();
			array_push($teachers[$td['people']], $td);
		}
		$peoples_ids = array();
		$current_teachers_ids = array();
		$past_teachers_ids = array();
		foreach ($teachers as $people_id=>$dates) {
			array_push($peoples_ids, $people_id);
			$found = false;
			foreach ($dates as $d) if ($d["end"] == null) { $found = true; break; }
			if ($found)
				array_push($current_teachers_ids, $people_id);
			else
				array_push($past_teachers_ids, $people_id);
		}
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, true);

		global $selected_period_id, $periods;
		$periods = PNApplication::$instance->curriculum->getAcademicPeriodsWithYearName();
		if (isset($_GET["period"]))
			$selected_period_id = $_GET["period"];
		else {
			$current = null; $next = null; $next_start = null;
			$now = time();
			foreach ($periods as $period) {
				$start = datamodel\ColumnDate::toTimestamp($period["start"]);
				$end = datamodel\ColumnDate::toTimestamp($period["start"]);
				if ($start <= $now && $end >= $now) {
					$current = $period;
					break;
				} else if ($start > $now && ($next == null || $next_start > $start)) {
					$next = $period;
					$next_start = $start;
				}
			}
			if ($current <> null) $selected_period_id = $current["id"];
			else if ($next <> null) $selected_period_id = $next["id"];
			else $selected_period_id = null;
		}
		
		global $batch_periods, $batches, $subjects, $assignments, $classes_ids, $classes;
		if ($selected_period_id <> null) {
			$batch_periods = SQLQuery::create()->select("BatchPeriod")->whereValue("BatchPeriod","academic_period",$selected_period_id)->execute();
			$batches_ids = array();
			$batch_periods_ids = array();
			foreach ($batch_periods as $p) {
				array_push($batch_periods_ids, $p["id"]);
				if (!in_array($p["batch"], $batches_ids))
					array_push($batches_ids, $p["batch"]);
			}
			$batches = count($batches_ids) > 0 ? SQLQuery::create()->select("StudentBatch")->whereIn("StudentBatch","id",$batches_ids)->execute() : array();
			$subjects = count($batch_periods_ids) > 0 ? SQLQuery::create()->select("CurriculumSubject")->whereIn("CurriculumSubject","period",$batch_periods_ids)->execute() : array();
			$subjects_ids = array();
			foreach ($subjects as $s) array_push($subjects_ids, $s["id"]);
			$assignments = count($subjects_ids) > 0 ? SQLQuery::create()->select("TeacherAssignment")->whereIn("TeacherAssignment","subject",$subjects_ids)->execute() : array();
			$classes_ids = array();
			foreach ($assignments as $a) if (!in_array($a["class"], $classes_ids)) array_push($classes_ids, $a["class"]);
			$classes = count($classes_ids) > 0 ? SQLQuery::create()->select("AcademicClass")->whereIn("AcademicClass","id",$classes_ids)->execute() : array();
		}
?>
<div id='top_container' class="page_container" style="width:100%;height:100%">
	<div class="page_title">
		<img src='/static/curriculum/teacher_32.png' style="vertical-align:top"/>
		Teachers
	</div>
	<div style='margin:5px 10px 5px 10px'>
		Show load for period <select onchange="var u = new window.URL(location.href);u.params['period'] = this.value;location.href = u.toString();"><?php
		foreach ($periods as $period) {
			echo "<option value='".$period['id']."'";
			if ($selected_period_id == $period["id"]) echo " selected='selected'";
			echo ">".htmlentities("Year ".$period["year_name"].", ".$period['name'])."</option>";
		} 
		?></select>
	</div>
	<div id='list_container' style='overflow:auto' layout='fill'>
		<div id='current_teachers'
			title='Current Teachers'
			collapsable='true'
			style='margin:10px'
		>
		<?php $this->buildTeachersList($current_teachers_ids, $teachers, $peoples);?>
		</div>
		<div id='past_teachers'
			title='Previous Teachers'
			collapsable='true'
			collapsed='true'
			style='margin:10px'
		>
		<?php $this->buildTeachersList($past_teachers_ids, $teachers, $peoples);?>
		</div>
	</div>
	<div class="page_footer">
		<button class='action' onclick='new_teacher();'><img src='<?php echo theme::make_icon("/static/curriculum/teacher_16.png",theme::$icons_10["add"]);?>'/>New Teacher</button>
	</div>
</div>

<script type='text/javascript'>
function new_teacher() {
	require("popup_window.js", function() {
		var p = new popup_window("New Teacher", theme.build_icon("/static/curriculum/teacher_16.png",theme.icons_10.add), "");
		var frame = p.setContentFrame("/dynamic/curriculum/page/popup_create_teacher?ondone=reload");
		frame.reload = function() {
			location.reload();
		};
		p.show();
	});
}
</script>
<?php 
	}
	
	private function buildTeachersList($teachers_ids, $teachers_dates, $peoples) {
		global $selected_period_id;
		global $batch_periods, $batches, $subjects, $assignments, $classes_ids, $classes;
		global $periods;
?>
<table style='margin:10px'><tbody>
<?php 
if ($selected_period_id <> null) {
	echo "<tr>";
	echo "<th colspan=2 rowspan=2>Teacher</th>";
	foreach ($batch_periods as $period) {
		foreach ($batches as $b) if ($b["id"] == $period["batch"]) { $batch = $b; break; }
		echo "<th colspan=2>";
		echo "Batch ".htmlentities($batch["name"]).", ".htmlentities($period["name"]);
		echo "</th>";
	}
	echo "<th colspan=2>Total</th>";
	echo "</tr>";
	echo "<tr>";
	for ($i = 0; $i < count($batch_periods)+1; $i++) {
		echo "<th>Week</th><th>Period</th>";
	}
	echo "</tr>";
}

foreach ($teachers_ids as $people_id) {
	$people = null;
	foreach ($peoples as $p) if ($p["id"] == $people_id) { $people = $p; break; }
	echo "<tr>";
	$id = $this->generateID();
	echo "<td id='$id'></td>";
	$this->onload("new profile_picture('$id',50,50,'center','middle').loadPeopleStorage($people_id,".json_encode($people["picture"]).",".json_encode($people["picture_revision"]).");");
	echo "<td>";
	$id = $this->generateID();
	echo "<div id='$id'>".htmlentities($people["first_name"])."</div>";
	$this->onload("window.top.datamodel.registerCellSpan(window,'People','first_name',$people_id,document.getElementById('$id'));");
	$id = $this->generateID();
	echo "<div id='$id'>".htmlentities($people["last_name"])."</div>";
	$this->onload("window.top.datamodel.registerCellSpan(window,'People','last_name',$people_id,document.getElementById('$id'));");
	echo "</td>";
	$total = 0;
	foreach ($batch_periods as $period) {
		foreach ($periods as $ap) if ($ap["id"] == $period["academic_period"]) { $academic = $ap; break; }
		$total_period = 0;
		foreach ($assignments as $a) {
			if ($a["people"] <> $people_id) continue;
			foreach ($subjects as $s) if ($s["id"] == $a["subject"]) { $subject = $s; break; }
			if ($subject["period"] <> $period["id"]) continue;
			if ($subject["hours"] == null) continue;
			switch ($subject["hours_type"]) {
			case "Per week": $total_period += $subject["hours"]*($academic["weeks"]-$academic["weeks_break"]); break;
			case "Per period": $total_period += $subject["hours"];
			}
		}
		echo "<td>";
		echo number_format($total_period/($academic["weeks"]-$academic["weeks_break"]),2)."h";
		echo "</td>";
		echo "<td>";
		echo $total_period."h";
		echo "</td>";
		$total += $total_period;
	}
	if (count($batch_periods) > 0) {
		echo "<td>";
		echo number_format($total_period/($academic["weeks"]-$academic["weeks_break"]),2)."h";
		echo "</td>";
		echo "<td>";
		echo $total_period."h";
		echo "</td>";
	}
	echo "</tr>";
}
?>
</tbody></table>
<?php 
	}
	
}
?>