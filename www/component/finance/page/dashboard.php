<?php 
class page_dashboard extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		require_once 'component/students_groups/page/TreeFrameSelection.inc';
		$can_manage = PNApplication::$instance->user_management->hasRight("manage_finance");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none'>
		<img src='/static/finance/finance_32.png'/>
		Finance Dashboard
		<div style='display:inline-block;font-size:12pt;margin-left:10px;font-style:italic'>
			<?php
			$batch_id = TreeFrameSelection::getBatchId();
			if ($batch_id <> null)
				echo "Batch ".toHTML(TreeFrameSelection::getBatchName());
			else if (TreeFrameSelection::isAllBatches()) echo "All batches";
			else if (TreeFrameSelection::isCurrentBatches()) echo "Current batches";
			else if (TreeFrameSelection::isAlumniBatches()) echo "Alumni batches";
			?>
		</div>
	</div>
	<div style='flex: 1 1 100%;overflow:auto;'>
		<?php 
		$batches_ids = TreeFrameSelection::getBatchesIds();
		$batches = PNApplication::$instance->curriculum->getBatches($batches_ids);
		foreach ($batches as $batch) {
			$group_id = TreeFrameSelection::getGroupId();
			$period_id = TreeFrameSelection::getPeriodId();
			if ($group_id <> null) {
				$q = PNApplication::$instance->students_groups->getStudentsQueryForGroup($group_id);
				PNApplication::$instance->people->joinPeople($q, "StudentGroup", "people", false);
			} else if ($period_id <> null) {
				$spe_id = TreeFrameSelection::getSpecializationId();
				if ($spe_id == null) $spe_id = false;
				$students_ids = PNApplication::$instance->students_groups->getStudentsForPeriod($period_id, $spe_id);
				$q = PNApplication::$instance->people->getPeoplesSQLQuery($students_ids,false,true);
			} else {
				$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($batch["id"]));
				PNApplication::$instance->people->joinPeople($q, "Student", "people", false);
			}
			$q->orderBy("People","last_name");
			$q->orderBy("People","first_name");
			$students = $q->execute();
			
			if (!TreeFrameSelection::isSingleBatch()) {
				echo "<div class='page_section_title' style='background-color:white'>";
				echo "Batch ".toHTML($batch["name"]);
				echo "</div>";
			}
			if (count($students) == 0) {
				echo "<i>No student in this batch</i>";
				continue;
			}
				
			// TODO
		}
		?>
	</div>
	<?php if ($can_manage) { ?>
	<div class='page_footer' style='flex:none;'>
		<button class='action green' onclick='newGeneralRegularPayment();'>
			Create General Regular Payment
		</button>
	</div>
	<?php } ?>
</div>
<div style='display:none;padding:5px;' id='new_general_regular_payment'>
	<form name='new_general_regular_payment'>
		<table>
			<tr>
				<td>Name:</td>
				<td><input type='text' size=20 maxlength=30 name='name'/></td>
			</tr>
			<tr>
				<td>Frequency:</td>
				<td>
					<span id='new_grp_times_container'></span>
					times every
					<span id='new_grp_every_container'></span>
					<select name='freq'>
						<option value='Daily'>Day</option>
						<option value='Weekly'>Week</option>
						<option value='Monthly' selected='selected'>Month</option>
						<option value='Yearly'>Year</option>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>
<?php 
$this->requireJavascript("typed_field.js");
$this->requireJavascript("field_integer.js");
?>
<script type='text/javascript'>
var new_grp_times = new field_integer(1,true,{can_be_null:false,min:1,max:100});
document.getElementById('new_grp_times_container').appendChild(new_grp_times.getHTMLElement());
var new_grp_every = new field_integer(1,true,{can_be_null:false,min:1,max:100});
document.getElementById('new_grp_every_container').appendChild(new_grp_every.getHTMLElement());
function newGeneralRegularPayment() {
	require("popup_window.js",function() {
		var popup = new popup_window("New Regular Payment","/static/finance/finance_16.png",document.getElementById('new_general_regular_payment'));
		popup.keep_content_on_close = true;
		var form = document.forms['new_general_regular_payment'];
		form.elements['name'].value = "";
		popup.addOkCancelButtons(function() {
			service.json("finance","new_general_regular_payment",{
				name:form.elements['name'].value,
				frequency:form.elements['freq'].value,
				times:new_grp_times.getCurrentData(),
				every:new_grp_every.getCurrentData()
			},function(res) {
				if (!res) return;
				getIFrameWindow(findFrame("pn_application_frame")).reloadMenu();
				location.reload();
			});
		});
		popup.show();
	});
}
</script>
<?php 
	}
	
}
?>