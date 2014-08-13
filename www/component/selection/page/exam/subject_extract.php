<?php 
require_once("component/selection/page/SelectionPage.inc");
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
			$selected_parts = array();
			foreach ($extracted_parts as $e)
				array_push($selected_parts, $e["part"]);
		} else {
			$extract = null;
			$subject_id = intval($_GET["subject"]);
			$selected_parts = array();
		}
		$available_parts = SQLQuery::create()
			->select("ExamSubjectPart")
			->whereValue("ExamSubjectPart", "exam_subject", $subject_id)
			->execute();
		
		$edit = true; // TODO check if possible (no grade yet...)
		if (isset($_GET["readonly"]) && $id > 0) $edit = false;
		require_once("component/data_model/DataBaseLock.inc");
		$locked_by = null;
		if ($id > 0) {
			$campaign_id = PNApplication::$instance->selection->getCampaignId();
			$lock_extract_id = DataBaseLock::lockRow("ExamSubjectExtract_".$campaign_id, $id, $locked_by);
			if ($lock_extract_id == null)
				$edit = false;
			else {
				$lock_subject_id = DataBaseLock::lockRow("ExamSubject_".$campaign_id, $subject_id, $locked_by);
				if ($lock_subject_id == null) {
					DataBaseLock::unlock($lock_extract_id);
					$edit = false;
				} else {
					DataBaseLock::generateScript($lock_extract_id);
					DataBaseLock::generateScript($lock_subject_id);
				}
			}
		}
		
		if ($edit) {
			$this->requireJavascript("input_utils.js");
		}
		
		if ($locked_by <> null) {
			echo "<div class='warning_box'><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> ".htmlentities($locked_by)." is currently working on this subject so you cannot edit it at the same time</div>";
		}
		
		?>
		<div style="display:inline-block;background-color:white">
			<div style="text-align:center;">
			<?php if ($edit) { ?>
			<input style="font-size:18pt;margin:3px;width:300px;" id='extract_name' type='text' maxlength='100' value="<?php if ($extract <> null) echo htmlentities($extract["name"]);?>"/>
			<?php } else { ?>
			<span style="font-size:18pt;"><?php echo htmlentities($extract["name"]);?></span>
			<?php } ?>
			</div>
			<div style="margin:10px;width:300px;">
				<?php foreach ($available_parts as $part) {?>
				<div>
					<input id='part_<?php echo $part["id"];?>' type="checkbox"<?php if (in_array($part["id"], $selected_parts)) echo " checked='checked'"; if (!$edit) echo " disabled='disabled'";?> onchange="pnapplication.dataUnsaved('extract');"/>
					Part <?php echo $part["index"];?> - <?php echo htmlentities($part["name"]);?>
				</div>
				<?php } ?>
			</div>
		</div>
		<script type='text/javascript'>
		var extract_id = <?php echo $id;?>;
		var extract_name = <?php echo json_encode($id > 0 ? $extract["name"] : "");?>;
		var popup = window.parent.get_popup_window_from_frame(window);
		<?php if ($edit) { ?>
		var input_name = document.getElementById('extract_name');
		input_name.onchange = function() {
			var n = input_name.value.trim();
			if (extract_name == n) return;
			extract_name = n;
			pnapplication.dataUnsaved('extract');
		};
		inputDefaultText(input_name, "Name");
		popup.addFrameSaveButton(function() {
			var name = input_name.getValue().trim();
			if (name.length == 0) { alert("Please enter a name"); return; }
			var parts = [];
			<?php foreach ($available_parts as $part) echo "if (document.getElementById('part_".$part["id"]."').checked) parts.push(".$part["id"].");"?>
			if (parts.length == 0) { alert("Please select at least one part"); return; }
			popup.freeze("Saving...");
			service.json("selection","exam/save_subject_extract",{extract:extract_id,name:name,parts:parts},function(res) {
				popup.unfreeze();
				if (!res) return;
				if (extract_id <= 0) extract_id = res.id;
				pnapplication.dataSaved('extract');
				window.parent.location.reload();
			});
		});
		popup.addCancelButton();
		<?php } else { ?>
		popup.addCloseButton();
		<?php } ?>
		</script>
		<?php 
	}
}
?>