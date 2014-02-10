<?php
global $theme; 
if (isset($_COOKIE["theme"]))
	$theme = $_COOKIE["theme"];
else {
	$theme = "default";
	setcookie("theme",$theme,time()+365*24*60*60,"/");
}

class theme extends Component {

	public static $icons_16;
	public static $icons_10;
	public static $icons_32;
	
	public static function css(&$page, $name) {
		global $theme;
		$page->add_stylesheet("/static/theme/".$theme."/style/".$name);
	}
	
	public static function make_icon($main, $small, $where = "right_bottom") {
		return "/static/application/icon.php?main=".urlencode($main)."&small=".urlencode($small)."&where=".urlencode($where);
	}
	
}

include("component/theme/static/".$theme."/theme.inc");
?>