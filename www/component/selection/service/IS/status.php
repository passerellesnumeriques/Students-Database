<?php 
class service_IS_status extends Service {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		// number of sessions done
		$q = SQLQuery::create()->select("InformationSession");
		PNApplication::$instance->calendar->joinEvent($q, "InformationSession","date");
		PNApplication::$instance->calendar->whereEventInThePast($q);
		$q->count("nb_sessions");
		$q->sum("InformationSession","number_boys_expected", "boys_expected");
		$q->sum("InformationSession","number_girls_expected", "girls_expected");
		$q->sum("InformationSession","number_boys_real", "boys_real");
		$q->sum("InformationSession","number_girls_real", "girls_real");
		$sessions_done = $q->executeSingleRow();
		// number of sessions in the future
		$q = SQLQuery::create()->select("InformationSession");
		PNApplication::$instance->calendar->joinEvent($q, "InformationSession","date");
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
			echo "<center><i>No Information Session Yet</i></center>";
			return;
		}
		echo $nb_sessions." session".($nb_sessions>1?"s":"");
		if ($nb_sessions == $sessions_done["nb_sessions"]) {
			// all sessions are done
			echo ": <span color='green'>All done</span><br/>";
		} else {
			echo "<ul style='padding-left:20px'>";
				echo "<li>";
					echo ($sessions_done["nb_sessions"])." done";
				echo "</li>";
				echo "<li>";
					echo ($sessions_future["nb_sessions"])." planned";
				echo "</li>";
				if ($sessions_no_date["nb_sessions"] > 0) {
					echo "<li>";
						echo "<span style='color:DarkOrange'>";
						echo ($sessions_no_date["nb_sessions"])." without date yet";
						echo "</span>";
					echo "</li>";
				}
			echo "</ul>";
		}
		
		// check if we have sessions without host
		$missing_host = SQLQuery::create()
			->select("InformationSession")
			->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session"), null, array("host"=>1))
			->whereNull("InformationSessionPartner", "host")
			->field("InformationSession", "id", "id")
			->field("InformationSession", "name", "name")
			->execute();
		$this->createWarningLink($missing_host, "without hosting partner");

		echo "<br/>";
		
		// attendance
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
			echo $total_real." attendees";
			if ($separate)
				echo " (".$total_boys_real." boys and ".$total_girls_real." girls)";
		} else {
			echo "<ul style='padding-left:20px'>";
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
		PNApplication::$instance->calendar->joinEvent($q, "InformationSession","date");
		PNApplication::$instance->calendar->whereEventInThePast($q);
		$q->where("NOT `number_boys_real` > 0");
		$q->where("NOT `number_girls_real` > 0");
		$q->field("InformationSession", "id", "id");
		$q->field("InformationSession", "name", "name");
		$missing_real = $q->execute();
		$this->createWarningLink($missing_real, "done without a number of attendees");
	}

	private $id_counter = 0;
	
	private function createWarningLink($sessions, $message) {
		if (count($sessions) == 0) return;
		$fct = "fct".$this->id_counter++;
		echo "<a style='color:DarkOrange' href='#' onclick='$fct(this);return false;'>";
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
				.",function() { popup_frame('/static/selection/IS/IS_16.png','Information Session','/dynamic/selection/page/IS/profile?id=".$s["id"]."'+(win.ISchanged ? '&onsaved=ISchanged':''),null,95,95,function(frame,pop){if (win.ISchanged) frame.ISchanged=win.ISchanged;}); }"
			.");\n";
		}
		echo "\t\tmenu.showBelowElement(link);\n";
		echo "\t});\n";
		echo "}\n";
		echo "</script>\n";
	}
}
?>