<?php
require_once("SelectionPage.inc"); 
class page_map extends SelectionPage {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function executeSelectionPage() {
		$type = @$_GET["type"];
		$markers = array();
		if ($type == "is" || $type == null) {
			$q = SQLQuery::create()
				->select("InformationSession")
				->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session",null=>array("host"=>true)))
				;
			PNApplication::$instance->contact->joinPostalAddress($q, "InformationSessionPartner", "host_address");
			$q->whereNotNull("PostalAddress","geographic_area");
			PNApplication::$instance->geography->joinGeographicArea($q, "PostalAddress", "geographic_area");
			$q->field("InformationSession","name","name");
			$q->field("InformationSession","number_boys_real","nb_boys");
			$q->field("InformationSession","number_girls_real","nb_girls");
			$q->field("PostalAddress", "lat", "lat");
			$q->field("PostalAddress", "lng", "lng");
			$q->field("GeographicArea", "north", "north");
			$q->field("GeographicArea", "south", "south");
			$q->field("GeographicArea", "west", "west");
			$q->field("GeographicArea", "east", "east");
			$q->join("InformationSession","Applicant",array("id"=>"information_session"));
			$q->groupBy("InformationSession","id");
			$q->countOneField("Applicant", "information_session", "nb_applicants");
			$sessions = $q->execute();
			foreach ($sessions as $is) {
				$marker = array();
				if ($is["lat"] <> null) {
					$marker["lat"] = floatval($is["lat"]);
					$marker["lng"] = floatval($is["lng"]);
				} else {
					$marker["lat"] = floatval($is["south"])+(floatval($is["north"])-floatval($is["south"]))/2;
					$marker["lng"] = floatval($is["west"])+(floatval($is["east"])-floatval($is["west"]))/2;
				}
				$marker["name"] = $is["name"];
				$marker["color"] = "#FFA0A0";
				$marker["text"] = (intval($is["nb_boys"])+intval($is["nb_girls"]))." attended, ".$is["nb_applicants"]." applicants";
				array_push($markers, $marker);
			}
		}
		if ($type == "exam" || $type == null) {
			$q = SQLQuery::create()
				->select("ExamCenter")
				->join("ExamCenter", "ExamCenterPartner", array("id"=>"exam_center",null=>array("host"=>true)))
				;
			PNApplication::$instance->contact->joinPostalAddress($q, "ExamCenterPartner", "host_address");
			$q->whereNotNull("PostalAddress","geographic_area");
			PNApplication::$instance->geography->joinGeographicArea($q, "PostalAddress", "geographic_area");
			$q->field("ExamCenter","name","name");
			$q->field("PostalAddress", "lat", "lat");
			$q->field("PostalAddress", "lng", "lng");
			$q->field("GeographicArea", "north", "north");
			$q->field("GeographicArea", "south", "south");
			$q->field("GeographicArea", "west", "west");
			$q->field("GeographicArea", "east", "east");
			$q->join("ExamCenter","Applicant",array("id"=>"exam_center"));
			$q->groupBy("ExamCenter","id");
			$q->expression("COUNT(*)", "nb_applicants");
			$q->expression("SUM(exam_passer)", "passers");
			$q->expression("SUM(IF(exam_attendance IS NULL,1,0))", "no_attendance");
			$q->expression("SUM(IF(exam_attendance='Yes',1,0))", "attendees");
			$q->expression("SUM(IF(exam_attendance='No',1,0))", "absents");
			$q->expression("SUM(IF(exam_attendance='Partially',1,0))", "partially");
			$q->expression("SUM(IF(exam_attendance='Cheating',1,0))", "cheaters");
			$centers = $q->execute();
			foreach ($centers as $center) {
				$marker = array();
				if ($center["lat"] <> null) {
					$marker["lat"] = floatval($center["lat"]);
					$marker["lng"] = floatval($center["lng"]);
				} else {
					$marker["lat"] = floatval($center["south"])+(floatval($center["north"])-floatval($center["south"]))/2;
					$marker["lng"] = floatval($center["west"])+(floatval($center["east"])-floatval($center["west"]))/2;
				}
				$marker["name"] = $center["name"];
				$marker["color"] = "#A0A0FF";
				$nb_applicants = intval($center["nb_applicants"]);
				$passers = intval($center["passers"]);
				$no_attendance = intval($center["no_attendance"]);
				$attendees = intval($center["attendees"]);
				$partially = intval($center["partially"]);
				$cheaters = intval($center["cheaters"]);
				$absents = intval($center["absents"]);
				$marker["text"] = $nb_applicants." applicants";
				if ($no_attendance == $nb_applicants)
					$marker["text"] .= ", no result yet";
				else {
					$marker["text"] .= ", ".$passers." passers";
					$marker["text"] .= "<br/>";
					$marker["text"] .= $attendees." attend";
					if ($partially > 0) $marker["text"] .= " + ".$partially." partially";
					if ($cheaters > 0) $marker["text"] .= " + ".$cheaters." cheaters";
					if ($absents > 0) $marker["text"] .= ", ".$absents." absents";
					if ($no_attendance > 0) $marker["text"] .= "<br/>".$no_attendance." without result yet";
				}
				array_push($markers, $marker);
			}
		}
		if ($type == "interview" || $type == null) {
			$q = SQLQuery::create()
				->select("InterviewCenter")
				->join("InterviewCenter", "InterviewCenterPartner", array("id"=>"interview_center",null=>array("host"=>true)))
				;
			PNApplication::$instance->contact->joinPostalAddress($q, "InterviewCenterPartner", "host_address");
			$q->whereNotNull("PostalAddress","geographic_area");
			PNApplication::$instance->geography->joinGeographicArea($q, "PostalAddress", "geographic_area");
			$q->field("InterviewCenter","name","name");
			$q->field("PostalAddress", "lat", "lat");
			$q->field("PostalAddress", "lng", "lng");
			$q->field("GeographicArea", "north", "north");
			$q->field("GeographicArea", "south", "south");
			$q->field("GeographicArea", "west", "west");
			$q->field("GeographicArea", "east", "east");
			$q->join("InterviewCenter","Applicant",array("id"=>"interview_center"));
			$q->groupBy("InterviewCenter","id");
			$q->expression("COUNT(*)", "nb_applicants");
			$q->expression("SUM(interview_passer)", "passers");
			$q->expression("SUM(IF(interview_attendance IS NULL,1,0))", "no_attendance");
			$q->expression("SUM(IF(interview_attendance=1,1,0))", "attendees");
			$q->expression("SUM(IF(interview_attendance=0,1,0))", "absents");
			$centers = $q->execute();
			foreach ($centers as $center) {
				$marker = array();
				if ($center["lat"] <> null) {
					$marker["lat"] = floatval($center["lat"]);
					$marker["lng"] = floatval($center["lng"]);
				} else {
					$marker["lat"] = floatval($center["south"])+(floatval($center["north"])-floatval($center["south"]))/2;
					$marker["lng"] = floatval($center["west"])+(floatval($center["east"])-floatval($center["west"]))/2;
				}
				$marker["name"] = $center["name"];
				$marker["color"] = "#A0FFA0";
				$nb_applicants = intval($center["nb_applicants"]);
				$passers = intval($center["passers"]);
				$no_attendance = intval($center["no_attendance"]);
				$attendees = intval($center["attendees"]);
				$absents = intval($center["absents"]);
				$marker["text"] = $nb_applicants." applicants";
				if ($no_attendance == $nb_applicants)
					$marker["text"] .= ", no result yet";
				else {
					$marker["text"] .= ", ".$passers." passers";
					$marker["text"] .= "<br/>";
					$marker["text"] .= $attendees." attend";
					if ($absents > 0) $marker["text"] .= ", ".$absents." absents";
					if ($no_attendance > 0) $marker["text"] .= "<br/>".$no_attendance." without result yet";
				}
				array_push($markers, $marker);
			}
		}
?>
<div style='width:100%;height:100%' id='map_container'>
</div>
<script type='text/javascript'>
function putMarkers(map, country) {
	var content;
	<?php
	foreach ($markers as $marker) {
		echo "content=document.createElement('DIV');\n";
		echo "content.style.padding = '1px';\n";
		echo "content.style.fontSize = '8pt';\n";
		echo "content.innerHTML='<b>'+".json_encode($marker["name"])."+'</b><br/>'+".json_encode($marker["text"]).";\n";
		echo "map.addShape(new window.top.PNMapMarker(".$marker["lat"].", ".$marker["lng"].",".json_encode($marker["color"]).",content));\n";
	}
	?>
	map.fitToShapes();
}

window.top.google.loadGoogleMap(document.getElementById('map_container'),function(map){
	// init to country
	window.top.geography.getCountry(window.top.default_country_id,function(country) {
		var ready = function() {
			if (typeof window.top.PNMapMarker == 'undefined') { setTimeout(ready,1); return; }
			putMarkers(map,country);
		};
		ready();
	});
});
window.top.geography.getCountry(window.top.default_country_id,function(country) {});
</script>
<?php 
	}
	
}
?>