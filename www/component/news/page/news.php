<?php 
class page_news extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/news/news.js");
		$this->add_javascript("/static/widgets/vertical_layout.js");
		$this->add_javascript("/static/widgets/header_bar.js");
		theme::css($this, "header_bar.css");
		?>
		<div id='page' style='width:100%;height:100%'>
			<div id='header'></div>
			<div id='news_container' layout='fill' style='overflow:auto;padding:3px;'></div>
		</div>
		<script>
		var sections = <?php echo $_GET["sections"]?>;
		var exclude = <?php echo isset($_GET["exclude"]) ? $_GET["exclude"] : "[]"?>;
		var title = <?php echo isset($_GET["title"]) ? json_encode($_GET["title"]) : "'Updates'";?>;
		new news('news_container',sections,exclude,function(n){
		},function(starts){
		});
		new header_bar('header','toolbar').setTitle("/static/news/news.png", title ? title : "");
		new vertical_layout('page');
		</script>
		<?php 
	}
	
}
?>