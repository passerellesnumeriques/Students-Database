<?php 
class service_interview_status_results extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		$q = SQLQuery::create()->select("Applicant");
		PNApplication::$instance->people->joinPeople($q, "Applicant", "people", false);
		$q->whereValue("Applicant","exam_passer",1);
		$q->expression("SUM(case when `interview_attendance` IS NULL then 1 else 0 end)", "nb_not_yet");
		$q->expression("SUM(`interview_attendance`)", "nb_attendees");
		$q->expression("SUM(case when `interview_attendance` = 0 then 1 else 0 end)", "nb_absents");
		$q->expression("SUM(case when `interview_passer`=1 AND `sex`='M' then 1 else 0 end)", "nb_passers_boys");
		$q->expression("SUM(case when `interview_passer`=1 AND `sex`='F' then 1 else 0 end)", "nb_passers_girls");
		$stats = $q->executeSingleRow();
		
		if ($stats["nb_attendees"] == 0) {
			echo "<center><i class='problem'>No result yet</i></center>";
			return;
		}
		
		echo "<div class='page_section_title2'>Attendance</div>";
		echo "<ul>";
		echo "<li>".$stats["nb_attendees"]." attended</li>";
		if ($stats["nb_absents"] > 0)
			echo "<li>".$stats["nb_absents"]." absent(s)</li>";
		echo "<li><div class='".($stats["nb_not_yet"] > 0 ? "problem" : "ok")."'>".$stats["nb_not_yet"]." still to be done</div></li>";
		echo "</ul>";
		
		echo "<div class='page_section_title2'>Passers</div>";
		$total = $stats["nb_passers_boys"] + $stats["nb_passers_girls"];
		echo "<div>".$total."/".$stats["nb_attendees"]." applicant".($total > 1 ? "s" : "")." passed</div>";
		if ($total > 0) {
			echo "<ul>";
			echo "<li>".$stats["nb_passers_girls"]." (".number_format(intval($stats["nb_passers_girls"])*100/$total,1)."%) ".($stats["nb_passers_girls"] > 1 ? "are girls" : "is a girl")."</li>";
			echo "<li>".$stats["nb_passers_boys"]." (".number_format(intval($stats["nb_passers_boys"])*100/$total,1)."%) ".($stats["nb_passers_boys"] > 1 ? "are boys" : "is a boy")."</li>";
			echo "</ul>";
		}		
	}

}
?>