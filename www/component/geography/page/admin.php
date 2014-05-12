<?php 
class page_admin extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/widgets/frame_header.js");
		$this->onload("new frame_header('geography_page',null,null,null,'middle');");
		$this->add_javascript("/static/widgets/select/select.js");
		?>
		<div id='geography_page' icon='/static/geography/geography_32.png' title='Geography' page='/dynamic/geography/page/set_geography_area'>
			<div style='margin-left:20px'>
				<span id='select_country'></span>
			</div>
		</div>
		<script type='text/javascript'>
		var select_country = new select('select_country');
		<?php 
// 		$countries = SQLQuery::create()->select("Country")->orderBy("Country", "name")->execute();
		$countries = PNApplication::$instance->geography->getCountriesList();
		echo "select_country.add('',\"<i>Select a Country</i>\");\n";
		foreach ($countries as $c)
			echo "select_country.add(".$c["country_id"].",\"<img src='/static/geography/flags/".strtolower($c["country_code"]).".png' style='vertical-align:bottom'/> ".htmlentities($c["country_name"])."\");\n";
		?>
		select_country.onchange = function() {
			document.getElementById('geography_page_content').src = '/dynamic/geography/page/set_geography_area?country='+select_country.getSelectedValue();
		};
		</script>
		<?php 
	}
	
}
?>