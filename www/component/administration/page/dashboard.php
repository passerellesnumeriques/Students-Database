<?php 
class page_dashboard extends Page {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function execute() {
?>
<style type="text/css">
.section_box {
	display: inline-block;
    width: 129px;
    height: 125px;
    padding: 3px 1px 3px 1px;
    margin: 3px;
    border: 1px solid rgba(0,0,0,0);
    border-radius: 5px; 
    cursor: pointer;
    vertical-align: top;
    text-decoration: none;
}
.section_box:hover {
	border: 1px solid #808080;
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
<div style="background-color: white">
	<div class="page_title">
		<img src='/static/administration/admin_32.png'/>
		Administration of the software
	</div>
	<div id="section_menu">
<?php 
require_once("component/administration/AdministrationPlugin.inc");
foreach (PNApplication::$instance->components as $name=>$c) {
	foreach ($c->getPluginImplementations() as $pi) {
		if (!($pi instanceof AdministrationPlugin)) continue;
		if ($pi instanceof AdministrationDashboardPlugin) continue;
		foreach ($pi->getAdministrationPages() as $page) {
			echo "<a class='section_box'";
			echo " href='".$page->getPage()."'";
			echo ">";
			echo "<div><img src='".$page->getIcon32()."'/></div>";
			echo "<div>".htmlentities($page->getTitle())."</div>";
			echo "<div>".htmlentities($page->getInfoText())."</div>";
			echo "</a>";
		}
	}
}
?>
	</div>
</div>
<?php 		
	}
	
}
?>