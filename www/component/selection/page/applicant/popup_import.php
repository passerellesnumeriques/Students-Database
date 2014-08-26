<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_applicant_popup_import extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function executeSelectionPage() {
?>
<div id='container' style='background-color:white;'>
	<div style='padding:10px'>
		<a href='#' onclick='manualImport(event);return false;'>Import manually from a file</a><br/>
		From a template:<br/>
		<button class='action' onclick='newTemplate();'>Create new template</button>
	</div>
</div>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);
window.top.require("popup_window.js");
window.top.require("excel_import.js");
function manualImport(ev) {
	window.top.require("popup_window.js", function() {
		var container = document.createElement("DIV");
		container.style.width = "100%";
		container.style.height = "100%";
		var p = new window.top.popup_window("Import Applicants", theme.icons_16._import, container);
		window.top.require("excel_import.js", function() {
			new window.top.excel_import(p, container, function(imp) {
				p.showPercent(95,95);
				imp.init();
				imp.loadImportDataURL("/dynamic/people/page/popup_create_people?types=applicant&ondone=import_done&multiple=true", <?php echo $_POST["input"];?>);
				imp.frame_import.import_done = window.frameElement.<?php echo $_GET["ondone"];?>;
				imp.uploadFile(ev);
				popup.close();
			});
		});
	});
}
function newTemplate() {
	popup.freeze();
	require("create_template.js",function() {
		new create_template('container', 'Applicant', '<?php echo PNApplication::$instance->selection->getCampaignId();?>', {'People':{'types':'/applicant/'}}, function() {
			popup.unfreeze();
		});
	});
}
</script>
<?php 
	}
} 
?>