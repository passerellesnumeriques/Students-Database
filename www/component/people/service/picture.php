<?php
class service_picture extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Retrieve the profile picture of a people"; }
	public function input_documentation() { echo "<code>people</code>: id of the people to get the picture"; }
	public function output_documentation() { echo "The picture"; }
	public function get_output_format($input) { return "image/jpeg"; }
	public function execute(&$component, $input) {
		$people_id = $_GET["people"];
		if ($people_id <> PNApplication::$instance->user_people->user_people_id) {
			if (!PNApplication::$instance->user_management->has_right("see_other_people_details")) {
				header("Location: ".theme::$icons_16["error"]);
				return;
			}
		}
		$people = SQLQuery::create()->select("People")->where("id",$people_id)->execute_single_row();
		
		if ($people["picture"] <> null) {
			$data = PNApplication::$instance->storage->get_data($people["picture"]);
			$img = imagecreatefromstring($data);
		} else {
			$img = imagecreatefromjpeg(dirname(__FILE__)."/../static/default_".($people["sex"] == "F" ? "female" : "male").".jpg");
		}
		$max_height = isset($_GET["max_height"]) ? intval($_GET["max_height"]) : -1;
		$max_width = isset($_GET["max_width"]) ? intval($_GET["max_width"]) : -1;
		if ($max_height <> -1 || $max_width <> -1) {
			$h = imagesy($img);
			$w = imagesx($img);
			$resize_ratio = 1;
			if ($max_height <> -1 && $h > $max_height) {
				$resize_ratio = $max_height/$h;
			}
			if ($max_width <> -1 && $w > $max_width) {
				$r = $max_width/$w;
				if ($r < $resize_ratio) $resize_ratio = $r;
			}
			if ($resize_ratio <> 1) {
				$nw = intval(floor($w*$resize_ratio));
				$nh = intval(floor($h*$resize_ratio));
				$img2 = imagecreatetruecolor($nw, $nh);
				imagecopyresized($img2, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
				imagedestroy($img);
				$img = $img2;
			}
		}
		
		//header("Content-Type: image/jpeg");
		header('Cache-Control: public', true);
		header('Pragma: public', true);
		$date = date("D, d M Y H:i:s",time());
		header('Date: '.$date, true);
		$expires = time()+24*60*60;
		header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
		imagejpeg($img);
	}
}
?>