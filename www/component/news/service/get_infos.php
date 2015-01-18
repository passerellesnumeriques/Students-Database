<?php 
class service_get_infos extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Retrieve informations about accessible sections' news"; }
	public function inputDocumentation() { 
		echo "<code>sections</code>: list of requested sections {name,categories} with categories the list of requested categories (null/empty for all sections or categories)";
		echo "<br/>"; 
		echo "<code>exclude</code>: (optional) list of sections and categories which should be excluded";
	}
	public function outputDocumentation() { echo "<code>sections</code>: list of {name,display_name,icon,can_write,categories:[{name,display_name,icon,can_write}]}"; }
	
	public function execute(&$component, $input) {
		require_once("component/news/NewsPlugin.inc");
		$sections = @$input["sections"];
		$exclude = @$input["exclude"];
		echo "[";
		$first = true;
		foreach (PNApplication::$instance->components as $c) {
			foreach ($c->getPluginImplementations("NewsPlugin") as $pi) {
				foreach ($pi->getSections() as $section) {
					// check if section is requested
					if ($sections == null)
						$categories = null;
					else {
						$found = null;
						foreach ($sections as $s)
							if ($s["name"] == $section->getName()) { $found = $s; break; }
						if ($found == null) continue; // section not selected
						$categories = $s["categories"];
					}
					if ($exclude == null)
						$exclude_categories = null;
					else {
						$found = null;
						foreach ($exclude as $s)
							if ($s["name"] == $section->getName()) { $found = $s; break; }
						if ($found == null)
							$exclude_categories = null;
						else {
							if (@$s["categories"] == null) continue; // all categories are excluded
							$exclude_categories = $s["categories"];
						}
					}
					// check section access
					$section_access = $section->getAccessRight();
					if ($section_access == 0) continue; // no access

					if ($first) $first = false; else echo ",";
					echo "{";
					echo "name:".json_encode($section->getName());
					echo ",display_name:".json_encode($section->getDisplayName());
					echo ",icon:".json_encode($section->getIcon());
					echo ",can_write:".($section_access == 2 ? "true" : "false");
					echo ",categories:[";
						
					$first_cat = true;
					foreach ($section->getCategories() as $cat) {
						// check if category is requested
						if ($categories <> null) {
							if (!in_array($cat->getName(), $categories)) continue; // category not requested
						}
						if ($exclude_categories <> null) {
							if (in_array($cat->getName(), $exclude_categories)) continue; // category excluded
						}
						// check category access
						$cat_access = $cat->getAccessRight();
						if ($cat_access == 0) continue; // no access
						if ($first_cat) $first_cat = false; else echo ",";
						echo "{";
						echo "name:".json_encode($cat->getName());
						echo ",display_name:".json_encode($cat->getDisplayName());
						echo ",icon:".json_encode($cat->getIcon());
						echo ",can_write:".($cat_access == 2 ? "true" : "false");
						echo "}";
					}
					
					echo "]}"; // close categories and section
				}
			}
		}
		echo "]";
	}
	
}
?>