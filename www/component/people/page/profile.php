<?php 
class page_profile extends Page {
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("window.profile_header = new frame_header('profile_page'); window.profile_header.setTitle(\"<span id='profile_title_first_name'></span> <span id='profile_title_last_name'></span>\");");
		
		$plugin = @$_GET["plugin"];
		$page = @$_GET["page"];
		$people_id = $_GET["people"];
		
		require_once("component/people/PeoplePlugin.inc");
		$q = SQLQuery::create();
		$people_alias = $q->table_id();
		$q->select(array("People"=>$people_alias));
		$q->where_value($people_alias, "id", $people_id);
		$q->field($people_alias, "first_name");
		$q->field($people_alias, "last_name");
		foreach (PNApplication::$instance->components as $cname=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof PeoplePlugin)) continue;
				$pi->preparePeopleProfilePagesRequest($q, $people_id);
			}
		}
		$people = $q->execute_single_row();
		
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
		
		require_once("component/data_model/page/utils.inc");
		datamodel_cell($this, "profile_title_first_name", false, "People", "first_name", $people_id, null, $people["first_name"], "function(){fireLayoutEventFor(window.profile_header.header);}");
		datamodel_cell($this, "profile_title_last_name", false, "People", "last_name", $people_id, null, $people["last_name"], "function(){fireLayoutEventFor(window.profile_header.header);}");

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
<div id='profile_page' icon='/static/people/profile_32.png' title='' page='<?php echo $page;?>'>
<?php 
foreach ($all_pages as $cp) {
	echo "<span class='page_menu_item'><a href=\"".$cp[2]."\" target='profile_page_content'><img src='".$cp[0]."'/>".$cp[1]."</a></span>";
}
?>
</div>
<?php 
	}
}
?>