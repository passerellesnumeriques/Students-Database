<?php
global $theme; 
if (isset($_COOKIE["theme"]))
	$theme = $_COOKIE["theme"];
else {
	$theme = "default";
	setcookie("theme",$theme,time()+365*24*60*60,"/");
}

class theme extends Component {

	public static $style;
	public static $icons_16;
	public static $icons_10;
	
	public static function make_icon($main, $small, $where = "right_bottom") {
		return "/static/application/icon.php?main=".urlencode($main)."&small=".urlencode($small)."&where=".urlencode($where);
	}
	
}

theme::$style = "/static/theme/".$theme."/style.css";
theme::$icons_16 = array(
	"add" => 			"/static/theme/".$theme."/icons_16/add.png",
	"back" => 			"/static/theme/".$theme."/icons_16/left.png",
	"cancel" => 		"/static/theme/".$theme."/icons_16/close.png",
	"close" => 			"/static/theme/".$theme."/icons_16/close.png",
	"copy" => 			"/static/theme/".$theme."/icons_16/copy.gif",
	"date_picker" => 	"/static/theme/".$theme."/icons_16/date_picker.png",
	"down" => 			"/static/theme/".$theme."/icons_16/down.png",
	"edit" => 			"/static/theme/".$theme."/icons_16/edit.png",
	"error" => 			"/static/theme/".$theme."/icons_16/error.png",
	"export" =>			"/static/theme/".$theme."/icons_16/export.png",
	"forbidden" => 		"/static/theme/".$theme."/icons_16/forbidden.png",
	"forward" =>		"/static/theme/".$theme."/icons_16/right.png",
	"import" =>			"/static/theme/".$theme."/icons_16/import.png",
	"info" => 			"/static/theme/".$theme."/icons_16/info.png",
	"left" => 			"/static/theme/".$theme."/icons_16/left.png",
	"loading" => 		"/static/theme/".$theme."/icons_16/loading.gif",
	"lock" => 			"/static/theme/".$theme."/icons_16/lock.png",
	"more_menu" => 		"/static/theme/".$theme."/icons_16/more_menu.png",
	"no" => 			"/static/theme/".$theme."/icons_16/close.png",
	"no_edit" => 		"/static/theme/".$theme."/icons_16/no_edit.png",
	"ok" => 			"/static/theme/".$theme."/icons_16/ok.png",
	"question" => 		"/static/theme/".$theme."/icons_16/question.png",
	"remove" => 		"/static/theme/".$theme."/icons_16/trash.png",
	"refresh" => 		"/static/theme/".$theme."/icons_16/refresh.png",
	"right" => 			"/static/theme/".$theme."/icons_16/right.png",
	"save" => 			"/static/theme/".$theme."/icons_16/save.png",
	"saving" =>			"/static/theme/".$theme."/icons_16/saving.gif",
	"search" => 		"/static/theme/".$theme."/icons_16/search.png",
	"up" => 			"/static/theme/".$theme."/icons_16/up.png",
	"wait" =>	 		"/static/theme/".$theme."/icons_16/wait.png",
	"warning" => 		"/static/theme/".$theme."/icons_16/warning.png",
	"yes" => 			"/static/theme/".$theme."/icons_16/ok.png",
	"zoom" => 			"/static/theme/".$theme."/icons_16/search.png",
	"remove_black" => 		"/static/theme/".$theme."/icons_16/trash_black.png",
);
theme::$icons_10 = array(
	"add" => 			"/static/theme/".$theme."/icons_10/add.png",
	"close" => 			"/static/theme/".$theme."/icons_10/close.png",
	"loading" =>		"/static/theme/".$theme."/icons_10/loading.gif",
	"no_edit" => 		"/static/theme/".$theme."/icons_10/no_edit.png",
	"online" => 		"/static/theme/".$theme."/icons_10/online.gif",
	"offline" => 		"/static/theme/".$theme."/icons_10/offline.gif",
	"popup" =>	 		"/static/theme/".$theme."/icons_10/popup.png",
	"remove" => 		"/static/theme/".$theme."/icons_10/close.png",
	"save" => 			"/static/theme/".$theme."/icons_10/save.gif",
	"semi_online" => 	"/static/theme/".$theme."/icons_10/semi_online.gif",
	"no_connection" => 	"/static/theme/".$theme."/icons_10/no_connection.gif",
);
?>