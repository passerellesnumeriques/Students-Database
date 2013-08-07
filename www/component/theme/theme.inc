<?php 
$theme = "default"; // TODO
class theme extends Component {

	public static $style;
	public static $icons_16;
	public static $icons_10;
	
}

theme::$style = "/static/theme/".$theme."/style.css";
theme::$icons_16 = array(
	"cancel" => 		"/static/theme/".$theme."/icons_16/close.png",
	"close" => 			"/static/theme/".$theme."/icons_16/close.png",
	"edit" => 			"/static/theme/".$theme."/icons_16/edit.png",
	"error" => 			"/static/theme/".$theme."/icons_16/error.png",
	"forbidden" => 		"/static/theme/".$theme."/icons_16/forbidden.png",
	"info" => 			"/static/theme/".$theme."/icons_16/info.png",
	"loading" => 		"/static/theme/".$theme."/icons_16/loading.gif",
	"no" => 			"/static/theme/".$theme."/icons_16/close.png",
	"no_edit" => 		"/static/theme/".$theme."/icons_16/no_edit.png",
	"ok" => 			"/static/theme/".$theme."/icons_16/ok.png",
	"question" => 		"/static/theme/".$theme."/icons_16/question.png",
	"save" => 			"/static/theme/".$theme."/icons_16/save.png",
	"saving" =>			"/static/theme/".$theme."/icons_16/saving.gif",
	"search" => 		"/static/theme/".$theme."/icons_16/search.png",
	"warning" => 		"/static/theme/".$theme."/icons_16/warning.png",
	"yes" => 			"/static/theme/".$theme."/icons_16/ok.png",
);
theme::$icons_10 = array(
	"close" => 			"/static/theme/".$theme."/icons_10/close.png",
);
?>