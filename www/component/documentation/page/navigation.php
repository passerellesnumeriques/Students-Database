<?php 
class page_navigation extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->requireJavascript("tree.js");
?>
	<div id='navigation_tree' style='background-color:white'>
	</div>
	<script type='text/javascript'>
	var nav = new tree('navigation_tree');
	var intro = new TreeItem("Getting Started",true);
	nav.addItem(intro);
	var global = new TreeItem("Global documentation",true);
	nav.addItem(global);
	var components = new TreeItem("<a href='/static/documentation/components.html' target='documentation'>Components</a>",true);
	nav.addItem(components);
	
	var item;
	item = new TreeItem("<a href='/static/documentation/general/general_architecture/general_architecture.html' target='documentation'>General Architecture</a>",false);
	intro.addItem(item);
	item = new TreeItem("<a href='/static/documentation/general/application_structure/application_structure.html' target='documentation'>Application Structure</a>",false);
	intro.addItem(item);
	item = new TreeItem("<a href='/static/documentation/general/code/coding_conventions.html' target='documentation'>Coding Conventions</a>",false);
	intro.addItem(item);
	var php = new TreeItem("PHP",false);
	intro.addItem(php);
	item = new TreeItem("<a href='php?general=database' target='documentation'>Database access</a>",false);
	php.addItem(item);
	item = new TreeItem("<a href='php?general=app' target='documentation'>Application and components</a>",false);
	php.addItem(item);
	var js = new TreeItem("JavaScript",false);
	intro.addItem(js);
	item = new TreeItem("<a href='static?component=javascript&path=general_doc.html' target='documentation'>General Functionalities</a>",false);
	js.addItem(item);
	item = new TreeItem("<a href='static?component=widgets&path=widgets.html' target='documentation'>Widgets</a>",false);
	js.addItem(item);
	item = new TreeItem("<a href='static?component=widgets&path=layout.html' target='documentation'>Layout widgets</a>",false);
	js.addItem(item);
	
	item = new TreeItem("<a href='/static/documentation/global_datamodel.html' target='documentation'>Global Data Model</a>",false);
	global.addItem(item);
	item = new TreeItem("<a href='/static/documentation/javascript.html' target='documentation'>JavaScript</a>",false);
	global.addItem(item);
	var comp;
<?php
	foreach (PNApplication::$instance->components as $c) {
		echo "comp = new TreeItem(\"<a href='component?name=".$c->name."' target='documentation'>".$c->name."</a>\",false);components.addItem(comp);\n";
		if (file_exists("component/".$c->name."/datamodel.inc"))
			echo "item = new TreeItem(\"<a href='component?name=".$c->name."#datamodel' target='documentation'>Data Model</a>\",false);comp.addItem(item);\n";
		echo "item = new TreeItem(\"<a href='component?name=".$c->name."#php' target='documentation'>PHP</a>\",false);comp.addItem(item);\n";
		if (file_exists("component/".$c->name."/service"))
			echo "item = new TreeItem(\"<a href='component?name=".$c->name."#services' target='documentation'>Services</a>\",false);comp.addItem(item);\n";
		if (file_exists("component/".$c->name."/static/")) {
			$files = array();
			$this->browse_js("component/".$c->name."/static/", "", $files);
			if (count($files) > 0) {
				echo "js = new TreeItem(\"<a href='component?name=".$c->name."#javascript' target='documentation'>JavaScript</a>\",false);comp.addItem(js);\n";
				foreach ($files as $file)
					echo "item = new TreeItem(\"<a href='component?name=".$c->name."#js_$file' target='documentation'>$file</a>\",false);js.addItem(item);\n";
			}
		}
	} 
?>
	</script>
<?php 
	}
	
	private function browse_js($path, $rel, &$files) {
		$dir = opendir($path);
		while (($filename = readdir($dir)) <> FALSE) {
			if (is_dir("$path/$filename")) {
				if ($filename == "." || $filename == "..") continue;
				$this->browse_js("$path/$filename/", "$rel$filename/", $files);
				continue;
			}
			$i = strrpos($filename, ".");
			if ($i === FALSE) continue;
			$ext = strtolower(substr($filename, $i+1));
			if ($ext <> "js") continue;
			array_push($files, "$rel$filename");
		}
		closedir($dir);
	}
	
}
?>