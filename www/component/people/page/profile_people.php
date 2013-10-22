<?php
class page_profile_people extends Page {
	public function get_required_rights() {
		return array(
			"see_other_people_details",
			function() { return $_GET["people"] == PNApplication::$instance->user_people->user_people_id; }
		);
	}
	public function execute() {
		$people = $_GET["people"];
		require_once("component/people/ProfileGeneralInfoPlugin.inc");
		$sections = array();
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (!($c instanceof ProfileGeneralInfoPlugin)) continue;
			foreach ($c->get_people_profile_general_info_sections($people) as $section) {
				array_push($section, $c);
				array_push($sections, $section);
			}
		}
		usort($sections, "compare_people_profile_sections");
		foreach ($sections as $section) {
			?>
			<div class='section_with_title' style='float:left;margin:5px'>
				<div><?php echo $section[1];?></div>
				<div><?php $section[3]->generate_people_profile_general_info_section($this, $people, $section[0]);?></div>
			</div>
			<?php
		}
	}
}
function compare_people_profile_sections($s1, $s2) {
	if ($s1[2] < $s2[2]) return -1;
	if ($s1[2] > $s2[2]) return 1;
	return 0;
}
?>