<?php 
require_once("component/storage/StoragePlugin.inc");
class SocialInvestigationPicturesPlugin implements StoragePlugin {

	public function getType() { return "social_investigation_picture"; }
	public function canRead($id) { return PNApplication::$instance->user_management->has_right("can_access_selection_data"); }
	public function canWrite($id) { return PNApplication::$instance->user_management->has_right("edit_social_investigation"); }
	
}
?>