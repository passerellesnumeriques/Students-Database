<?php 
global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../../www";
$generated_dir = dirname(__FILE__)."/../../generated_doc";

require_once("Utils.inc");
require_once("FSUtils.inc");

// copy general files
copy(dirname(__FILE__)."/../index.html", $generated_dir."/index.html");
copy(dirname(__FILE__)."/../style.css", $generated_dir."/style.css");
copy(dirname(__FILE__)."/../tree_expand.png", $generated_dir."/tree_expand.png");
copy(dirname(__FILE__)."/../tree_collapse.png", $generated_dir."/tree_collapse.png");
copy(dirname(__FILE__)."/../list_circle.gif", $generated_dir."/list_circle.gif");
copy(dirname(__FILE__)."/../component.png", $generated_dir."/component.png");
copy(dirname(__FILE__)."/../dependencies.png", $generated_dir."/dependencies.png");
copy(dirname(__FILE__)."/../static.png", $generated_dir."/static.png");
copy(dirname(__FILE__)."/../javascript.png", $generated_dir."/javascript.png");
copy(dirname(__FILE__)."/../image.png", $generated_dir."/image.png");
copy(dirname(__FILE__)."/../text.png", $generated_dir."/text.png");
copy(dirname(__FILE__)."/../datamodel.png", $generated_dir."/datamodel.png");
copy(dirname(__FILE__)."/../php.gif", $generated_dir."/php.gif");
copy(dirname(__FILE__)."/../service.gif", $generated_dir."/service.gif");
copy($www_dir."/component/javascript/static/utils.js", $generated_dir."/utils.js");
copy(dirname(__FILE__)."/../general/data_model.html", $generated_dir."/general/data_model.html");

// general
$nav_general = "<?php global \$general; \$general = new TreeNode(\"General\",null,true);";
@mkdir($generated_dir."/general");
// general doc
FSUtils::copy_dir($www_dir."/doc", $generated_dir."/general");
$f = fopen($www_dir."/doc/index","r");
while (($line = fgets($f,4096)) <> null) {
	$line = trim($line);
	$i = strpos($line, ":");
	if ($i === FALSE) { echo "ERROR: invalid index file for general documentation"; continue; }
	$title = trim(substr($line,0,$i));
	$link = trim(substr($line,$i+1));
	$nav_general .= "\$general->add(new TreeNode(\"".php_str($title)."\",\"general/".php_str($link)."\",false));";
}
$nav_general .= "\$general->add(new TreeNode(\"PHP\",\"general/php/index.html\",false));";
$nav_general .= "\$general->add(new TreeNode(\"Data Model\",\"general/data_model.html\",false));";
fclose($f);
$nav_general .= "?>";
FSUtils::write_file("tmp/navigation/general", $nav_general);
?>