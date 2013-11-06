<?php 
class page_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("window.profile_header = new frame_header('profile_page'); window.profile_header.setTitle(\"<span id='profile_title_first_name'></span> <span id='profile_title_last_name'></span>\");");
		
		$plugin = @$_GET["plugin"];
		$page = @$_GET["page"];
		$people = @$_GET["people"];
		if ($people == null && isset($_GET["user"]))
			$people = PNApplication::$instance->user_people->get_people_from_user($_GET["user"]);
		
		if ($plugin == null) $plugin = "people";
		if ($page == null) $page = "profile_".$plugin;
		$pages = @PNApplication::$instance->components[$plugin]->get_profile_pages($people);
		if ($pages == null || !isset($pages[$page])) {
			PNApplication::error("Unknow profile page '".$page."' in '".$plugin."'");
			return;
		}
		$page = $pages[$page][2];
		
		if ($people <> null) {
			$q = SQLQuery::create()->select("People")->field('first_name')->field('last_name')->where('id',$people);
			if ($people == PNApplication::$instance->user_people->user_people_id)
				$q->bypass_security();
			$res = $q->execute_single_row();
			if ($res) {
				require_once("component/data_model/page/utils.inc");
				datamodel_cell($this, "profile_title_first_name", false, "People", "first_name", $people, null, $res["first_name"], "function(){fireLayoutEventFor(window.profile_header.header);}");
				datamodel_cell($this, "profile_title_last_name", false, "People", "last_name", $people, null, $res["last_name"], "function(){fireLayoutEventFor(window.profile_header.header);}");
			}
		}

		require_once("component/people/ProfilePlugin.inc");
		$all_pages = array();
		foreach (PNApplication::$instance->components as $cname=>$c) {
			if (!($c instanceof ProfilePlugin)) continue;
			$pages = @$c->get_profile_pages($people);
			if ($pages <> null)
				foreach ($pages as $page_id=>$cp)
					array_push($all_pages, $cp);
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