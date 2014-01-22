<?php
class page_profile_people extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		$people_id = $_GET["people"];
		require_once("component/people/PeoplePlugin.inc");

		$q = SQLQuery::create();
		$people_alias = $q->table_id();
		$q->select(array("People"=>$people_alias));
		$q->where_value($people_alias, "id", $people_id);
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pi->preparePeopleProfilePagesRequest($q, $people_id);
			}
		}
		$people = $q->execute_single_row();
		
		$sections = array();
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pi_sections = $pi->getPeopleProfileGeneralInfoSections($people_id, $people, $q);
				if ($pi_sections <> null)
				foreach ($pi_sections as $section) {
					array_push($section, $c);
					array_push($sections, $section);
				}
			}
		}
		usort($sections, "compare_people_profile_sections");
		$this->add_javascript("/static/widgets/section/section.js");
		foreach ($sections as $section) {
			$section_id = $this->generateID();
			echo "<div id='$section_id' icon='".$section[0]."' title=".json_encode($section[1])." style='display:inline-block;float:left;margin:5px'>";
			include $section[2];
			echo "</div>";
			$this->onload("section_from_html('$section_id');");
			/*
			?>
			<div class='section_with_title' style='float:left;margin:5px'>
				<div><img src='<?php echo $section[0];?>' style='vertical-align:bottom;padding-right:3px'/><?php echo $section[1];?></div>
				<div><?php include $section[2];?></div>
			</div>
			<?php
			*/
		}
	}
}
function compare_people_profile_sections($s1, $s2) {
	if ($s1[3] < $s2[3]) return -1;
	if ($s1[3] > $s2[3]) return 1;
	return 0;
}
?>