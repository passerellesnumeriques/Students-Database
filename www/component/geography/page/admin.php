<?php 
class page_admin extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('geography_page');");
		?>
		<div id='geography_page' icon='/static/geography/geography_32.png' title='Geography' page='/dynamic/geography/page/set_geography_area'>
			<div>
				Country to edit: 
				<?php
				$countries = SQLQuery::create()->select("Country")->execute();
				echo "<select onchange=\"document.getElementById('geography_page_content').src = '/dynamic/geography/page/set_geography_area?country='+this.value;\">";
				echo "<option value=''></option>";
				foreach ($countries as $c)
					echo "<option value = '".$c['id']."'>".$c['name']."</option>";
				echo "</select>";
				?>
			</div>
		</div>
		<?php 
	}
	
}
?>