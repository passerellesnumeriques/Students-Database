<?php
class page_profile_people extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		$people_id = $_GET["people"];
		require_once("component/people/PeopleProfileGeneralInfoPlugin.inc");
		$plugins = array();
		$q = SQLQuery::create()
			->select("People")
			->whereValue("People", "id", $people_id)
			;
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleProfileGeneralInfoPlugin)) continue;
				$res = $pi->prepareRequestForSection($q, $people_id);
				array_push($plugins, array($pi, $res));
			}
		}
		
		$people = $q->executeSingleRow();
		
		usort($plugins, "compare_people_profile_general_info_plugins");
		
		$this->add_javascript("/static/widgets/section/section.js");
		foreach ($plugins as $a) {
			$pi = $a[0];
			$res = $a[1];
			$section_id = $this->generateID();
			echo "<div id='$section_id' icon='".$pi->getIcon()."' title=".json_encode($pi->getName())." style='display:inline-block;float:left;margin:5px' css='soft'>";
			$pi->generateSection($this, $people_id, $people, $res, $q);
			echo "</div>";
			$this->onload("section_from_html('$section_id');");
		}
	}
}
function compare_people_profile_general_info_plugins($s1, $s2) {
	if ($s1[0]->getPriority() < $s2[0]->getPriority()) return -1;
	if ($s1[0]->getPriority() > $s2[0]->getPriority()) return 1;
	return 0;
}
?>