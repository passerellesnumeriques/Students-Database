<?php 
header("Content-Type: text/javascript");
require_once("component/Component.inc");
require_once("component/theme/theme.inc");
?>
theme = {
	name: <?php global $theme; echo json_encode($theme);?>,
	css: function (name) { add_stylesheet("/static/theme/"+this.name+"/style/"+name); },
	build_icon: function(main,small,where) {
		return "/static/application/icon.php?main="+encodeURIComponent(main)+"&small="+encodeURIComponent(small)+"&where="+where;
	},
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
	},
	icons_32: {
<?php 
$first = true;
foreach (theme::$icons_32 as $name=>$url) {
	if ($first) $first = false; else echo ",\r\n";
	echo $name.": ".json_encode($url);
}
?>
	}
};