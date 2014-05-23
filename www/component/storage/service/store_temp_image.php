<?php 
class service_store_temp_image extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Receive a file and store it temporarly"; }
	public function inputDocumentation() { echo "A file containing an image, and optionnally a maximum size for the image"; }
	public function outputDocumentation() { echo "the id of the temporarly stored file"; }
	
	/**
	 * @param storage $component
	 */
	public function execute(&$component, $input) {
		$ids = array();
		$names = array();
		$types = array();
		$sizes = array();
		$component->receive_upload($ids, $names, $types, $sizes, 10*60);
		if (count($ids) > 0) {
			$path = $component->get_data_path($ids[0]);
			$img = imagecreatefromstring(file_get_contents($path));
			if ($img === false) {
				PNApplication::error("Invalid image format");
				$component->remove_data($ids[0]);
				return;
			}
			$w = imagesx($img);
			$h = imagesy($img);
			$nw = $w;
			$nh = $h;
			if (isset($_GET["max_size"]))
				$_GET["max_width"] = $_GET["max_height"] = $_GET["max_size"];
			if (isset($_GET["max_width"]) && $w > intval($_GET["max_width"])) {
				$nw = intval($_GET["max_width"]);
				$nh = $h*($nw/$w);
			}
			if (isset($_GET["max_height"]) && $nh > intval($_GET["max_height"])) {
				$n = intval($_GET["max_height"]);
				$r = $n/$nh;
				$nw *= $r;
				$nh = $n; 
			}
			if ($nw <> $w || $nh <> $h) {
				$img2 = imagecreatetruecolor($nw, $nh);
				imagecopyresized($img2, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
				imagedestroy($img);
				$img = $img2;
			}
			imagejpeg($img, $path);
			$component->set_mime($ids[0], "image/jpeg");
			echo "{id:".$ids[0]."}";
		}
	}
	
}
?>