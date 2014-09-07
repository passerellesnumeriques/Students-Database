<?php 
header("Content-Type: text/javascript");
#DEV
require_once("component/Component.inc");
require_once("component/theme/theme.inc");
#END
#PROD
#require_once("component/PNApplication.inc");
#END

global $theme;

$css_list = array();
function browse_css($directory, $path, $css_path, &$css_list) {
	$dir = opendir($directory);
	while (($filename = readdir($dir)) <> null) {
		if ($filename == ".") continue;
		if ($filename == "..") continue;
		if (is_dir($directory."/".$filename))
			browse_css($directory."/".$filename, $path.$filename."/", $css_path.$filename."/", $css_list);
		else {
			if (substr($filename, strlen($filename)-4) == ".css")
				$css_list[$path.$filename] = $css_path.$filename;
		}
	}
	closedir($dir);
}
browse_css("component/theme/static/default/style", "", "default/style/", $css_list);
if ($theme <> "default")
	browse_css("component/theme/static/".$theme."/style", "", $theme."/style/", $css_list);
?>
window.theme = {
	name: <?php echo json_encode($theme);?>,
	css_list: [<?php
	$first = true;
	foreach ($css_list as $css_name=>$css_path) {
		if ($first) $first = false; else echo ",";
		echo "{name:".json_encode($css_name).",path:".json_encode($css_path)."}";
	} 
	?>],
	css: function (name,onloaded) {
		for (var i = 0; i < this.css_list.length; ++i)
			if (this.css_list[i].name == name) {
				addStylesheet("/static/theme/"+this.css_list[i].path,onloaded);
				break;
			}
		if (window.parent && window.parent != window && window.parent.theme)
			window.parent.theme.css(name); 
	},
	build_icon: function(main,small,where) {
		if (!where) where = "right_bottom";
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