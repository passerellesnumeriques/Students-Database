<?php 
header("Content-Type: text/javascript");
require_once("component/Component.inc");
require_once("component/theme/theme.inc");
?>
theme = {
	icons_16: {
<?php 
$first = true;
foreach (theme::$icons_16 as $name=>$url) {
	if ($first) $first = false; else echo ",\r\n";
	echo $name.": ".json_encode($url);
}
?>
	},
	icons_10: {
<?php 
$first = true;
foreach (theme::$icons_10 as $name=>$url) {
	if ($first) $first = false; else echo ",\r\n";
	echo $name.": ".json_encode($url);
}
?>
	}
};