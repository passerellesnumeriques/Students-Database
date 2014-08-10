<?php 
class page_news extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->addJavascript("/static/news/news.js");
		?>
		<div id='page' style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none;'>
				<img src='/static/news/news_32.png'/>
				<?php echo isset($_GET["title"]) ? htmlentities($_GET["title"]) : "Updates";?>
				<button class='flat icon' title='Post a message' onclick="postMessage();"><img src='/static/news/write_24.png'/></button>
			</div>
			<div id='news_container' style='flex:1 1 auto;overflow:auto;padding:10px;'></div>
		</div>
		<script>
		var sections = <?php echo isset($_GET["sections"]) ? $_GET["sections"] : "[]";?>;
		var exclude = <?php echo isset($_GET["exclude"]) ? $_GET["exclude"] : "[]"?>;
		var news_obj = new news('news_container',sections,exclude,function(n){
		},function(starts){
		});
		function postMessage() {
			news_obj.post();
		}
		</script>
		<?php 
	}
	
}
?>