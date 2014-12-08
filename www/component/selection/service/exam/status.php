<?php 
class service_exam_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		// number of exam centers
		$nb_centers = SQLQuery::create()->select("ExamCenter")->count("nb_centers")->executeSingleValue();

		if ($nb_centers == 0) {
			echo "<center><i class='problem' style='padding:5px'>No exam center yet</i></center>";
			return;
		}
		
		// overview on exam centers
		echo "<div class='page_section_title2'>Exam Centers</div>";
		echo "<div style='padding:0px 5px'>";
		echo $nb_centers." exam center".($nb_centers>1?"s":"")."<ul>";
		$q = SQLQuery::create()->select("ExamSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
		PNApplication::$instance->calendar->whereEventInThePast($q, true);
		$nb_sessions_done = $q->count()->executeSingleValue(); 
		$q = SQLQuery::create()->select("ExamSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "ExamSession", "event");
		PNApplication::$instance->calendar->whereEventInTheFuture($q, true);
		$nb_sessions_future = $q->count()->executeSingleValue();
		echo "<li>".$nb_sessions_done." session".($nb_sessions_done>1?"s":"")." already done</li>"; 
		echo "<li>".$nb_sessions_future." session".($nb_sessions_future>1?"s":"")." scheduled not yet done</li>"; 
		echo "</ul>";
		echo "</div>";
		
		// overview on linked information sessions
		echo "<div class='page_section_title2'>Information Sessions</div>";
		echo "<div style='padding:0px 5px'>";
		$total_nb_is = SQLQuery::create()->select("InformationSession")->count("nb")->executeSingleValue();
		$is_not_linked = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->whereNull("ExamCenterInformationSession", "exam_center")
			->field("InformationSession", "id")
			->executeSingleField();
		if (count($is_not_linked) == 0) {
			echo "<div class='ok'>All (".$total_nb_is.") linked to an exam center</div>";
		} else {
			echo "<a href='#' class='need_action' onclick=\"popup_frame(null,'Link Information Sessions to Exam Centers','/dynamic/selection/page/exam/link_is_with_exam_center?onsaved=saved',null,null,null,function(frame,pop){frame.saved=loadExamCenterStatus;});return false;\">".count($is_not_linked)." not linked to an exam center</a><br/>";
		}
		echo "</div>";
		
		// overview on applicants
		echo "<div class='page_section_title2'>Applicants</div>";
		echo "<div style='padding:0px 5px'>";
		$nb_applicants_no_exam_center = SQLQuery::create()->select("Applicant")->whereNotValue("Applicant","automatic_exclusion_step","Application Form")->whereNull("Applicant","exam_center")->count("nb")->executeSingleValue();
		$nb_applicants_ok = SQLQuery::create()->select("Applicant")->whereNotValue("Applicant","automatic_exclusion_step","Application Form")->whereNotNull("Applicant","exam_center")->whereNotNull("Applicant", "exam_session")->count("nb")->executeSingleValue();
		
		$applicants_no_schedule = SQLQuery::create()
			->select("Applicant")
			->whereNotNull("Applicant","exam_center")
			->whereNull("Applicant", "exam_session")
			->whereNotValue("Applicant","automatic_exclusion_step","Application Form")
			->groupBy("Applicant", "exam_center")
			->countOneField("Applicant", "people", "nb")
			->join("Applicant", "ExamCenter", array("exam_center"=>"id"))
			->field("ExamCenter", "name", "center_name")
			->field("ExamCenter", "id", "center_id")
			->execute();
		$nb_applicants_no_schedule = 0;
		foreach ($applicants_no_schedule as $center) $nb_applicants_no_schedule += $center["nb"];

		$total_applicants = $nb_applicants_ok + $nb_applicants_no_schedule + $nb_applicants_no_exam_center;
		echo $total_applicants." applicant(s)<ul style='padding-left:20px'>";
		echo "<li>";
		if ($nb_applicants_no_exam_center == 0)
			echo "<span class='ok'>All are assigned to an exam center</span>";
		else {
			echo "<a class='problem' href='#' onclick='applicantsNotLinkedToExamCenter();return false;'>".$nb_applicants_no_exam_center." not assigned to an exam center</a>";
			?>
			<script type='text/javascript'>
			function applicantsNotLinkedToExamCenter() {
				postData('/dynamic/selection/page/applicant/list', {
					title: "Applicants without Exam Center",
					filters: [
						{category:"Selection",name:"Excluded",force:true,data:{values:[0]}},
						{category:"Selection",name:"Exam Center",force:true,data:{values:['NULL']}}
					]
				});
			}
			</script>
			<?php 
		}
		echo "</li>";
		if ($nb_applicants_no_schedule > 0 || $total_applicants > $nb_applicants_no_exam_center) {
			echo "<li>";
			if ($nb_applicants_no_schedule == 0)
				echo "<span class='ok'>All ".($nb_applicants_no_exam_center > 0 ? "assigned ": "")."have a schedule</span>";
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
							window.top.popup_frame('/static/selection/exam/exam_center_16.png','Exam Center','/dynamic/selection/page/exam/center_profile?onsaved=saved&id=<?php echo $center["center_id"];?>',null,95,95,function(frame,pop) {
								frame.saved = function() { if (window.refreshPage) window.refreshPage(); else window.loadExamCenterStatus(); };
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