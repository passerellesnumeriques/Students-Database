<?php 
class page_exam_create_center_from_is extends Page {
	
	public function getRequiredRights() { return array("manage_exam_center"); }
	
	public function execute() {
		theme::css($this,"steps_section.css");
		
		$country = PNApplication::$instance->geography->getLocalCountry();
		$divisions = PNApplication::$instance->geography->getLocalDivisions();
		$areas = array();
		foreach ($divisions as $div)
			array_push($areas, PNApplication::$instance->geography->getAllAreas($country["id"], $div["id"]));
		
		// Get Information Sessions List
		$list = SQLQuery::create()
			// select Information Sessions
			->select("InformationSession")
			->field("InformationSession", "name", "is_name")
			// not yet linked to an exam center
			->join("InformationSession", "ExamCenterInformationSession", array("id"=>"information_session"))
			->whereNull("ExamCenterInformationSession", "exam_center")
			// attach number of applicants
			->join("InformationSession", "Applicant", array("id"=>"information_session"))
			->groupBy("Applicant", "information_session")
			->countOneField("Applicant", "people", "nb_applicants")
			// attach hosting partner
			->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session"), null, array("host"=>true))
			->join("InformationSessionPartner", "Organization", array("organization"=>"id"))
			->field("Organization", "name", "hosting_partner_name")
			->join("InformationSessionPartner", "PostalAddress", array("host_address"=>"id"))
			->field("PostalAddress", "geographic_area", "geographic_area")
			->execute();
		?>
		<div class='steps_section'>
			<div class='header'>
				<div>1</div>
				<div>
					Select the Information Session<br/>which will host the new Exam Center
				</div>
			</div>
			<div class='content'>
				<table>
					<tr><th></th><th>Information Session</th><th>Hosting Partner</th><?php
					foreach ($divisions as $div) echo "<th>".htmlentities($div["name"])."</th>"; 
					?></tr>
					<?php 
					foreach ($list as $is) {
						echo "<tr>";
						echo "<td>";
						if ($is["geographic_area"] <> null)
							echo "<input type='radio'/>";
						echo "</td>";
						echo "<td>".htmlentities($is["is_name"])."</td>";
						if ($is["geographic_area"] == null)
							echo "<td colspan=".(count($divisions)+1)." style='color:red'>No hosting partner !</td>";
						else {
							echo "<td>".htmlentities($is["hosting_partner_name"])."</td>";
							$host_areas = $this->getAreas($is["geographic_area"], $divisions, $areas);
							foreach ($host_areas as $a) {
								echo "<td>".($a <> null ? $a["name"] : "")."</td>";
							}
						}
						echo "</tr>";
					}
					?>
				</table>
			</div>
		</div>
		<div class='steps_section'>
			<div class='header'>
				<div>2</div>
				<div>
					Select other Information Sessions<br/>to link to this Exam Center
				</div>
			</div>
			<div class='content'>
				Bla bla bla
			</div>
		</div>
		<?php 
	}
	
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