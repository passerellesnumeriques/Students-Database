<?php 
class page_teachers extends Page {
	
	public function getRequiredRights() { return array("consult_curriculum"); }
	
	public function execute() {
		$this->requireJavascript("vertical_layout.js");
		$this->onload("new vertical_layout('top_container');");
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
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, true);

?>
<div id='top_container' class="page_container" style="width:100%;height:100%">
	<div class="page_title">
		<img src='/static/curriculum/teacher_32.png' style="vertical-align:top"/>
		Teachers
	</div>
	<div id='list_container' style='overflow:auto;background-color:#e8e8e8;' layout='fill'>
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
	
	/**
	 * Create the table of teachers
	 * @param array $teachers_ids list of teachers
	 * @param array $teachers_dates dates of etachers
	 * @param array $peoples teachers information
	 */
	private function buildTeachersList($teachers_ids, $teachers_dates, $peoples) {
?>
<div style='background-color:white;padding:10px'>
<table><tbody>
<?php 
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
	echo "</tr>";
}
?>
</tbody></table>
</div>
<?php 
	}
	
}
?>