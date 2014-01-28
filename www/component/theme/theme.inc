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
	public static $icons_32;
	
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
	"color" => 			"/static/theme/".$theme."/icons_16/color.png",
	"config" => 		"/static/theme/".$theme."/icons_16/config.png",
	"copy" => 			"/static/theme/".$theme."/icons_16/copy.gif",
	"dashboard" => 		"/static/theme/".$theme."/icons_16/dashboard.png",
	"date_picker" => 	"/static/theme/".$theme."/icons_16/date_picker.png",
	"down" => 			"/static/theme/".$theme."/icons_16/down.png",
	"edit" => 			"/static/theme/".$theme."/icons_16/edit.png",
	"error" => 			"/static/theme/".$theme."/icons_16/error.png",
	"export" =>			"/static/theme/".$theme."/icons_16/export.png",
	"forbidden" => 		"/static/theme/".$theme."/icons_16/forbidden.png",
	"forward" =>		"/static/theme/".$theme."/icons_16/right.png",
	"help" =>			"/static/theme/".$theme."/icons_16/help.png",
	"hide" => 		"/static/theme/".$theme."/icons_16/invisible.png",
	"home" => 			"/static/theme/".$theme."/icons_16/home.png",
	"import" =>			"/static/theme/".$theme."/icons_16/import.png",
	"info" => 			"/static/theme/".$theme."/icons_16/info.png",
	"invisible" => 		"/static/theme/".$theme."/icons_16/invisible.png",
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
	"show" =>	 		"/static/theme/".$theme."/icons_16/visible.png",
	"up" => 			"/static/theme/".$theme."/icons_16/up.png",
	"visible" => 		"/static/theme/".$theme."/icons_16/visible.png",
	"wait" =>	 		"/static/theme/".$theme."/icons_16/wait.png",
	"warning" => 		"/static/theme/".$theme."/icons_16/warning.png",
	"window_popup" =>	"/static/theme/".$theme."/icons_16/popupize.gif",
	"window_maximize" =>"/static/theme/".$theme."/icons_16/popup_maximize.gif",
	"window_minimize" =>"/static/theme/".$theme."/icons_16/popup_minimize.gif",
	"yes" => 			"/static/theme/".$theme."/icons_16/ok.png",
	"zoom" => 			"/static/theme/".$theme."/icons_16/search.png",
	"zoom_in" => 		"/static/theme/".$theme."/icons_16/zoom_in.png",
	"zoom_out" => 		"/static/theme/".$theme."/icons_16/zoom_out.png",
	"remove_black" => 	"/static/theme/".$theme."/icons_16/trash_black.png",
);
theme::$icons_10 = array(
	"add" => 			"/static/theme/".$theme."/icons_10/add.png",
	"arrow_down_context_menu" => "/static/theme/".$theme."/icons_10/arrow_down_context_menu.gif",
	"close" => 			"/static/theme/".$theme."/icons_10/close.png",
	"edit" => 			"/static/theme/".$theme."/icons_10/edit.gif",
	"forbidden" =>		"/static/theme/".$theme."/icons_10/forbidden_10.png",
	"loading" =>		"/static/theme/".$theme."/icons_10/loading.gif",
	"no_edit" => 		"/static/theme/".$theme."/icons_10/no_edit.png",
	"online" => 		"/static/theme/".$theme."/icons_10/online.gif",
	"offline" => 		"/static/theme/".$theme."/icons_10/offline.gif",
	"popup" =>	 		"/static/theme/".$theme."/icons_10/popup.png",
	"remove" => 		"/static/theme/".$theme."/icons_10/remove.png",
	"save" => 			"/static/theme/".$theme."/icons_10/save.gif",
	"semi_online" => 	"/static/theme/".$theme."/icons_10/semi_online.gif",
	"no_connection" => 	"/static/theme/".$theme."/icons_10/no_connection.gif",
);

theme::$icons_32 = array(
	"config" => 		"/static/theme/".$theme."/icons_32/config.png",
	"dashboard" => 		"/static/theme/".$theme."/icons_32/dashboard.png",
	"home" => 			"/static/theme/".$theme."/icons_32/home.png",
);
?>