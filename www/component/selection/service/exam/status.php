<?php 
class service_exam_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		// overview on exam centers
		echo "<div class='page_section_title2'>Exam Centers</div>";
		echo "<div style='padding:5px'>";
		// number of exam centers
		$nb_centers = SQLQuery::create()->select("ExamCenter")->count("nb_centers")->executeSingleValue();
		if ($nb_centers == 0) {
			echo "<i style='color:red'>No exam center yet</i><br/>";
		} else {
			// TODO
		}
		echo "</div>";
		
		// overview on linked information sessions
		echo "<div class='page_section_title2'>Information Sessions</div>";
		echo "<div style='padding:5px'>";
		$total_nb_is = SQLQuery::create()->select("InformationSession")->count("nb")->executeSingleValue();
		$is_not_linked = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->whereNull("ExamCenterInformationSession", "exam_center")
			->field("InformationSession", "id")
			->executeSingleField();
		if (count($is_not_linked) == 0) {
			echo "<div style='color:green'>All (".$total_nb_is.") linked to an exam center</div>";
		} else {
			echo "<div style='color:DarkOrange'>".count($is_not_linked)." not linked to an exam center</div>";
		}
		echo "</div>";
		
		// overview on applicants
		echo "<div class='page_section_title2'>Applicants</div>";
		echo "<div style='padding:5px'>";
		$nb_applicants_no_exam_center = SQLQuery::create()->select("Applicant")->whereNull("Applicant","exam_center")->count("nb")->executeSingleValue();
		$nb_applicants_no_schedule = SQLQuery::create()->select("Applicant")->whereNotNull("Applicant","exam_center")->whereNull("Applicant", "exam_session")->count("nb")->executeSingleValue();
		$nb_applicants_ok = SQLQuery::create()->select("Applicant")->whereNotNull("Applicant","exam_center")->whereNotNull("Applicant", "exam_session")->count("nb")->executeSingleValue();
		$total_applicants = $nb_applicants_ok + $nb_applicants_no_schedule + $nb_applicants_no_exam_center;
		echo $total_applicants." applicant(s)<ul style='padding-left:20px'>";
		// TODO
		echo "</ul>";
		echo "</div>";
	}

}
?>