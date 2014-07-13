<?php 
class service_save_transcripts_app_config extends Service {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/html"; }
	
	public function execute(&$component, $input) {
		$name = $input["name"];
		$value = $input["value"];
		switch ($name) {
			case "location":
			case "signatory_name":
			case "signatory_title":
				break;
			default: PNApplication::error("Invalid configuration name: ".$name); return;
		}
		$db = SQLQuery::getDataBaseAccessWithoutSecurity();
		$db->execute("INSERT INTO `ApplicationConfig` (`name`,`value`) VALUES ('".$db->escapeString("transcripts_".$name)."','".$db->escapeString($value)."') ON DUPLICATE KEY UPDATE `value`='".$db->escapeString($value)."'");
	}
	
}
?>