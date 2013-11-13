<?php 
function import_data(&$page, $icon, $title, $data_list) {
	
	$page_id = $page->generate_id();
	echo "<div id='$page_id' style='width:100%;height:100%'>";
		
	$header_id = $page->generate_id();
	echo "<div id='$header_id' icon='$icon' title='$title' layout='fixed'></div>";
	$page->add_javascript("/static/widgets/page_header.js");
	
	$container_id = $page->generate_id();
	echo "<div layout='fill' id='$container_id' style='overflow:hidden'></div>";
	$page->add_javascript("/static/data_import/custom_import.js");
	
	echo "</div>";
	
	$page->add_javascript("/static/data_model/DataDisplay.js");
	?>
	<script type="text/javascript">
	require("vertical_layout.js",function() {
		new vertical_layout('<?php echo $page_id;?>');
	});
	var header = new page_header('<?php echo $header_id;?>');
	new custom_import('<?php echo $container_id;?>',function(imp) {
		var upload_excel = document.createElement("DIV");
		upload_excel.className = "button";
		upload_excel.innerHTML = "<img src='/static/data_import/import_excel_16.png'/> Upload Excel File";
		upload_excel.onclick = function() {
			imp.upload_excel(function(){
				var win = getIFrameWindow(imp.excel_frame);
				if (win.excel) win.excel = null;
			}, function(){
				var status = 1;
				var lock = lock_screen(null,"<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Uploading Excel file...");
				var check = function() {
					var win = getIFrameWindow(imp.excel_frame);
					if (!win.excel || !win.excel.tabs) {
						if (win.page_errors) {
							unlock_screen(lock);
							return;
						}
						if (win.excel_uploaded) {
							if (status == 1) {
								status = 2;
								set_lock_screen_content(lock, "<img src='"+theme.icons_16.loading+"' style='vertical-align:bottom'/> Preparing Excel...");
							}
						}
						setTimeout(check,25);
						return;
					}
					unlock_screen(lock);
				};
				check();
			});
		};
		header.addMenuItem(upload_excel);
		fireLayoutEventFor(document.getElementById('<?php echo $page_id;?>'));
		<?php 
		foreach ($data_list as $data)
			echo "imp.add_data(".$data->javascriptDataDisplay(null).");";
		?>
	});
	</script>
	<?php 
		
}
?>