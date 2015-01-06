<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_create_center_from_exam extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_interview_center"); }
	
	public function executeSelectionPage() {
		theme::css($this,"steps_section.css");
		theme::css($this,"grid.css");
		
		$country = PNApplication::$instance->geography->getLocalCountry();
		$divisions = PNApplication::$instance->geography->getLocalDivisions();
		$areas = array();
		foreach ($divisions as $div)
			array_push($areas, PNApplication::$instance->geography->getAllAreas($country["id"], $div["id"]));
		
		// Get Exam Centers List
		$q = SQLQuery::create()
			// select Information Sessions
			->select("ExamCenter")
			->field("ExamCenter", "id", "ec_id")
			->field("ExamCenter", "name", "ec_name")
			// not yet linked to an interview center
			->join("ExamCenter", "InterviewCenterExamCenter", array("id"=>"exam_center"))
			->whereNull("InterviewCenterExamCenter", "interview_center")
			// attach hosting partner
			->join("ExamCenter", "ExamCenterPartner", array("id"=>"exam_center"), null, array("host"=>true))
			// attach number of applicants
			->join("ExamCenter", "Applicant", array("id"=>"exam_center"))
			->countOneField("Applicant", "people", "nb_applicants")
			->groupBy("ExamCenter", "id")
			;
		PNApplication::$instance->contact->joinOrganization($q, "ExamCenterPartner", "organization");
		PNApplication::$instance->contact->joinPostalAddress($q, "ExamCenterPartner", "host_address");
		$q->field("PostalAddress", "geographic_area", "geographic_area");
		$list = $q->execute();
		?>
		<div>
		<div class='steps_section' style='height:100%'>
			<div class='header'>
				<div class='step_number'>1</div>
				<div class='step_description'>
					Select the Exam Center<br/>which will host the new Interview Center
				</div>
			</div>
			<div class='content' style='height:100%'>
				<table class='grid'>
					<tr><th></th><th>Exam Center</th><th>Hosting Partner</th><?php
					foreach ($divisions as $div) echo "<th>".toHTML($div["name"])."</th>"; 
					?><th>Applicants</th></tr>
					<?php 
					foreach ($list as $ec) {
						echo "<tr>";
						echo "<td>";
						if ($ec["geographic_area"] <> null)
							echo "<input type='radio' id='radio_ec_".$ec["ec_id"]."' onchange=\"if (this.checked) host_selected(".$ec["ec_id"].");\"/>";
						echo "</td>";
						echo "<td>".toHTML($ec["ec_name"])."</td>";
						if ($ec["geographic_area"] == null)
							echo "<td colspan=".(count($divisions)+1)." style='color:red'>No hosting partner !</td>";
						else {
							echo "<td>".toHTML($ec["organization_name"])."</td>";
							$host_areas = $this->getAreas($ec["geographic_area"], $divisions, $areas);
							foreach ($host_areas as $a) {
								echo "<td>".($a <> null ? $a["name"] : "")."</td>";
							}
						}
						echo "<td align='center'>".$ec["nb_applicants"]."</td>";
						echo "</tr>";
					}
					?>
				</table>
			</div>
		</div>
		<div class='steps_section' style='height:100%'>
			<div class='header'>
				<div class='step_number'>2</div>
				<div class='step_description'>
					Select other Exam Centers<br/>to link to this Interview Center
				</div>
			</div>
			<div class='content' style='height:100%'>
				<table class='grid'>
					<tr><th></th><th>Exam Center</th><th>Hosting Partner</th><?php
					foreach ($divisions as $div) echo "<th>".toHTML($div["name"])."</th>"; 
					?><th>Applicants</th></tr>
					<?php 
					foreach ($list as $ec) {
						echo "<tr>";
						echo "<td>";
						echo "<input type='checkbox' disabled='disabled' id='cb_ec_".$ec["ec_id"]."'/>";
						echo "</td>";
						echo "<td>".toHTML($ec["ec_name"])."</td>";
						if ($ec["geographic_area"] == null)
							echo "<td colspan=".(count($divisions)+1)." style='color:red'>No hosting partner !</td>";
						else {
							echo "<td>".toHTML($ec["organization_name"])."</td>";
							$host_areas = $this->getAreas($ec["geographic_area"], $divisions, $areas);
							foreach ($host_areas as $a) {
								echo "<td>".($a <> null ? $a["name"] : "")."</td>";
							}
						}
						echo "<td align='center'>".$ec["nb_applicants"]."</td>";
						echo "</tr>";
					}
					?>
				</table>
			</div>
		</div>
		<div class='steps_section' style='height:100%'>
			<div class='header'>
				<div class='step_number'>3</div>
			</div>
			<div class='content' style='height:100%'>
				<button id='create_button' class='action' disabled='disabled' onclick='create();'>Create Interview Center</button>
			</div>
		</div>
		</div>
		<script type='text/javascript'>
		var all_ec_ids = [<?php 
		$first = true;
		foreach ($list as $ec) {
			if ($first) $first = false; else echo ",";
			echo $ec["ec_id"];
		}
		?>];
		var host_id = null;
		function host_selected(ec_id) {
			host_id = ec_id;
			for (var i = 0; i < all_ec_ids.length; ++i) {
				var cb = document.getElementById('cb_ec_'+all_ec_ids[i]);
				if (all_ec_ids[i] == ec_id) { cb.disabled = 'disabled'; cb.checked = 'checked'; }
				else cb.disabled = '';
			}
			document.getElementById('create_button').disabled = "";
		}
		function create() {
			var linked = [];
			for (var i = 0; i < all_ec_ids.length; ++i) {
				if (all_ec_ids[i] == host_id) continue;
				var cb = document.getElementById('cb_ec_'+all_ec_ids[i]);
				if (cb.checked) linked.push(all_ec_ids[i]);
			}
			var popup = window.parent.getPopupFromFrame(window);
			popup.showPercent(95,95);
			postData("/dynamic/selection/page/interview/center_profile"<?php if (isset($_GET["onsaved"])) echo "+'?onsaved=".$_GET["onsaved"]."'";?>, {host_exam_center:host_id,others_exam_centers:linked});
		}
		</script>
		<?php 
	}
	
	/**
	 * Get a list of areas: the given area, plus its parent areas
	 * @param integer $area_id GeographicArea id
	 * @param array $divisions list of CountryDivision
	 * @param array $areas list of GeographicArea
	 * @return array list of areas
	 */
	private function getAreas($area_id, $divisions, $areas) {
		$list = array();
		for ($i = 0; $i < count($divisions); $i++) $list[$i] = null;
		for ($i = count($divisions)-1; $i >= 0; $i--) {
			$area = null;
			foreach ($areas[$i] as $a) 
				if ($a["id"] == $area_id) {
					$area = $a;
					break;
				}
			if ($area <> null) {
				$list[$i] = $area;
				$area_id = $area["parent"];
			}
		}
		return $list;
	}
}
?>