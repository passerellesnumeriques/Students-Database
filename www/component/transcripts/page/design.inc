<?php 
function generate_transcript() {
	$design = file_get_contents("component/transcripts/page/design.html");
	
	$app_conf = SQLQuery::create()->bypassSecurity()
#DEV
		->noWarning() // TODO
#END
		->select("ApplicationConfig")->where("`name` LIKE 'transcripts_%'")->execute();
	$config = array();
	foreach ($app_conf as $ac) $config[substr($ac["name"],12)] = $ac["value"];
	
	$design = str_replace("#Location#", @$config["location"], $design);
	$design = str_replace("#Signatory Name#", @$config["signatory_name"], $design);
	$design = str_replace("#Signatory Title#", @$config["signatory_title"], $design);
	
	echo $design;
}
?>