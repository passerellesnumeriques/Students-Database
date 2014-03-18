<?php 
class service_save_picture extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$people_id = $input["id"];
		
		if (!$component->canModify($people_id)) {
			PNApplication::error("Access denied.");
			return;
		}
		
		$width = intval($input["picture"]["width"]);
		$height = intval($input["picture"]["height"]);
		$data = $input["picture"]["data"];
		
		$img = imagecreatetruecolor($width, $height);
		for ($y = 0; $y < $height; $y++)
			for ($x = 0; $x < $width; $x++) {
				$i = ($y*$width+$x)*4;
				$alpha = 127-intval(intval($data[$i+3])/2);
				$color = imagecolorallocatealpha($img, intval($data[$i]), intval($data[$i+1]), intval($data[$i+2]), $alpha);
				imagesetpixel($img, $x, $y, $color);		
			}
		
		SQLQuery::startTransaction();
		$id = PNApplication::$instance->storage->store_data("", "image/jpeg");
		if ($id == null) return;
		if (!imagejpeg($img, PNApplication::$instance->storage->get_data_path($id))) return;
		SQLQuery::create()->updateByKey("People", $people_id, array("picture"=>$id));
		if (!PNApplication::has_errors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>