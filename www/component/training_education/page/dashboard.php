<?php 
class page_dashboard extends Page {
	
	public function get_required_rights() { return array(); } // TODO
	
	public function execute() {
		$this->require_javascript("section.js");
		$this->require_javascript("page_header.js");
		?>
		<div id='page' style='width:100%;height:100%'>
			<div id='header' icon='<?php echo theme::$icons_16["dashboard"];?>' title='Education Dashboard' style='position:fixed;height:25px;top:0px'>
			</div>
			<div id='content' style='position:absolute;top:25px;width:100%'>
				<div id='updates_section' icon='/static/news/news.png' title='Education Updates' collapsable='true' style='margin:10px'>
					<div id='updates_container' style='padding:2px'></div>
				</div>
			</div> 
		</div>
		<script type='text/javascript'>
		require("news.js");
		new page_header('header',true);
		var updates_section = section_from_html('updates_section');
		var updates_loading = document.createElement("IMG");
		updates_loading.src = "/static/news/loading.gif";
		updates_loading.style.position = "absolute";
		updates_loading.style.visibility = "hidden";
		updates_loading.style.verticalAlign = "bottom";
		updates_section.addTool(updates_loading);
		require("news.js",function() {
			new news('updates_container',[{name:"education"}],null,function(n){
			},function(starts){
				if (starts) {
					updates_loading.style.position = "static";
					updates_loading.style.visibility = "visible";
					fireLayoutEventFor(updates_section.element);
				} else {
					updates_loading.style.position = "absolute";
					updates_loading.style.visibility = "hidden";
					fireLayoutEventFor(updates_section.element);
				}
			});
		});
		</script>
		<?php 
	}
	
}
?>