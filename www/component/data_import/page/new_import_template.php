<?php 
class page_new_import_template extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->require_javascript("upload.js");
?>
<div id='container'>
	<div class='wizard_header'>
		<img src='/static/data_import/import_excel_32.png'/>
		Upload an Excel file as example
	</div>
	<div id='upload'>
	</div>
</div>
<script type='text/javascript'>
var locker = null;
var excel_frame = null;
var template_frame = null;
function wait_ready() {
	var w = getIFrameWindow(excel_frame);
	if (!w.excel || !w.excel.tabs) {
		if (w.page_errors) {
			unlock_screen(locker);
			return;
		}
		if (w.excel_uploaded)
			set_lock_screen_content(locker, "Building Excel view...");
		setTimeout(wait_ready, 50);
		return;
	}
	unlock_screen(locker);
}
new upload('upload', false, '/dynamic/storage/service/store_temp', function(popup, received) {
	var id = received[0];
	popup.freeze();
	var split = document.createElement("DIV");
	excel_frame = document.createElement("IFRAME");
	excel_frame.style.border = "0px";
	excel_frame.src = "/dynamic/data_import/page/excel_upload?id="+id;
	template_frame = document.createElement("IFRAME");
	template_frame.style.border = "0px";
	template_frame.src = "/dynamic/data_import/page/new_import_template_choose_type?root=<?php echo urlencode($_GET["root"]); if (isset($_GET["submodel"])) echo "&submodel=".urlencode($_GET["submodel"]);?>";
	split.appendChild(excel_frame);
	split.appendChild(template_frame);
	split.style.width = "100%";
	split.style.height = "100%";
	document.body.removeChild(document.getElementById('container'));
	document.body.appendChild(split);
	require("splitter_vertical.js",function() {
		new splitter_vertical(split,0.5);
	});
	popup.close();
	locker = lock_screen(null, "Analyzing Excel File...");
	wait_ready();
});
</script>
<?php 
	}
	
}
?>