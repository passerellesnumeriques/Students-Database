<?php 
class page_profile extends Page {
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		theme::css($this, "frame_header.css");
		
		$sub_models = null;
		if (isset($_POST) && isset($_POST["input"])) {
			$input = json_decode($_POST["input"], true);
			if (isset($input["sub_models"]))
				$sub_models = $input["sub_models"];
		}
		
		$page = @$_GET["page"];
		$people_id = $_GET["people"];
		
		require_once("component/people/PeopleProfilePagePlugin.inc");
		$q = SQLQuery::create()
			->select("People")
			->whereValue("People", "id", $people_id)
			->field("People", "first_name")
			->field("People", "last_name")
			->field("People", "types", "people_types")
			;
		$people = $q->executeSingleRow();
		$types = PNApplication::$instance->people->parseTypes($people["people_types"]);

		$pages = array();
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeopleProfilePagePlugin)) continue;
				if (!($pi->isValidFor($people_id, $types))) continue;
				array_push($pages, $pi);
			}
		}

		function pages_sort($p1, $p2) {
			return $p1->getPriority()-$p2->getPriority();
		}
		usort($pages, "pages_sort");
		if ($page == null) $page = $pages[0]->getURL($people_id);
?>
<div id='profile_page' page='<?php echo $page; if ($sub_models <> null) echo "&sub_models=".json_encode($sub_models);?>'>
<?php 
foreach ($pages as $p) {
	echo "<div";
	echo " icon=\"".htmlentities($p->getIcon())."\"";
	echo " text=\"".htmlentities($p->getName())."\"";
	echo " link=\"".htmlentities($p->getURL($people_id));
	if ($sub_models <> null)
		echo "&sub_models=".json_encode($sub_models);
	echo "\"";
	echo " tooltip=\"".htmlentities($p->getTooltip())."\"";
	echo "></div>";
}
?>
</div>
<script type='text/javascript'>
var profile_header = new frame_header('profile_page');
var title = document.createElement("DIV");
var span_first_name = document.createElement("SPAN"); title.appendChild(span_first_name);
title.appendChild(document.createTextNode(" "));
var span_last_name = document.createElement("SPAN"); title.appendChild(span_last_name);
profile_header.setTitle('/static/people/profile_32.png', title);
var people_id = <?php echo $people_id;?>;
var first_name = <?php echo json_encode($people["first_name"]);?>;
var last_name = <?php echo json_encode($people["last_name"]);?>;
var cell;
<?php 
require_once("component/data_model/page/utils.inc");
datamodel_cell_inline($this, "cell", "span_first_name", false, "People", "first_name", "people_id", null, "first_name", "function(){layout.invalidate(profile_header.header);}");
datamodel_cell_inline($this, "cell", "span_last_name", false, "People", "last_name", "people_id", null, "last_name", "function(){layout.invalidate(profile_header.header);}");
?>
</script>
<?php 
	}
}
?>