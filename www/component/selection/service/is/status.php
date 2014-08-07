<?php 
class service_is_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		// *** Sessions ***

		// number of sessions done
		$q = SQLQuery::create()->select("InformationSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InformationSession","date");
		PNApplication::$instance->calendar->whereEventInThePast($q);
		$q->count("nb_sessions");
		$q->sum("InformationSession","number_boys_expected", "boys_expected");
		$q->sum("InformationSession","number_girls_expected", "girls_expected");
		$q->sum("InformationSession","number_boys_real", "boys_real");
		$q->sum("InformationSession","number_girls_real", "girls_real");
		$sessions_done = $q->executeSingleRow();
		// number of sessions in the future
		$q = SQLQuery::create()->select("InformationSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InformationSession","date");
		PNApplication::$instance->calendar->whereEventInTheFuture($q, false);
		$q->count("nb_sessions");
		$q->sum("InformationSession","number_boys_expected", "boys_expected");
		$q->sum("InformationSession","number_girls_expected", "girls_expected");
		$q->sum("InformationSession","number_boys_real", "boys_real");
		$q->sum("InformationSession","number_girls_real", "girls_real");
		$sessions_future = $q->executeSingleRow();
		// number of session without a date
		$q = SQLQuery::create()->select("InformationSession");
		$q->whereNull("InformationSession", "date");
		$q->count("nb_sessions");
		$q->sum("InformationSession","number_boys_expected", "boys_expected");
		$q->sum("InformationSession","number_girls_expected", "girls_expected");
		$q->sum("InformationSession","number_boys_real", "boys_real");
		$q->sum("InformationSession","number_girls_real", "girls_real");
		$sessions_no_date = $q->executeSingleRow();
		
		// total number of sessions
		$nb_sessions = $sessions_done["nb_sessions"] + $sessions_future["nb_sessions"] + $sessions_no_date["nb_sessions"];

		if ($nb_sessions == 0) {
			// nothing started yet
			echo "<center><i class='problem' style='padding:5px'>No Information Session Yet</i></center>";
			return;
		}
		
		// check if we have sessions without host
		$missing_host = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session"), null, array("host"=>1))
			->whereNull("InformationSessionPartner", "host")
			->field("InformationSession", "id", "id")
			->field("InformationSession", "name", "name")
			->execute();
		
		if ($nb_sessions == $sessions_done["nb_sessions"]) {
			// all sessions are done
			echo "<div class='page_section_title2'>Sessions: <span class='ok'>".$nb_sessions.", all done.</span></div>";
			if (count($missing_host) > 0) {
				echo "<div style='padding:0px 5px'>";
				$this->createWarningLink($missing_host, "without hosting partner");
				echo "</div>";				
			}
		} else {
			echo "<div class='page_section_title2'>Sessions: ".$nb_sessions."</div>";
			echo "<div style='padding:0px 5px;'>";
			
			echo "<ul>";
				echo "<li>";
					echo ($sessions_done["nb_sessions"])." done";
				echo "</li>";
				echo "<li>";
					echo ($sessions_future["nb_sessions"])." planned";
				echo "</li>";
				if ($sessions_no_date["nb_sessions"] > 0) {
					echo "<li>";
						echo "<span class='problem'>";
						echo ($sessions_no_date["nb_sessions"])." without date yet";
						echo "</span>";
					echo "</li>";
				}
			echo "</ul>";
		
			$this->createWarningLink($missing_host, "without hosting partner");
	
			echo "</div>";
		}

		// *** ATTENDANCE ***
		echo "<div class='page_section_title2'>Attendance</div>";
		echo "<div style='padding:0px 5px'>";
		
		$separate = $component->getOneConfigAttributeValue("separate_boys_girls_IS");
		$total_boys_expected = $sessions_done["boys_expected"] + $sessions_future["boys_expected"] + $sessions_no_date["boys_expected"];
		$total_girls_expected = $sessions_done["girls_expected"] + $sessions_future["girls_expected"] + $sessions_no_date["girls_expected"];
		$total_expected = $total_boys_expected + $total_girls_expected;
		$total_boys_real = $sessions_done["boys_real"] + $sessions_future["boys_real"] + $sessions_no_date["boys_real"];
		$total_girls_real = $sessions_done["girls_real"] + $sessions_future["girls_real"] + $sessions_no_date["girls_real"];
		$total_real = $total_boys_real + $total_girls_real;
		
		echo $total_expected." attendees expected";
		if ($separate)
			echo " (".$total_boys_expected." boys and ".$total_girls_expected." girls)";
		if ($nb_sessions == $sessions_done["nb_sessions"]) {
			// all sessions done
			echo "<br/>".$total_real." attendees";
			if ($separate)
				echo " (".$total_boys_real." boys and ".$total_girls_real." girls)";
			if ($total_expected > 0)
				echo " = ".floor($total_real*100/$total_expected)."% of expectation";
			echo "<br/>";
		} else {
			echo "<ul>";
				echo "<li>";
					echo $total_real." attendees so far";
					if ($separate)
						echo " (".$total_boys_real." boys and ".$total_girls_real." girls)";
				echo "</li>";
				echo "<li>";
					$remain_boys_expected = $sessions_future["boys_expected"] + $sessions_no_date["boys_expected"];
					$remain_girls_expected = $sessions_future["girls_expected"] + $sessions_no_date["girls_expected"];
					$remain_total = $remain_boys_expected + $remain_girls_expected;
					echo $remain_total." additional expected";
					if ($separate)
						echo " (".$remain_boys_expected." boys and ".$remain_girls_expected." girls)";
				echo "</li>";
			echo "</ul>";
		}
		
		// check all sessions have a number of expected
		$missing_expected = SQLQuery::create()
			->select("InformationSession")
			->where("NOT `number_boys_expected` > 0")
			->where("NOT `number_girls_expected` > 0")
			->field("InformationSession", "id", "id")
			->field("InformationSession", "name", "name")
			->execute();
		$this->createWarningLink($missing_expected, "without expected number");
		// check all sessions done have a number of attendees
		$q = SQLQuery::create()->select("InformationSession");
		PNApplication::$instance->calendar->joinCalendarEvent($q, "InformationSession","date");
		PNApplication::$instance->calendar->whereEventInThePast($q);
		$q->where("NOT `number_boys_real` > 0");
		$q->where("NOT `number_girls_real` > 0");
		$q->field("InformationSession", "id", "id");
		$q->field("InformationSession", "name", "name");
		$missing_real = $q->execute();
		$this->createWarningLink($missing_real, "done without a number of attendees");
		
		echo "</div>";
		

		echo "<div class='page_section_title2'>Applicants</div>";
		echo "<div style='padding:0px 5px'>";
		// number of applicants
		$applicants_count = SQLQuery::create()->bypassSecurity()->select("Applicant")->join("Applicant","People",array("people"=>"id"))->groupBy("People","sex")->count("nb")->field("People","sex","sex")->execute();
		$applicants_M = $applicants_F = 0;
		foreach ($applicants_count as $a)
			if ($a["sex"] == "M") $applicants_M = $a["nb"];
			else if ($a["sex"] == "F") $applicants_F = $a["nb"];
		$total_applicants = $applicants_M + $applicants_F;
		$applicants_no_IS = SQLQuery::create()->select("Applicant")->whereNull("Applicant","information_session")->count("nb_applicants")->executeSingleValue();
		echo $total_applicants." applicant".($total_applicants > 1 ? "s" : "");
		if ($total_real > 0)
			echo " (".floor($total_applicants*100/$total_real)."% of attendance)";
		if ($total_applicants > 0) {
			echo "<ul>";
			echo "<li>".$applicants_F." girl".($applicants_F > 1 ?"s":"")." (".floor($applicants_F*100/$total_applicants)."%)</li>";
			echo "<li>".$applicants_M." boy".($applicants_M > 1 ?"s":"")." (".floor($applicants_M*100/$total_applicants)."%)</li>";
			echo "</ul>";
		} else echo "<br/>";
		if ($applicants_no_IS > 0) {
			echo "<a class='need_action' href='#' onclick=\"window.top.popup_frame(null,'Applicants','/dynamic/selection/page/applicant/list',{filters:[{category:'Selection',name:'Information Session',data:{values:['NULL']}}]},95,95);return false;\">";
			echo $applicants_no_IS." applicant".(count($applicants_no_IS) > 1 ? "s":"")." not attched to an Information Session";
			echo "</a><br/>\n";
		}
		
		echo "</div>";
	}

	private $id_counter = 0;
	
	private function createWarningLink($sessions, $message) {
		if (count($sessions) == 0) return;
		$fct = "fct".$this->id_counter++;
		echo "<a class='need_action' href='#' onclick='$fct(this);return false;'>";
		echo count($sessions)." session".(count($sessions) > 1 ? "s":"")." ".$message;
		echo "</a><br/>\n";
		echo "<script type='text/javascript'>\n";
		echo "function $fct(link){\n";
		echo "\trequire('context_menu.js',function(){\n";
		echo "\t\tvar menu = new context_menu();\n";
		echo "\t\tvar div;\n";
		echo "\t\tvar win=window;\n";
		foreach ($sessions as $s) {
			echo "\t\tmenu.addIconItem("
				."null"
				.",".json_encode($s["name"])
				.",function() { popup_frame('/static/selection/is/is_16.png','Information Session','/dynamic/selection/page/is/profile?id=".$s["id"]."'+(win.ISchanged ? '&onsaved=ISchanged':''),null,95,95,function(frame,pop){if (win.ISchanged) frame.ISchanged=win.ISchanged;}); }"
			.");\n";
		}
		echo "\t\tmenu.showBelowElement(link);\n";
		echo "\t});\n";
		echo "}\n";
		echo "</script>\n";
	}
}
?>