<?php
global $theme; 
if (isset($_COOKIE["theme"])) {
	$theme = $_COOKIE["theme"];
	if ($theme == "" || !file_exists("component/theme/static/".$theme)) {
		$theme = "default";
		setcookie("theme",$theme,time()+365*24*60*60,"/");
	}
} else {
	$theme = "default";
	setcookie("theme",$theme,time()+365*24*60*60,"/");
}

class theme extends Component {

	public static $icons_16;
	public static $icons_10;
	public static $icons_32;
	
	public static function css(&$page, $name) {
		global $theme;
		if (file_exists("component/theme/static/".$theme."/style/".$name))
			$page->addStylesheet("/static/theme/".$theme."/style/".$name);
		else
			$page->addStylesheet("/static/theme/default/style/".$name);
	}
	
	public static function make_icon($main, $small, $where = "right_bottom") {
		return "/static/application/icon.php?main=".urlencode($main)."&small=".urlencode($small)."&where=".urlencode($where);
	}
	
}

include("component/theme/static/default/theme.inc");
if ($theme <> "default")
	include("component/theme/static/".$theme."/theme.inc");
?>