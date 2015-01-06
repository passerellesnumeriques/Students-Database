<?php 
class service_interview_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		// number of centers
		$nb_centers = SQLQuery::create()->select("InterviewCenter")->count("nb_centers")->executeSingleValue();

		if ($nb_centers == 0) {
			echo "<center><i class='problem' style='padding:5px'>No interview center yet</i></center>";
			return;
		}
		
		// overview on centers
		echo "<div class='page_section_title2'>Interview Centers</div>";
		echo "<div style='padding:0px 5px'>";
		echo $nb_centers." interview center".($nb_centers>1?"s":"")."<ul>";
		$q = SQLQuery::create()->select("InterviewSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
		PNApplication::$instance->calendar->whereEventInThePast($q, true);
		$nb_sessions_done = $q->count()->executeSingleValue(); 
		$q = SQLQuery::create()->select("InterviewSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InterviewSession", "event");
		PNApplication::$instance->calendar->whereEventInTheFuture($q, true);
		$nb_sessions_future = $q->count()->executeSingleValue();
		echo "<li>".$nb_sessions_done." session".($nb_sessions_done>1?"s":"")." already done</li>"; 
		echo "<li>".$nb_sessions_future." session".($nb_sessions_future>1?"s":"")." scheduled not yet done</li>"; 
		echo "</ul>";
		echo "</div>";
		
		// overview on linked exam centers
		echo "<div class='page_section_title2'>From Exam Centers</div>";
		echo "<div style='padding:0px 5px'>";
		$total_nb_exam_centers = SQLQuery::create()->select("ExamCenter")->count("nb")->executeSingleValue();
		$centers_not_linked = SQLQuery::create()
			->select("ExamCenter")
			->join("ExamCenter", "InterviewCenterExamCenter", array("id"=>"exam_center"))
			->whereNull("InterviewCenterExamCenter", "interview_center")
			->field("ExamCenter", "id")
			->executeSingleField();
		if (count($centers_not_linked) == 0) {
			echo "<div class='ok'>All (".$total_nb_exam_centers.") linked to an interview center</div>";
		} else {
			echo "<a href='#' class='need_action' onclick=\"popupFrame(null,'Link Exam Centers to Interview Centers','/dynamic/selection/page/interview/link_centers?onsaved=saved',null,null,null,function(frame,pop){frame.saved=loadInterviewCenterStatus;});return false;\">".count($centers_not_linked)." not linked to an interview center</a><br/>";
		}
		echo "</div>";
		
		// overview on applicants
		echo "<div class='page_section_title2'>Applicants</div>";
		echo "<div style='padding:0px 5px'>";
		$nb_applicants_no_interview_center = SQLQuery::create()->select("Applicant")->whereValue("Applicant","exam_passer",1)->whereNull("Applicant","interview_center")->count("nb")->executeSingleValue();
		$nb_applicants_ok = SQLQuery::create()->select("Applicant")->whereValue("Applicant","exam_passer",1)->whereNotNull("Applicant","interview_center")->whereNotNull("Applicant", "interview_session")->count("nb")->executeSingleValue();
		
		$applicants_no_schedule = SQLQuery::create()
			->select("Applicant")
			->whereNotNull("Applicant","interview_center")
			->whereNull("Applicant", "interview_session")
			->whereValue("Applicant","exam_passer",1)
			->groupBy("Applicant", "interview_center")
			->countOneField("Applicant", "people", "nb")
			->join("Applicant", "InterviewCenter", array("interview_center"=>"id"))
			->field("InterviewCenter", "name", "center_name")
			->field("InterviewCenter", "id", "center_id")
			->execute();
		$nb_applicants_no_schedule = 0;
		foreach ($applicants_no_schedule as $center) $nb_applicants_no_schedule += $center["nb"];

		$total_applicants = $nb_applicants_ok + $nb_applicants_no_schedule + $nb_applicants_no_interview_center;
		echo $total_applicants." applicant(s) eligible for interview<ul style='padding-left:20px'>";
		echo "<li>";
		if ($nb_applicants_no_interview_center == 0)
			echo "<span class='ok'>All are assigned to an interview center</span>";
		else {
			echo "<a class='problem' href='#' onclick='applicantsNotLinkedToInterviewCenter();return false;'>".$nb_applicants_no_interview_center." not assigned to an interview center</a>";
			?>
			<script type='text/javascript'>
			function applicantsNotLinkedToInterviewCenter() {
				postData('/dynamic/selection/page/applicant/list', {
					title: "Applicants without Interview Center",
					filters: [
						{category:"Selection",name:"Excluded",force:true,data:{values:[0]}},
						{category:"Selection",name:"Interview Center",force:true,data:{values:['NULL']}}
					]
				});
			}
			</script>
			<?php 
		}
		echo "</li>";
		if ($nb_applicants_no_schedule > 0 || $total_applicants > $nb_applicants_no_interview_center) {
			echo "<li>";
			if ($nb_applicants_no_schedule == 0)
				echo "<span class='ok'>All ".($nb_applicants_no_interview_center > 0 ? "assigned ": "")."have a schedule</span>";
			else {
				echo "<a class='problem' href='#' onclick='applicantsNoSchedule(this);return false;'>".$nb_applicants_no_schedule." don't have a schedule</a>";
				?>
				<script type='text/javascript'>
				function applicantsNoSchedule(link) {
					require("context_menu.js",function() {
						var menu = new context_menu();
						<?php
						foreach ($applicants_no_schedule as $center) {
							echo "menu.addIconItem(null,".json_encode($center["nb"]." applicant(s) in ".$center["center_name"]).",function() {";
							?>
							window.top.popupFrame('/static/selection/interview/interview_16.png','Interview Center','/dynamic/selection/page/interview/center_profile?onsaved=saved&id=<?php echo $center["center_id"];?>',null,95,95,function(frame,pop) {
								frame.saved = function() { if (window.refreshPage) window.refreshPage(); else window.loadInterviewCenterStatus(); };
							});
							<?php 
							echo "});\n";
						} 
						?>
						menu.showBelowElement(link);
					});
				}
				</script>
				<?php 
			}
		}
		echo "</li>";
		echo "</ul>";
		echo "</div>";
	}
	
}
?>