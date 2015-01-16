<?php 
class page_new_loan extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		require_once 'component/data_model/TableDefinition.inc';
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_date.js");
		$this->requireJavascript("field_integer.js");
		$this->onload("window.loan_date = new field_date('".datamodel\ColumnDate::toSQLDate(getdate())."',true,{can_be_null:false});document.getElementById('date_container').appendChild(window.loan_date.getHTMLElement());");
		$this->onload("window.start_date = new field_date('".datamodel\ColumnDate::toSQLDate(getdate())."',true,{can_be_null:false});document.getElementById('start_date_container').appendChild(window.start_date.getHTMLElement());");
		$this->onload("window.every_nb = new field_integer(1,true,{can_be_null:false,min:1,max:999});document.getElementById('every_container').appendChild(window.every_nb.getHTMLElement());");
		$this->onload("window.nb_times = new field_integer(1,true,{can_be_null:false,min:1,max:999});document.getElementById('nb_times_container').appendChild(window.nb_times.getHTMLElement());window.nb_times.onchange.addListener(function(){refreshRepayAmount();});");
		$this->onload("window.specific_date = new field_date('".datamodel\ColumnDate::toSQLDate(getdate())."',true,{can_be_null:false});document.getElementById('specific_date_container').appendChild(window.specific_date.getHTMLElement());");
?>
<div style='background-color:white;padding:5px;'>
<table>
	<tr>
		<td>Date of the loan</td>
		<td id='date_container'></td>
	</tr>
	<tr>
		<td>Amount of the loan</td>
		<td><input type='number' min=0 id='amount' onchange='refreshRepayAmount();'/></td>
	</tr>
	<tr>
		<td>Reason</td>
		<td><input type='text' size=50 maxlength=250 id='reason'/></td>
	</tr>
</table>
<div class='page_section_title'>Schedule for repayment</div>

<input type='radio' name='schedule_type' checked='checked' id='no_schedule'/> Not yet defined<br/>

<input type='radio' name='schedule_type' id='several_schedule' onchange='refreshRepayAmount();'/>
	Every <span id='every_container'></span>
	<select id='frequency' onchange='refreshRepayAmount();'>
		<option value='Daily'>Day(s)</option>
		<option value='Weekly'>Week(s)</option>
		<option value='Monthly' selected='selected'>Month(s)</option>
		<option value='Yearly'>Year(s)
	</select>
	Starting on <span id='start_date_container'></span>
	for
	<span id='nb_times_container'></span>
	times
	<span id='repay_info'></span>
<br/>

<input type='radio' name='schedule_type' id='one_schedule'/> Full amount on specific date: <span id='specific_date_container'></span><br/>

</div>
<script type='text/javascript'>
function refreshRepayAmount() {
	var span = document.getElementById('repay_info');
	var repay = null;
	if (document.getElementById('several_schedule').checked) {
		var amount = document.getElementById('amount').value.trim();
		if (amount.length > 0) {
			amount = parseFloat(amount);
			if (!isNaN(amount) && amount > 0) {
				if (!window.nb_times.hasError())
					repay = amount/window.nb_times.getCurrentData();
			}
		}
	}
	if (repay === null) span.innerHTML = "";
	else span.innerHTML = "(Repay "+repay.toFixed(2)+" each time)";
	layout.changed(span);
}
var popup = window.parent.getPopupFromFrame(window);
popup.addOkCancelButtons(function() {
	var amount = document.getElementById('amount').value.trim();
	if (amount.length == 0) { alert('Please enter an amount for the loan'); return; }
	amount = parseFloat(amount);
	if (isNaN(amount) || amount <= 0) { alert("Please enter a valid amount"); return; }
	var reason = document.getElementById('reason').value.trim();
	if (reason.length == 0) { alert("Please enter a reason for the loan"); return; }
	var operations = [];
	if (document.getElementById('no_schedule').checked) {
		// no schedule
		operations.push({amount:-amount,date:dateToSQL(new Date()),description:'Loan: '+reason});
	} else if (document.getElementById('one_schedule').checked) {
		// single operation
		if (window.specific_date.hasError()) { alert("Please enter a date for the repayment"); return; }
		operations.push({amount:-amount,date:window.specific_date.getCurrentData(),description:'Repay loan: '+reason});
	} else {
		if (window.every_nb.hasError()) { alert("Please correct the frequency of payment: "+window.every_nb.getError()); return; }
		var freq = document.getElementById('frequency').value;
		if (window.start_date.hasError()) { alert("Please enter a starting date to schedule repayments"); return; }
		if (window.nb_times.hasError()) { alert("Please enter a valid number of repayments"); return; }
		var date = parseSQLDate(window.start_date.getCurrentData());
		var nb = window.nb_times.getCurrentData();
		var remaining = amount*100;
		var i = 1;
		do {
			var repay = Math.floor(remaining/nb--);
			remaining -= repay;
			repay /= 100;
			operations.push({amount:-repay,date:dateToSQL(date),description:'Repay '+(i++)+'/'+window.nb_times.getCurrentData()+' for loan: '+reason});
			switch (freq) {
			case 'Daily': date.setDate(date.getDate()+window.every_nb.getCurrentData()); break;
			case 'Weekly': date.setDate(date.getDate()+7*window.every_nb.getCurrentData()); break;
			case 'Monthly': date.setMonth(date.getMonth()+window.every_nb.getCurrentData()); break;
			case 'Yearly': date.setFullYear(date.getFullYear()+window.every_nb.getCurrentData()); break;
			}
		} while (nb > 0);
	}
	popup.freeze("Creation of the loan...");
	service.json("finance","create_loan",{people:<?php echo $_GET["student"];?>,date:window.loan_date.getCurrentData(),reason:reason,operations:operations},function(res) {
		if (!res) { popup.unfreeze(); return; }
		<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();"?>
		popup.close();
	});
});
</script>
<?php 
	}
	
}
?>