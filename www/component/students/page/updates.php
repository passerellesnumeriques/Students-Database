<?php 
class page_updates extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
		$batches = null;
		if (isset($_GET["batches"])) {
			if ($_GET["batches"] == "current") {
				$title = "for Current Students";
				$list = PNApplication::$instance->curriculum->getCurrentBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			} else if ($_GET["batches"] == "alumni") {
				$title = "for Alumni";
				$list = PNApplication::$instance->curriculum->getAlumniBatches();
				$batches = array();
				foreach ($list as $b) array_push($batches, $b["id"]);
			}
		}
		if (isset($_GET["batch"])) {
			$batches = array($_GET["batch"]);
			$batch = PNApplication::$instance->curriculum->getBatch($_GET["batch"]);
			$id = $this->generateID();
			$title = "for Batch <span id='$id'>".htmlentities($batch["name"])."</span>";
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
			if (PNApplication::$instance->user_management->has_right("consult_students_list"))
				$title = "for All Students";
			else
				$title = "";
		}
		
		$this->addJavascript("/static/news/news.js");
		?>
		<div id='page' style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none;'>
				<img src='/static/news/news_32.png'/>
				Updates <?php echo $title;?>
				<?php if (PNApplication::$instance->news->canPostInSection("students")) { ?>
				<button class='flat icon' title='Post a message' onclick="postMessage();"><img src='/static/news/write_24.png'/></button>
				<?php } ?>
			</div>
			<div style='flex:1 1 auto;display:flex;flex-direction:row;overflow:auto;'>
				<div style='flex:1 1 auto;'>
					<div class='page_section_title2' style='background-color:white'>Updates</div>
					<div id='updates_container' style='padding:5px'></div>
				</div>
				<div style='flex:1 1 auto;'>
					<div class='page_section_title2' style='background-color:white'>Activities</div>
					<div id='activities_container' style='padding:5px'></div>
				</div>
			</div>
		</div>
		<script>
		var sections = [{name:"students",categories:null,tags:<?php echo $tags?>}];
		var exclude = [];
		var updates = new news('updates_container',sections,exclude,'update',function(n){
		},function(starts){
		});
		var activities = new news('activities_container',sections,exclude,'activity',function(n){
		},function(starts){
		});
		function postMessage() {
			var div = document.createElement("DIV");
			div.className = "info_box";
			div.innerHTML = "<img src='"+theme.icons_16.info+"' style='vertical-align:bottom'/> Messages without category are visible by students:<ul><li>If it is not related to any batch, all students will see it</li><li>Else only students of the selected batches will see it</li></ul>Other messages are not visible by students";
			updates.post('students',null,<?php echo $post_tags;?>,div);
		}
		</script>
		<?php 
	}
	
}
?>