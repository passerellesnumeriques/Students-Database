<?php 
class page_profile extends Page {
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		theme::css($this, "frame_header.css");
		
		$plugin = @$_GET["plugin"];
		$page = @$_GET["page"];
		$people_id = $_GET["people"];
		
		require_once("component/people/PeoplePlugin.inc");
		$q = SQLQuery::create();
		$people_alias = $q->generateTableAlias();
		$q->select(array("People"=>$people_alias));
		$q->whereValue($people_alias, "id", $people_id);
		$q->field($people_alias, "first_name");
		$q->field($people_alias, "last_name");
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pi->preparePeopleProfilePagesRequest($q, $people_id);
			}
		}
		$people = $q->executeSingleRow();
		
		if ($plugin == null) $plugin = "people";
		if ($page == null) $page = "profile_".$plugin;
		$pages = null;
		if (isset(PNApplication::$instance->components[$plugin])) {
			foreach (PNApplication::$instance->components[$plugin]->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pages = $pi->getPeopleProfilePages($people_id, $people, $q);
				break;
			}
		}
		if ($pages == null || !isset($pages[$page])) {
			PNApplication::error("Unknow profile page '".$page."' in '".$plugin."'");
			return;
		}
		$page = $pages[$page][2];
		
		$all_pages = array();
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pages = @$pi->getPeopleProfilePages($people_id, $people, $q);
				if ($pages <> null)
					foreach ($pages as $page_id=>$cp)
						array_push($all_pages, $cp);
			}
		}
		function pages_sort($p1, $p2) {
			return $p1[3]-$p2[3];
		}
		usort($all_pages, "pages_sort");
?>
<div id='profile_page' page='<?php echo $page;?>'>
<?php 
foreach ($all_pages as $cp) {
	echo "<div";
	echo " icon=\"".htmlentities($cp[0])."\"";
	echo " text=\"".htmlentities($cp[1])."\"";
	echo " link=\"".htmlentities($cp[2])."\"";
	if (isset($cp[4]) && $cp[4] <> null)
		echo " tooltip=\"".htmlentities($cp[4])."\"";
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