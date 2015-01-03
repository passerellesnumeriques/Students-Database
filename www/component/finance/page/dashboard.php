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
	<div style='flex: 1 1 100%;'>
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
					<select name='freq'>
						<option value='Daily'>Daily</option>
						<option value='Weekly'>Weekly</option>
						<option value='Monthly' selected='selected'>Monthly</option>
						<option value='Yearly'>Yearly</option>
					</select>
				</td>
			</tr>
		</table>
	</form>
</div>
<script type='text/javascript'>
function newGeneralRegularPayment() {
	require("popup_window.js",function() {
		var popup = new popup_window("New Regular Payment","/static/finance/finance_16.png",document.getElementById('new_general_regular_payment'));
		popup.keep_content_on_close = true;
		var form = document.forms['new_general_regular_payment'];
		form.elements['name'].value = "";
		popup.addOkCancelButtons(function() {
			service.json("finance","new_general_regular_payment",{name:form.elements['name'].value,frequency:form.elements['freq'].value},function(res) {
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