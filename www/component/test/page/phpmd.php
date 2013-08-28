<?php 
class page_phpmd extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$out = array();
		$ret = 0;
		$php_ini = php_ini_loaded_file();
		$php_bin = dirname($php_ini)."/php.exe";
		$cmd = $php_bin." -c ".$php_ini." ".realpath("component/test/tools/phpmd.phar");
		$cmd .= " ".dirname(__FILE__)."/../../..";
		$cmd .= " html";
		$cmd .= " cleancode,codesize,controversial,design,naming,unusedcode";
		$cmd .= " 2>&1";
		passthru($cmd);
	}
	
}
?>