<?php 
class page_home extends Page {

	public function get_required_rights() { return array(); }
	
	public function execute() {
		$exclude_components = array("development","test");
		
		$this->add_javascript("/static/widgets/collapsable_section/collapsable_section.js");

		echo "Components<br/>";
		foreach (PNApplication::$instance->components as $name=>$c) {
			if (in_array($name, $exclude_components)) continue;
			echo "<div id='section_component_".$name."' class='collapsable_section'>";
			echo "<div class='collapsable_section_header'>".$name."</div>";
			echo "<div class='collapsable_section_content'><img src='".theme::$icons_16["loading"]."'/></div>";
			echo "</div>";
			$this->onload("new collapsable_section('section_component_".$name."');");
		}

?>
General reports<br/>
phpmd<br/>
<iframe src="/dynamic/test/page/phpmd" style='width:100%;border:none' frameBorder=0>
</iframe>

<script type='text/javascript'>

</script>
<?php
	}
	
}
?>