<?php 
/**
 * Sections for Location and Other Partners for a center (IS, Exam, Interview)
 * @param integer|null $id the id of the InformationSession, ExamCenter or InterviewCenter. null for a new one.
 * @param SelectionPage $page the page
 * @param string $center_type one of: InformationSession, ExamCenter, InterviewCenter which will define the name of tables to use
 * @param string $geographic_area_text_json GeographicAreaText JSON structure, or null 
 */
function locationAndPartners(&$page, $id, $center_type, $geographic_area_text_json, $editable) {
	$page->requireJavascript("section.js");
	$page->addJavascript("/static/selection/common_centers/location_and_partners.js");
	?>
	<div id='section_location' 
		icon='/static/contact/address_16.png' 
		title='Location'
		collapsable='true'
		css='soft'
	>
	</div>
	<div id='section_partners'
		icon='/static/contact/directory_16.png'
		title='Other Partners'
		collapsable='true'
		css='soft'
	>
	</div>
	<script type='text/javascript'>
	var popup = window.parent.get_popup_window_from_frame(window);
	var section_location = sectionFromHTML('section_location');
	var section_partners = sectionFromHTML('section_partners');
	var center_id = <?php echo json_encode($id);?>;
	var partners = <?php
	if ($id <> null) {
		require_once("component/selection/common_centers/SelectionPartnersJSON.inc");
		echo SelectionPartnersJSON::PartnersFromCenterID($center_type, $id);
	} else
		echo "[]"; 
	?>;
	window.center_location = new location_and_partners(popup, section_location, section_partners, <?php echo json_encode($center_type);?>, center_id, <?php echo $geographic_area_text_json;?>, partners, <?php echo $editable ? "true" : "false";?>);
	</script>
	<?php 
}
?>