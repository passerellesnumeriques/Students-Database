<?php 
class page_teachers extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		$this->onload("sectionFromHTML('current_teachers');");
		$this->onload("sectionFromHTML('past_teachers');");
		$this->requireJavascript("profile_picture.js");
		
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
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, true, true);
		$can_edit = PNApplication::$instance->user_management->has_right("edit_curriculum");

		if (count($current_teachers_ids) > 0) {
			$current_period = PNApplication::$instance->curriculum->getCurrentAcademicPeriod();
			$q = SQLQuery::create()->select("TeacherAssignment");
			$q->join("TeacherAssignment","SubjectTeaching",array("subject_teaching"=>"id"));
			PNApplication::$instance->curriculum->joinSubjects($q, "SubjectTeaching", "subject", true);
			$q->whereValue("BatchPeriod","academic_period",$current_period["id"]);
			$q->whereIn("TeacherAssignment","people",$current_teachers_ids);
			$q->field("TeacherAssignment","people","teacher");
			$q->field("CurriculumSubject","name","subject_name");
			$q->groupBy("CurriculumSubject","id");
			$q->groupBy("TeacherAssignment","people");
			$current_assignments = $q->execute();
		} else
			$current_assignments = array();
		
		if (count($peoples_ids) > 0) {
			$q = SQLQuery::create()->select("TeacherAssignment");
			$q->join("TeacherAssignment","SubjectTeaching",array("subject_teaching"=>"id"));
			PNApplication::$instance->curriculum->joinSubjects($q, "SubjectTeaching", "subject", true, true, true);
			$q->where("`AcademicPeriod`.`end` < '".date("Y-m-d")."'");
			$q->whereIn("TeacherAssignment","people",$peoples_ids);
			$q->field("TeacherAssignment","people","teacher");
			$q->field("CurriculumSubject","name","subject_name");
			$q->field("BatchPeriod","name","period_name");
			$q->field("StudentBatch","name","batch_name");
			$q->groupBy("CurriculumSubject","id");
			$q->groupBy("TeacherAssignment","people");
			$q->orderBy("AcademicPeriod","start",false);
			$previous_assignments = $q->execute();
		} else
			$previous_assignments = array();

