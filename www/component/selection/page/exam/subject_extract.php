<?php 
require_once("/../SelectionPage.inc");
class page_exam_subject_extract extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_exam_subject"); }

	public function executeSelectionPage(){
		$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;
		if ($id > 0) {
			$extract = SQLQuery::create()
				->select("ExamSubjectExtract")
				->whereValue("ExamSubjectExtract","id",$id)
				->executeSingleRow();
			$extracted_parts = SQLQuery::create()
				->select("ExamSubjectExtractParts")
				->whereValue("ExamSubjectExtractParts","extract",$id)
				->join("ExamSubjectExtractParts", "ExamSubjectPart", array("part"=>"id"))
				->execute();
			$subject_id = $extracted_parts[0]["exam_subject"];
		} else {
			$extract = null;
			$extracted_parts = array();
			$subject_id = intval($_GET["subject"]);
		}
		$available_parts = SQLQuery::create()
			->select("ExamSubjectPart")
			->whereValue("ExamSubjectPart", "exam_subject", $subject_id)
			->execute();
		
		$edit = true; // TODO check if possible (no grade yet...)
		if (isset($_GET["readonly"])) $edit = false;
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		if ($id > 0) {
			$lock_extract_id = DataBaseLock::lockRow("ExamSubjectExtract", $id, $locked_by);
			if ($lock_extract_id == null)
				$edit = false;
			else {
				$lock_subject_id = DataBaseLock::lockRow("ExamSubject", $subject_id, $locked_by);
				if ($lock_subject_id == null) {
					DataBaseLock::unlock($lock_extract_id);
					$edit = false;
				} else {
					DataBaseLock::generateScript($lock_extract_id);
					DataBaseLock::generateScript($lock_subject_id);
				}
			}
		}
		
		if ($locked_by <> null) {
			echo "<div class='warning_box'><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> ".htmlentities($locked_by)." is currently working on this subject so you cannot edit it at the same time</div>";
		}
		
		?>
		<div style="display:inline-block;background-color:white">
			<div id='name_container'></div>
			<?php foreach ($available_parts as $part) {?>
			<div>
				Part <?php echo $part["index"];?> - <?php echo htmlentities($part["name"]);?>
			</div>
			<?php } ?>
		</div>
		<script type='text/javascript'>
		</script>
		<?php 
	}
}
?>