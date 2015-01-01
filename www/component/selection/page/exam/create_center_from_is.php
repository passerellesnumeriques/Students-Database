<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_exam_create_center_from_is extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function executeSelectionPage() {
		theme::css($this,"steps_section.css");
		theme::css($this,"grid.css");
		
		$country = PNApplication::$instance->geography->getLocalCountry();
		$divisions = PNApplication::$instance->geography->getLocalDivisions();
		$areas = array();
		foreach ($divisions as $div)
			array_push($areas, PNApplication::$instance->geography->getAllAreas($country["id"], $div["id"]));
		
		// Get Information Sessions List
		$q = SQLQuery::create()
			// select Information Sessions
			->select("InformationSession")
			->field("InformationSession", "id", "is_id")
			->field("InformationSession", "name", "is_name")
			// not yet linked to an exam center
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->whereNull("ExamCenterInformationSession", "exam_center")
			// attach hosting partner
			->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session"), null, array("host"=>true))
			// attach number of applicants
			->join("InformationSession", "Applicant", array("id"=>"information_session"))
			->countOneField("Applicant", "people", "nb_applicants")
			->groupBy("InformationSession", "id")
			;
		PNApplication::$instance->contact->joinOrganization($q, "InformationSessionPartner", "organization");
		PNApplication::$instance->contact->joinPostalAddress($q, "InformationSessionPartner", "host_address");
		$q->field("PostalAddress", "geographic_area", "geographic_area");
		$list = $q->execute();
		?>
		<div>
		<div class='steps_section' style='height:100%'>
			<div class='header'>
				<div class='step_number'>1</div>
				<div class='step_description'>
					Select the Information Session<br/>which will host the new Exam Center
				</div>
			</div>
			<div class='content' style='height:100%'>
				<table class='grid'>
					<tr><th></th><th>Information Session</th><th>Hosting Partner</th><?php
					foreach ($divisions as $div) echo "<th>".toHTML($div["name"])."</th>"; 
					?><th>Applicants</th></tr>
					<?php 
					foreach ($list as $is) {
						echo "<tr>";
						echo "<td>";
						if ($is["geographic_area"] <> null)
							echo "<input type='radio' id='radio_is_".$is["is_id"]."' onchange=\"if (this.checked) host_selected(".$is["is_id"].");\"/>";
						echo "</td>";
						echo "<td>".toHTML($is["is_name"])."</td>";
						if ($is["geographic_area"] == null)
							echo "<td colspan=".(count($divisions)+1)." style='color:red'>No hosting partner !</td>";
						else {
							echo "<td>".toHTML($is["organization_name"])."</td>";
							$host_areas = $this->getAreas($is["geographic_area"], $divisions, $areas);
							foreach ($host_areas as $a) {
								echo "<td>".($a <> null ? $a["name"] : "")."</td>";
							}
						}
						echo "<td align='center'>".$is["nb_applicants"]."</td>";
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
					Select other Information Sessions<br/>to link to this Exam Center
				</div>
			</div>
			<div class='content' style='height:100%'>
				<table class='grid'>
					<tr><th></th><th>Information Session</th><th>Hosting Partner</th><?php
					foreach ($divisions as $div) echo "<th>".toHTML($div["name"])."</th>"; 
					?><th>Applicants</th></tr>
					<?php 
					foreach ($list as $is) {
						echo "<tr>";
						echo "<td>";
						echo "<input type='checkbox' disabled='disabled' id='cb_is_".$is["is_id"]."'/>";
						echo "</td>";
						echo "<td>".toHTML($is["is_name"])."</td>";
						if ($is["geographic_area"] == null)
							echo "<td colspan=".(count($divisions)+1)." style='color:red'>No hosting partner !</td>";
						else {
							echo "<td>".toHTML($is["organization_name"])."</td>";
							$host_areas = $this->getAreas($is["geographic_area"], $divisions, $areas);
							foreach ($host_areas as $a) {
								echo "<td>".($a <> null ? $a["name"] : "")."</td>";
							}
						}
						echo "<td align='center'>".$is["nb_applicants"]."</td>";
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
				<button id='create_button' class='action' disabled='disabled' onclick='create();'>Create Exam Center</button>
			</div>
		</div>
		</div>
		<script type='text/javascript'>
		var all_is_ids = [<?php 
		$first = true;
		foreach ($list as $is) {
			if ($first) $first = false; else echo ",";
			echo $is["is_id"];
		}
		?>];
		var host_id = null;
		function host_selected(is_id) {
			host_id = is_id;
			for (var i = 0; i < all_is_ids.length; ++i) {
				var cb = document.getElementById('cb_is_'+all_is_ids[i]);
				if (all_is_ids[i] == is_id) { cb.disabled = 'disabled'; cb.checked = 'checked'; }
				else cb.disabled = '';
			}
			document.getElementById('create_button').disabled = "";
		}
		function create() {
			var linked = [];
			for (var i = 0; i < all_is_ids.length; ++i) {
				if (all_is_ids[i] == host_id) continue;
				var cb = document.getElementById('cb_is_'+all_is_ids[i]);
				if (cb.checked) linked.push(all_is_ids[i]);
			}
			var popup = window.parent.getPopupFromFrame(window);
			popup.showPercent(95,95);
			postData("/dynamic/selection/page/exam/center_profile"<?php if (isset($_GET["onsaved"])) echo "+'?onsaved=".$_GET["onsaved"]."'";?>, {host_is:host_id,others_is:linked});
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