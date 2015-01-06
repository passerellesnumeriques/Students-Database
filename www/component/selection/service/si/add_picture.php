<?php 
class service_si_add_picture extends Service {
	
	public function getRequiredRights() { return array("edit_social_investigation"); }
	
	public function documentation() { echo "Add a picture"; }
	public function inputDocumentation() { echo "applicant, id"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		$applicant_id = $input["applicant"];
		$picture_id = $input["id"];
		// check the picture
		$path = PNApplication::$instance->storage->get_data_path($picture_id);
		$img = imagecreatefromstring(file_get_contents($path));
		if ($img == false) {
			PNApplication::$instance->storage->remove_data($picture_id);
			PNApplication::error("The uploaded file is not an image");
			return;
		}
		imagedestroy($img);
		// store the picture
		SQLQuery::startTransaction();
		PNApplication::$instance->storage->convertTempFile($picture_id, "social_investigation_picture");
		SQLQuery::create()->insert("SIPicture", array("picture"=>$picture_id,"applicant"=>$applicant_id));
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
		
}
?>