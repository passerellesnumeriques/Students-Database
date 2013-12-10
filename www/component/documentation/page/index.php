<?php 
class page_index extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		echo "<div style='width:100%;height:100%' id='doc_top_container'>";
		echo "<div style='background-color:#C0C0C0' layout='25'>";
		echo "PN Students Database - Technical Documentation";
		echo " <div class='button' onclick='location.reload();'><img src='".theme::$icons_16["refresh"]."'/></div>";
		echo "</div>";
		echo "<div layout='fill' id='doc_container'>";
			echo "<iframe src='navigation' style='border:none;width:100%;height:100%' frameBorder=0 name='navigation'></iframe>";
			echo "<iframe src='home' style='border:none;width:100%;height:100%' frameBorder=0 name='documentation'></iframe>";
		echo "</div>";
		echo "</div>";
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->onload("new vertical_layout('doc_top_container');");
		$this->add_javascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('doc_container',0.2);");
	}
	
}
?>