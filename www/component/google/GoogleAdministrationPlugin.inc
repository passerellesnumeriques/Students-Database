<?php
require_once("component/administration/AdministrationPlugin.inc");
/**
 * Implementation of AdministrationPlugin for Google
 */ 
class GoogleAdministrationPlugin implements AdministrationPlugin {
	
	public function getAdministrationPages() {
		return array(
			new AdministrationPage(
				"Google",
				"/dynamic/google/page/admin",
				"/static/google/google.png",
				"/static/google/google_32.png",
				"Administration of Google Services",
				function() { return PNApplication::$instance->user_management->hasRight("admin_google"); },
				function() {
					if (!PNApplication::$instance->google->isInstalled())
						return "<img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Google is not configured.<br/>";
					return null;
				}
			)
		);
	}
	
}
?>