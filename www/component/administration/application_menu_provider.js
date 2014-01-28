<?php
require_once("AdministrationPlugin.inc");
foreach (PNApplication::$instance->components as $name=>$c) {
	foreach ($c->getPluginImplementations() as $pi) {
		if (!($pi instanceof AdministrationPlugin)) continue;
		foreach ($pi->getAdministrationPages() as $page) {
			echo "addMenuItem(".json_encode($page->getIcon16()).", ".json_encode($page->getTitle()).", ".json_encode($page->getPage()).");";
		}
	}
} 
?>