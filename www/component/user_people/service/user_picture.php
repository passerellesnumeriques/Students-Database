<?php 
class service_user_picture extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Retrieve the picture from the given user"; }
	public function input_documentation() { echo "domain and username"; }
	public function output_documentation() { echo "the picture in JPEG format"; }
	
	public function get_output_format($input) {
		return "image/jpeg";
	}
	
	public function execute(&$component, $input) {
		if ($_GET["domain"] == PNApplication::$instance->local_domain) {
			require_once("component/people/service/picture.php");
			$service = new service_picture();
			$people_id = SQLQuery::create()->bypassSecurity()
				->select("Users")
				->whereValue("Users", "username", $_GET["username"])
				->join("Users", "UserPeople", array("id"=>"user"))
				->field("UserPeople", "people", "people_id")
				->executeSingleValue();
			$_GET["people"] = $people_id;
			$service->execute(PNApplication::$instance->people, array());
		} else {
			header('Cache-Control: public', true);
			header('Pragma: public', true);
			$date = date("D, d M Y H:i:s",time());
			header('Date: '.$date, true);
			$expires = time()+24*60*60;
			header('Expires: '.date("D, d M Y H:i:s",$expires).' GMT', true);
			// TODO picture with interrogation point ?
		}
	}
	
}
?>