?>
<style type='text/css'>
.teachers_table {
	border-spacing: 0px;
}
.teacher_row {
}
.teacher_row:hover {
	background: linear-gradient(to bottom, #FFF0D0 0%, #F0D080 100%);
}
</style>
<div class="page_container" style="width:100%;height:100%;display:flex;flex-direction:column;">
	<div class="page_title" style="flex:none;">
		<img src='/static/teaching/teacher_32.png' style="vertical-align:top"/>
		Teachers
	</div>
	<div id='list_container' style='overflow:auto;background-color:#e8e8e8;flex:1 1 auto;'>
		<div id='current_teachers'
			title='Current Teachers'
			collapsable='true'
			style='margin:10px'
		>
		<?php $this->buildTeachersList($current_teachers_ids, $teachers, $peoples, $current_assignments, $previous_assignments);?>
		</div>
		<div id='past_teachers'
			title='Previous Teachers'
			collapsable='true'
			collapsed='false'
			style='margin:10px'
		>
		<?php $this->buildTeachersList($past_teachers_ids, $teachers, $peoples, null, $previous_assignments);?>
		</div>
	</div>
	<div class="page_footer" style="flex:none;">
		<?php
		$this->requireJavascript("people_search.js");
		$this->requireJavascript("custom_search.js");
		
		$search_id = $this->generateID();
		echo "<div style='display:inline-block' id='$search_id'></div>";
		$this->onload("new people_search('$search_id','teacher',function(people){ window.top.popupFrame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people='+people.id,null,95,95); });");
		if ($can_edit) {
		?>
		<button class='action green' onclick='new_teacher();'><img src='<?php echo theme::make_icon("/static/teaching/teacher_16.png",theme::$icons_10["add"]);?>'/>New Teacher</button>
		<?php } ?>
	</div>
</div>

<script type='text/javascript'>
function new_teacher() {
	require("popup_window.js", function() {
		var p = new popup_window("New Teacher", theme.build_icon("/static/teaching/teacher_16.png",theme.icons_10.add), "");
		var frame = p.setContentFrame("/dynamic/people/page/popup_new_person?type=teacher&ondone=reload");
		frame.reload = function() {
			location.reload();
		};
		p.show();
	});
}
</script>
<?php 
	}
	
	private function sortPeopleIds($ids, $peoples) {
		$res = array();
		foreach ($peoples as $p)
			if (in_array($p["id"], $ids)) array_push($res, $p["id"]);
		return $res;
	}
	
	/**
	 * Create the table of teachers
	 * @param array $teachers_ids list of teachers
	 * @param array $teachers_dates dates of etachers
	 * @param array $peoples teachers information
	 */
	private function buildTeachersList($teachers_ids, $teachers_dates, $peoples, $current_assignments, $previous_assignments) {
		$teachers_ids = $this->sortPeopleIds($teachers_ids, $peoples);
?>
<div style='background-color:white;padding:10px'>
<table class='teachers_table'><tbody>
<?php 
require_once("component/data_model/page/utils.inc");
foreach ($teachers_ids as $people_id) {
	$people = null;
	foreach ($peoples as $p) if ($p["id"] == $people_id) { $people = $p; break; }
	echo "<tr class='teacher_row' style='cursor:pointer' onclick=\"window.top.popupFrame('/static/people/profile_16.png','Profile','/dynamic/people/page/profile?people=".$people_id."',null,95,95);\">";
	$id = $this->generateID();
	echo "<td id='$id'></td>";
	$this->onload("new profile_picture('$id',50,50,'center','middle').loadPeopleStorage($people_id,".json_encode($people["picture"]).",".json_encode($people["picture_revision"]).");");
	echo "<td style='white-space:nowrap'>";
	$id = $this->generateID();
	echo "<div id='$id'>".toHTML($people["first_name"])."</div>";
	$this->onload("window.top.datamodel.registerCellSpan(window,'People','first_name',$people_id,document.getElementById('$id'));");
	$id = $this->generateID();
	echo "<div id='$id'>".toHTML($people["last_name"])."</div>";
	$this->onload("window.top.datamodel.registerCellSpan(window,'People','last_name',$people_id,document.getElementById('$id'));");
	echo "</td>";
	// dates
	$dates = null;
	foreach ($teachers_dates as $pid=>$d) if ($pid == $people_id) { $dates = $d; break; }
	$last_date = null;
	if ($dates <> null) foreach ($dates as $d) if ($d["end"] == null) { $last_date = $d; break; } else if ($last_date == null || strtotime($d["start"]) > strtotime($last_date["start"])) $last_date = $d;
	echo "<td style='padding-left:10px;white-space:nowrap'>";
	if ($last_date <> null) {
		echo "Started on ";
		datamodel_cell_here($this, PNApplication::$instance->user_management->has_right("edit_curriculum"), "TeacherDates", "start", $last_date["id"], $last_date["start"], null);
		echo "<br/>Until ";
		datamodel_cell_here($this, PNApplication::$instance->user_management->has_right("edit_curriculum"), "TeacherDates", "end", $last_date["id"], $last_date["end"], null);
	}
	echo "</td>";
	if ($current_assignments !== null) {
		echo "<td style='padding-left:10px;min-width:150px'>";
		$subjects = array();
		foreach ($current_assignments as $a) if ($a["teacher"] == $people_id) array_push($subjects, $a["subject_name"]);
		if (count($subjects) == 0)
			echo "Currently not assigned to any subject";
		else {
			echo "Currently assigned to: ";
			$first_subject = true;
			foreach ($subjects as $s) {
				if ($first_subject) $first_subject = false; else echo ", ";
				echo toHTML($s);
			}
		}
		echo "</td>";
	}
	echo "<td style='padding-left:10px'>";
	$subjects = array();
	foreach ($previous_assignments as $a) if ($a["teacher"] == $people_id) array_push($subjects, $a);
	if (count($subjects) == 0)
		echo "Never assigned to any subject";
	else {
		echo "Has taught: ";
		for ($i = 0; $i < count($subjects); $i++) {
			if ($i > 0) echo ", ";
			if ($i == 5) {
				echo "<i>... and ".(count($subjects)-5)." more</i>";
				break;
			}
			echo toHTML($subjects[$i]["subject_name"])." (Batch ".toHTML($subjects[$i]["batch_name"])." ".toHTML($subjects[$i]["period_name"]).")";
		}
	}
	echo "</td>";
	echo "</tr>";
}
?>
</tbody></table>
</div>
<?php 
	}
	
}
?>