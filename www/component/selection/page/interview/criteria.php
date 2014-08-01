<?php 
require_once("/../SelectionPage.inc");
class page_interview_criteria extends SelectionPage {
	
	public function getRequiredRights() { return array("see_interview_criteria"); }
	
	public function executeSelectionPage() {
		$can_edit = PNApplication::$instance->selection->canEditInterviewCriteria();
		
		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();
		
		$this->requireJavascript("section.js");
		if ($can_edit) {
			$this->requireJavascript("typed_field.js");
			$this->requireJavascript("field_text.js");
			$this->requireJavascript("field_decimal.js");
		}
		?>
		<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none'>
				Interview Criteria and Rules
			</div>
			<div id='page_content' style="padding:10px;overflow:hidden;flex:1 1 auto">
				<div 
					id='criteria_section'
					title='Criteria'
					collapsable='true'
				>
					<table><tbody id='criteria_table'>
					<tr>
						<th>Criterion</th>
						<th>Max Score</th>
						<th></th>
					</tr>
					</tbody></table>
				</div>
				<div 
					id='rules_section'
					title='Eligibility Rules'
				>
					<div id='rules_container'>
					</div>
				</div>
			</div>
		</div>
		<script type='text/javascript'>
		var criteria_section = sectionFromHTML('criteria_section');
		var rules_section = sectionFromHTML('rules_section');

		var criteria = <?php echo json_encode($criteria);?>;

		function addCriterion(criterion) {
			var table = document.getElementById('criteria_table');
			var tr = document.createElement("TR");
			tr.criterion_id = criterion.id;
			table.appendChild(tr);
			var td = document.createElement("TD");
			<?php if ($can_edit) {?>
			tr.field_name = new field_text(criterion.name, true, {max_length:100,can_be_null:false,min_length:1,min_size:30});
			td.appendChild(tr.field_name.getHTMLElement());
			tr.field_name.ondatachanged.add_listener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_name'); });
			tr.field_name.ondataunchanged.add_listener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_name'); });
			<?php } else {?>
			td.appendChild(document.createTextNode(criterion.name));
			<?php } ?>
			tr.appendChild(td);

			td = document.createElement("TD");
			td.style.textAlign = "right";
			<?php if ($can_edit) {?>
			tr.field_score = new field_decimal(criterion.max_score, true, {min:0,integer_digits:3,decimal_digits:2});
			td.appendChild(tr.field_score.getHTMLElement());
			tr.field_score.ondatachanged.add_listener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_score'); });
			tr.field_score.ondataunchanged.add_listener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_score'); });
			<?php } else {?>
			td.appendChild(document.createTextNode(parseFloat(criterion.max_score).toFixed(2)));
			<?php } ?>
			tr.appendChild(td);

			<?php if ($can_edit) {?>
			td = document.createElement("TD");
			var remove = document.createElement("BUTTON");
			remove.className = "flat icon";
			remove.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			remove.onclick = function() {
				if (criterion.id > 0)
					pnapplication.dataUnsaved('criterion_removed_'+criterion.id);
				else {
					pnapplication.dataSaved('new_criterion_'+criterion.id);
					pnapplication.dataSaved('criterion_'+criterion.id+"_name");
					pnapplication.dataSaved('criterion_'+criterion.id+"_score");
				}
				tr.parentNode.removeChild(tr);
			};
			td.appendChild(remove);
			tr.appendChild(td);
			<?php } ?>
		}

		for (var i = 0; i < criteria.length; ++i)
			addCriterion(criteria[i]);

		var new_criteria_id_counter = -1;
		function newCriterion() {
			criterion = { id: new_criteria_id_counter--, name: '', max_score: 0};
			pnapplication.dataUnsaved('new_criterion_'+criterion.id);
			addCriterion(criterion);
		}

		function saveCriteria() {
			var table = document.getElementById('criteria_table');
			var criteria = [];
			for (var i = 1; i < table.childNodes.length; ++i) {
				var tr = table.childNodes[i];
				if (!tr.criterion_id) continue;
				var name = tr.field_name.getCurrentData().trim();
				if (name.length == 0) { alert("Please specify a name for each criterion"); return; }
				var score = tr.field_score.getCurrentData();
				if (score == null) { alert("Please specify a score for each criterion"); return; }
				criteria.push({id:tr.criterion_id,name:name,max_score:score});
			}
			var locker = lock_screen(null, "Saving interview criteria...");
			service.json("selection","interview/save_criteria",{criteria:criteria},function(res) {
				if (!res) { unlock_screen(locker); return; }
				pnapplication.cancelDataUnsaved();
				location.reload();
			});
		}

		<?php if ($can_edit) {?>
		criteria_section.addButton(null,"Add Criterion","action",newCriterion);
		pnapplication.autoDisableSaveButton(criteria_section.addButton(theme.icons_16.save, "Save", "action", saveCriteria));
		pnapplication.autoDisableSaveButton(criteria_section.addButton(null,"Cancel Modifications","action",function() {
			pnapplication.cancelDataUnsaved();
			location.reload();
		}));
		<?php } ?>
		</script>
		<?php 
	}
	
}
?>