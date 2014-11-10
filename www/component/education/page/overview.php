<?php 
class page_overview extends Page {
	
	public function getRequiredRights() { return array(); } // TODO
	
	public function execute() {
		$period = null;
		$spe = null;
		$class = null;
		if (isset($_GET["batches"])) {
			if ($_GET["batches"] == "current") {
				$batches = PNApplication::$instance->curriculum->getCurrentBatches();
			} else {
				$batches = PNApplication::$instance->curriculum->getAlumniBatches();
			}
		} else if (isset($_GET["batch"])) {
			$batches = array(PNApplication::$instance->curriculum->getBatch($_GET["batch"]));
			if (isset($_GET["period"]))
				$period = PNApplication::$instance->curriculum->getBatchPeriod($_GET["period"]);
			if (isset($_GET["specialization"]))
				$spe = PNApplication::$instance->curriculum->getSpecialization($_GET["specialization"]);
			//if (isset($_GET["class"]))
			//	$class = PNApplication::$instance->curriculum->getAcademicClass($_GET["class"]);
		} else {
			$batches = PNApplication::$instance->curriculum->getBatches();
		}
?>
<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;position:absolute;top:0px;left:0px;'>
	<div class='page_title' style='flex:none'>
		<img src='<?php echo theme::$icons_32["info"];?>'/>
		<?php
		if (isset($_GET["batches"])) {
			if ($_GET["batches"] == "current")
				echo "Current Students";
			else
				echo "Alumni";
		} else if (isset($_GET["batch"])) {
			echo "Batch ".toHTML($batches[0]["name"]);
			if ($period <> null)
				echo ", ".toHTML($period["name"]);
			if ($spe <> null)
				echo ", Specialization ".toHTML($spe["name"]);
			//if ($class <> null)
			//	echo ", Class ".toHTML($class["name"]);
		} else {
			echo "All Students";
		}
		?>
	</div>
	<div>
		TODO
	</div>
</div>
<?php 
	}
	
}
?>