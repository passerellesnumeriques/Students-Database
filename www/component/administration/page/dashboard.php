<?php 
class page_dashboard extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
?>
<style type="text/css">
.section_box {
	display: inline-block;
    width: 130px;
    height: 130px;
    padding: 3px 3px 3px 3px;
    margin: 3px;
    border: 1px solid rgba(0,0,0,0);
    border-radius: 5px; 
    cursor: pointer;
    vertical-align: top;
    text-decoration: none;
}
.section_box:hover {
	border: 1px solid #808080;
	box-shadow: 2px 2px 2px 0px #C0C0C0;
}
.section_box:active {
	box-shadow: none;
	position: relative;
	top: 2px;
	left: 2px;
	background-color: #F0F0F0;
}
.section_box>div {
	text-align: center;
}
.section_box>div:nth-child(2) {
	color: black;
	font-size: 12pt;
	font-weight: bold;
}
.section_box>div:nth-child(3) {
	color: #808080;
}
</style>
<div style="background-color: white;box-shadow:2px 0px 2px 0px #A0A0A0;">
	<div class="page_title">
		<img src='/static/administration/admin_32.png'/>
		Administration of the software
	</div>
	<div id="section_menu" style='padding:10px 5px;'>
<?php 
require_once("component/administration/AdministrationPlugin.inc");
$pages = array();
foreach (PNApplication::$instance->components as $c)
	foreach ($c->getPluginImplementations() as $pi)
		if ($pi instanceof AdministrationPlugin && !($pi instanceof AdministrationDashboardPlugin))
			foreach ($pi->getAdministrationPages() as $page)
				if ($page->canAccess())
					array_push($pages, $page);
usort($pages, function($p1,$p2) {
	$s1 = $p1->getTitle();
	$s2 = $p2->getTitle();
	return strcasecmp($s1, $s2);
});

foreach ($pages as $page) {
	echo "<a class='section_box'";
	echo " href='".$page->getPage()."'";
	echo ">";
	echo "<div><img src='".$page->getIcon32()."'/></div>";
	echo "<div>".toHTML($page->getTitle())."</div>";
	echo "<div>".toHTML($page->getInfoText())."</div>";
	echo "</a>";
}
?>
	</div>
</div>
<?php 		
	}
	
}
?>