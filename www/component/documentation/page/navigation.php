<?php 
class page_navigation extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("tree.js");
?>
	<div id='navigation_tree' style='width:100%;height:100%;overflow:auto'>
	</div>
	<script type='text/javascript'>
	var nav = new tree('navigation_tree');
	var general = new TreeItem("Getting Started",true);
	nav.addItem(general);
	var components = new TreeItem("Components",true);
	nav.addItem(components);
	var item;
	item = new TreeItem("<a href='/static/documentation/general/general_architecture/general_architecture.html' target='documentation'>General Architecture</a>",false);
	general.addItem(item);
	item = new TreeItem("<a href='/static/documentation/general/application_structure/application_structure.html' target='documentation'>Application Structure</a>",false);
	general.addItem(item);
	item = new TreeItem("<a href='global_datamodel' target='documentation'>Global Data Model</a>",false);
	general.addItem(item);
	var php = new TreeItem("PHP",false);
	general.addItem(php);
	item = new TreeItem("<a href='php?general=database' target='documentation'>Database access</a>",false);
	php.addItem(item);
	item = new TreeItem("<a href='php?general=app' target='documentation'>Application and components</a>",false);
	php.addItem(item);
<?php
	foreach (PNApplication::$instance->components as $c) {
		echo "item = new TreeItem(\"<a href='component?name=".$c->name."' target='documentation'>".$c->name."</a>\",false);components.addItem(item);\n";
	} 
?>
	</script>
<?php 
	}
	
}
?>