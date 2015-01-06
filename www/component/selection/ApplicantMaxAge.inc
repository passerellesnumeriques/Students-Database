<?php 
require_once("component/people/PeopleCreationStepPlugin.inc");
class ApplicantMaxAge implements PeopleCreationStep {
	
	public function getId() { return "selection_applicant_max_age"; }
	
	public function getPriority() { return 1; } // important to do it as soon as possible
	
	public function isApplicable($people) {
		$types = PNApplication::$instance->people->parseTypes($people["people_types"]);
		if (!in_array("applicant", $types)) return false;
		return PNApplication::$instance->selection->getOneConfigAttributeValue("forbid_too_old_applicants");
	}
	
	public function generatePageFor($peoples_ids, &$page) {
		require_once("component/data_model/TableDefinition.inc");
		$max_bd = PNApplication::$instance->selection->getOneConfigAttributeValue("limit_date_of_birth");
		$min = datamodel\ColumnDate::toTimestamp($max_bd);
		$peoples = PNApplication::$instance->people->getPeoples($peoples_ids, false, false, true, true);
		$ok = array();
		$too_old = array();
		$unknown = array();
		foreach ($peoples as $p) {
			$bd = $p["birthdate"];
			if ($bd == null) array_push($unknown, $p);
			else {
				$bd = datamodel\ColumnDate::toTimestamp($bd);
				if ($bd >= $min) array_push($ok, $p);
				else array_push($too_old, $p);
			}
		}
		if (count($too_old) > 0) {
			$ids = array();
			foreach ($too_old as $p) array_push($ids, $p["people_id"]);
			SQLQuery::create()->bypassSecurity()->updateByKeys("Applicant", 
				array(array(
					$ids,
					array(
						"excluded"=>1,
						"automatic_exclusion_step"=>"Application Form",
						"automatic_exclusion_reason"=>"Too old"
					)
				))
			);
		}
		echo "<div style='background-color:white;padding:10px;'>";
		if (count($unknown)+count($too_old) == 0) {
			echo "<img src='".theme::$icons_16["ok"]."' style='vertical-align:bottom'/> All aplicants are below the maximum age.";
		} else {
			if (count($too_old) > 0) {
				echo "<img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> The following applicants are above the maximum age (maximum birth date is $max_bd), and have been automatically excluded from the selection process:<ul>";
				foreach ($too_old as $p) {
					echo "<li>";
					echo toHTML($p["first_name"]." ".$p["last_name"]);
					echo " (born on ".$p["birthdate"].")";
					echo "</li>";
				}
				echo "</ul><br/>";
			}
			if (count($unknown) > 0) {
				echo "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> The following applicants don't have a birth date, so we cannot check if they reached the maximum age:<ul>";
				foreach ($unknown as $p) {
					echo "<li>";
					echo toHTML($p["first_name"]." ".$p["last_name"]);
					echo "</li>";
				}
				echo "</ul><br/>";
			}
			if (count($ok) > 0) {
				echo "<img src='".theme::$icons_16["ok"]."' style='vertical-align:bottom'/> The following applicants are below the maximum age and can continue the selection process:<ul>";
				foreach ($ok as $p) {
					echo "<li>";
					echo toHTML($p["first_name"]." ".$p["last_name"]);
					echo " (born on ".$p["birthdate"].")";
					echo "</li>";
				}
				echo "</ul><br/>";
			}
		}
		echo "</div>";
		echo "<script type='text/javascript'>";
		echo "var w=window;";
		echo "window.popup.addContinueButton(function() { w.stepDone(); });";
		echo "</script>";
	}
	
}
?>