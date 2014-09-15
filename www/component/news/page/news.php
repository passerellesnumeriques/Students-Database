<?php 
class page_news extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->addJavascript("/static/news/news.js");
		?>
		<div id='page' style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none;'>
				<img src='/static/news/news_32.png'/>
				<?php echo isset($_GET["title"]) ? toHTML($_GET["title"]) : "Updates";?>
				<button class='flat icon' title='Post a message' onclick="postMessage();"><img src='/static/news/write_24.png'/></button>
			</div>
			<div style='overflow:auto;'>
				<div style='width:49%;vertical-align:top;display:inline-block;'>
					<div class='page_section_title2 shadow' style='background-color:white'>Updates</div>
					<div id='updates_container' style='padding:5px'></div>
				</div>
				<div style='width:49%;vertical-align:top;display:inline-block;'>
					<div class='page_section_title2 shadow' style='background-color:white'>Activities</div>
					<div id='activities_container' style='padding:5px'></div>
				</div>
			</div>
		</div>
		<script>
		var sections = <?php echo isset($_GET["sections"]) ? $_GET["sections"] : "[]";?>;
		var exclude = <?php echo isset($_GET["exclude"]) ? $_GET["exclude"] : "[]"?>;
		var updates = new news('updates_container',sections,exclude,'update',function(n){
		},function(starts){
		});
		var activities = new news('activities_container',sections,exclude,'activity',function(n){
		},function(starts){
		});
		function postMessage() {
			updates.post();
		}
		</script>
		<?php 
	}
	
}
?>