<?php 
class page_profile extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('profile_page');");
		
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
		
		$full_name = null;
		if ($people <> null) {
			$q = SQLQuery::create()->select("People")->field('first_name')->field('last_name')->where('id',$people);
			if ($people == PNApplication::$instance->user_people->user_people_id)
				$q->bypass_security();
			$res = $q->execute_single_row();
			if ($res) $full_name = $res["first_name"]." ".$res["last_name"];
		}

		$all_pages = array();
		foreach (PNApplication::$instance->components as $cname=>$c) {
			$cl = new ReflectionClass($c);
			try { if ($cl->getMethod("get_profile_pages") == null) continue; }
			catch (Exception $e) { continue; }
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
<div id='profile_page' icon='/static/people/profile_32.png' title='<?php echo $full_name <> null ? $full_name : "Profile"?>' page='<?php echo $page;?>'>
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