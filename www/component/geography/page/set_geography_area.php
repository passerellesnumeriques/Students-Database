<?php
class page_set_geography_area extends Page {
	public function getRequiredRights(){
	// TODO
	return array();
	}
	public function execute(){
		// check a country has been selected
		if (!isset($_GET['country']) || $_GET['country'] == "") {
			echo "<div style='margin:10px'>Please select a country to edit</div>";
			return;
		}
		
		// Layout of the page
		?>
		<div id='page_split' style='width:100%;height:100%'>
			<div style='overflow:auto;'>
				<div id='manage_divisions_section' title="Country Divisions" style='margin:10px'>
					<div id ='manage_divisions' ></div>
				</div>
			</div>
			<div style='padding:10px'>
				<div id='tree_section' title="Geographic Areas" style="height:100%" fill_height="true">
				</div>
			</div>
		</div>
		<?php 
		$this->addJavascript("/static/widgets/splitter_vertical/splitter_vertical.js");
		$this->onload("new splitter_vertical('page_split',0.25);");
		$this->addJavascript("/static/widgets/section/section.js");
		?>
		<script type='text/javascript'>
		divisions_section = sectionFromHTML('manage_divisions_section');
		areas_section = sectionFromHTML('tree_section');
		country_id = <?php echo $_GET["country"];?>;
		</script>
		<?php

		// divisions
		$this->addJavascript("/static/geography/admin/edit_divisions.js");
		?>
		<script type='text/javascript'>
		divisions = new EditCountryDivisionsControl(divisions_section, country_id);
		</script>
		<?php
		
		// areas tree
		$this->addJavascript("/static/geography/admin/edit_areas.js");
		$this->requireJavascript("tree.js");
		theme::css($this, "tree.css");
		?>
		<script type='text/javascript'>
		areas = new GeographicAreasTree(areas_section, country_id);
		</script>
		<?php

		// link divisions and areas
		?>
		<script type='text/javascript'>
		divisions.division_removed.add_listener(function(division_index){ areas.reset(); });
		areas.area_added.add_listener(function(a){ divisions.areaAdded(a.division_index); });
		areas.area_removed.add_listener(function(a){ divisions.areaRemoved(a.division_index); });
		</script>
		<?php
				

		// show message while loading data
		?>
		<script type='text/javascript'>
		var loading_lock = lock_screen(null, "Loading Geographic Information...");
		window.top.require("geography.js", function() {
			window.top.geography.getCountryData(country_id, function(data) {
				unlock_screen(loading_lock);
			});
		});
		</script>
		<?php
	}
}

?>