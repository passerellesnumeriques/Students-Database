<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_applicant_popup_import extends SelectionPage {
	
	public function getRequiredRights() { return array("edit_applicants"); }
	
	public function executeSelectionPage() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
?>
<div id='container' style='background-color:white;'>
	<div class='help_header'>
		<table><tr><td><img src='<?php echo theme::$icons_32["help"];?>'/></td><td>
		If you want to import from a single file, you can import manually.<br/>
		If you have several files with the same format, you can use templates.<br/>
		A template is used to specify the format of files once, then you can<br/>
		use it to import several files automatically.
		</td></tr></table>
	</div>
	<div style='padding:10px'>
		<button class='big' style='font-size:14pt' onclick='manualImport(event);return false;'>
			<img src='/static/data_import/import_excel_32.png'/> Import manually from a file
		</button>
		<br/>
		<div style='margin-top:10px'></div>
		<div id='template_section' title='Using a template'>
			<div>
				<?php
				require_once("component/data_import/page/template_list.inc");
				template_list($this, "selection_applicant", "Applicant", PNApplication::$instance->selection->getCampaignId(), "{'People':{'types':'/applicant/'}}", "/dynamic/people/page/popup_create_people?types=applicant", $_POST["input"], $_GET["ondone"]);
				?>
			</div>
		</div>
	</div>
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
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
	require("edit_template.js",function() {
		new edit_template('container', 'selection_applicant', 'Applicant', '<?php echo PNApplication::$instance->selection->getCampaignId();?>', {'People':{'types':'/applicant/'}}, null, function() {
			popup.unfreeze();
		});
	});
}
var sec = sectionFromHTML(document.getElementById('template_section'));
sec.addButton(null, "Create new template", "action green", newTemplate);
</script>
<?php 
	}
} 
?>