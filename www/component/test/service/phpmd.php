<?php 
class service_phpmd extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	public function getOutputFormat($input) { return "text/xml"; }
	
	public function execute(&$component, $input) {
		$out = array();
		$ret = 0;
		$php_ini = php_ini_loaded_file();
		$php_bin = dirname($php_ini)."/php.exe";
		$cmd = $php_bin." -c ".$php_ini." ".realpath("component/test/tools/phpmd.phar");
		$cmd .= " ".dirname(__FILE__)."/../../..";
		$cmd .= " xml";
		$cmd .= " cleancode,codesize,design,unusedcode";
		$cmd .= " 2>&1";
		passthru($cmd);
	}
	
}
?>