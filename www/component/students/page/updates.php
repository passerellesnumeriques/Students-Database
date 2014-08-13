<?php 
class page_updates extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$batches = null;
		if (isset($_GET["batches"])) {
			if ($_GET["batches"] == "current") {
				$title = "Current Students";
				$list = PNApplication::$instance->curriculum->getCurrentBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			} else if ($_GET["batches"] == "alumni") {
				$title = "Alumni";
				$list = PNApplication::$instance->curriculum->getAlumniBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			}
		}
		if (isset($_GET["batch"])) {
			$batches = array($_GET["batch"]);
			$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
			$id = $this->generateID();
			$title = "Batch <span id='$id'>".htmlentities($batch["name"])."</span>";
			$this->onload("window.top.datamodel.registerCellSpan(window, 'StudentBatch', 'name', ".$batch["id"].", document.getElementById('$id'));");
		}
		
		$post_tags = "null";
		if ($batches <> null) {
			$tags = "[";
			$first = true;
			if (isset($_GET["class"])) {
				// restricted to a given class
				$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
				$period = PNApplication::$instance->curriculum->getBatchPeriod($_GET["period"]);
				$id = $this->generateID();
				$title .= ", Period <span id='$id'>".htmlentities($period["name"])."</span>";
				$this->onload("window.top.datamodel.registerCellSpan(window, 'BatchPeriod', 'name', ".$period["id"].", document.getElementById('$id'));");
				$cl = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
				$id = $this->generateID();
				$title .= ", Class <span id='$id'>".htmlentities($cl["name"])."</span>";
				$this->onload("window.top.datamodel.registerCellSpan(window, 'AcademicClass', 'name', ".$cl["id"].", document.getElementById('$id'));");
				$tags .= "'class".$cl["id"]."'";
				$post_tags = "{\"batch".$batch["id"]."\":".json_encode("Batch ".$batch["name"]);
				$post_tags .= ",\"period".$period["id"]."\":".json_encode("Period ".$period["name"]);
				$post_tags .= ",\"class".$cl["id"]."\":".json_encode("Class ".$cl["name"]);
				$post_tags .= "}";
			} else if (isset($_GET["period"])) {
				// restricted to a given period
				$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
				$period = PNApplication::$instance->curriculum->getBatchPeriod($_GET["period"]);
				$id = $this->generateID();
				$title .= ", Period <span id='$id'>".htmlentities($period["name"])."</span>";
				$this->onload("window.top.datamodel.registerCellSpan(window, 'BatchPeriod', 'name', ".$period["id"].", document.getElementById('$id'));");
				$tags .= "'period".$period["id"]."'";
				$post_tags = "{\"batch".$batch["id"]."\":".json_encode("Batch ".$batch["name"]);
				$post_tags .= ",\"period".$period["id"]."\":".json_encode("Period ".$period["name"]);
				$post_tags .= "}";
			} else {
				$post_tags = "{";
				foreach ($batches as $b) {
					if ($first) $first = false; else { $post_tags .= ","; $tags .= ","; }
					$tags .= json_encode("batch".$b);
					$batch = PNApplication::$instance->curriculum->getBatch($b);
					$post_tags .= "\"batch".$batch["id"]."\":".json_encode("Batch ".$batch["name"]);
				}
				$post_tags .= "}";
			}
			$tags .= "]";
		} else {
			$tags = "null";
			$title = "All Students";
		}
		
		$this->addJavascript("/static/news/news.js");
		?>
		<div id='page' style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none;'>
				<img src='/static/news/news_32.png'/>
				Updates for <?php echo $title;?>
				<?php if (PNApplication::$instance->news->canPostInSection("students")) { ?>
				<button class='flat icon' title='Post a message' onclick="postMessage();"><img src='/static/news/write_24.png'/></button>
				<?php } ?>
			</div>
			<div id='news_container' style='flex:1 1 auto;overflow:auto;padding:10px;'></div>
		</div>
		<script>
		var sections = [{name:"students",categories:null,tags:<?php echo $tags?>}];
		var exclude = [];
		var news_obj = new news('news_container',sections,exclude,function(n){
		},function(starts){
		});
		function postMessage() {
			news_obj.post('students',null,<?php echo $post_tags;?>);
		}
		</script>
		<?php 
	}
	
}
?>