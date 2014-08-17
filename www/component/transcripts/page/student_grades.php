<?php 
class page_student_grades extends Page {
	
	public function getRequiredRights() { return array("consult_students_grades"); }
	
	public function execute() {
		$people_id = $_GET["people"];
		
		$published_grades = SQLQuery::create()->select("PublishedTranscriptStudentSubjectGrade")->whereValue("PublishedTranscriptStudentSubjectGrade","people",$people_id)->execute();
		if (count($published_grades) == 0) {
			echo "<div class='info_box'>No transcript yet</div>";
			return;
		}
		
		$transcripts_ids = array();
		foreach ($published_grades as $pg) if (!in_array($pg["id"], $transcripts_ids)) array_push($transcripts_ids, $pg["id"]);
		$transcripts = SQLQuery::create()->select("PublishedTranscript")->whereIn("PublishedTranscript","id",$transcripts_ids)->execute();
		
		$periods_ids = array();
		foreach ($transcripts as $t) if (!in_array($t["period"], $periods_ids)) array_push($periods_ids, $t["period"]);
		$periods = PNApplication::$instance->curriculum->getBatchPeriodsById($periods_ids);
		
		require_once("component/transcripts/page/design.inc");
		echo "<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>";
		echo "<div id='transcripts_tabs' style='display:flex;flex-direction:column;height:100%;padding:5px;'>";
		foreach ($transcripts as $t) {
			$period = null;
			foreach ($periods as $p) if ($p["id"] == $t["period"]) { $period = $p; break; }
			echo "<div title=".json_encode($period["name"].", ".$t["name"],JSON_HEX_APOS)." style='text-align:center;overflow:auto;height:100%;'>";
			echo "<div style='margin:5px;border-radius:5px;padding:10px;background-color:white;display:inline-block;box-shadow: 2px 2px 2px 0px #808080;border:1px solid #C0C0C0;'>";
			echo "<div style='text-align:left;display:inline-block;width:630px;height:810px;'>";
			generatePublishedTranscript($t["id"], $people_id);
			echo "</div>";
			echo "</div>";
			echo "</div>";
		}
		echo "</div>";
		echo "</div>";
		$this->requireJavascript("tabs.js");
		?>
		<script type='text/javascript'>
		var t = new tabs('transcripts_tabs');
		t.header.style.flex = "none";
		t.content.style.flex = "1 1 auto";
		</script>
		<?php 
	}
	
}
?>