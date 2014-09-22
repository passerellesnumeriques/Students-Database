<?php 
class help extends Component {
	
	public function isShown($help_id) {
		return !$this->isHidden($help_id);
	}
	
	public function isHidden($help_id) {
		$res = SQLQuery::create()->bypassSecurity()->select("HelpSystemHidden")->whereValue("HelpSystemHidden","help_id",$help_id)->whereValue("HelpSystemHidden","user",PNApplication::$instance->user_management->user_id)->executeSingleRow();
		if ($res <> null) return true;
		return false;
	}
	
	public function startHelp($help_id, &$page, $horiz_pos, $vert_pos, $can_display_at_the_beginning = true) {
		$page->requireJavascript("help_system.js");
		$page->requireJavascript("jquery.min.js");
		$page->requireJavascript("animation.js");
		theme::css($page, "help_system.css");
		$id = $page->generateID();
		$page->onload("animation.fadeIn(document.getElementById('$id'),500);");
		$script = "";
		echo "<div id='$id' class='help_system' style='visibility:hidden;";
		if ($horiz_pos == "left") echo "left:3px;";
		else if ($horiz_pos == "right") echo "right:3px;";
		else if (substr($horiz_pos,0,9) == "relative:") {
			$rel = substr($horiz_pos,9);
			$i = strpos($rel, ":");
			$elem_id = substr($rel,0,$i);
			$pos = substr($rel,$i+1);
			$script .= "helpPositionHorizontal(".json_encode($id).",".json_encode($pos).",".json_encode($elem_id).");";
		} else echo "left:0px;";
		if ($vert_pos == "top") echo "top:3px;";
		else if ($vert_pos == "bottom") echo "bottom:3px;";
		else if (substr($vert_pos,0,9) == "relative:") {
			$rel = substr($vert_pos,9);
			$i = strpos($rel, ":");
			$elem_id = substr($rel,0,$i);
			$pos = substr($rel,$i+1);
			$script .= "helpPositionVertical(".json_encode($id).",".json_encode($pos).",".json_encode($elem_id).");";
		} else echo "top:0px;";
		echo "'>";
		echo "<script type='text/javascript'>";
		if (!$can_display_at_the_beginning)
			echo "window.help_display_ready = false;";
		else
			echo "window.help_display_ready = true;";
		echo "setTimeout(function(){".$script."},1);";
		echo "</script>";
		echo "<div class='help_system_header'>";
		echo "<img src='".theme::$icons_32["help"]."'/> Help";
		echo "</div>";
		echo "<div class='help_system_content'>";
		return $id;
	}
	
	public function endHelp($help_div_id, $help_id) {
		echo "</div>";
		echo "<div class='help_system_footer'>";
		echo "<div style='float:left'><input type='checkbox' onchange=\"service.json('help','hide',{help_id:'$help_id'},function(res){});var div = document.getElementById('$help_div_id');animation.fadeOut(div,200,function(){div.parentNode.removeChild(div);});\"/> Close and do not show this help again</div>";
		echo "<div style='float:right'><a href='#' onclick=\"var div = document.getElementById('$help_div_id');animation.fadeOut(div,200,function(){div.parentNode.removeChild(div);});return false;\"><img src='".theme::$icons_10["close"]."' style='vertical-align:middle'/> Close and show again next time</a></div>";
		echo "</div>";
		echo "</div>";
	}
	
	public function spanArrow(&$page, $text, $arrow_to_elem_selector, $force_connect = null, $onover = false) {
		$page->requireJavascript("drawing.js");
		$id = $page->generateID();
		echo "<span id='$id' class='help_system_span_arrow'>";
		echo $text;
		echo "</span>";
		echo "<script type='text/javascript'>helpSystemArrow('$id','$arrow_to_elem_selector',".($onover ? "true" : "false").",".($force_connect <> null ? json_encode($force_connect) : "null").");</script>";
	}
	
	public function availableHelp($help_id) {
		echo "<script type='text/javascript'>";
		echo "window._register_page_help_$help_id = function() {";
		echo "  if (!window.top.help_system) { var w=window; window.top.require('help_system.js',function() { w._register_page_help_$help_id(); }); return; }";
		echo "  window.top.help_system.registerAvailableHelp(window, ".json_encode($help_id).");";
		echo "  window._register_page_help_$help_id = null;";
		echo "};";
		echo "window._register_page_help_$help_id();";
		echo "</script>";
	}
	
}
?>