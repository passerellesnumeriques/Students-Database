<?php 
class page_new_loan extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		require_once 'component/data_model/TableDefinition.inc';
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_date.js");
		$this->requireJavascript("field_integer.js");
		$this->onload("window.start_date = new field_date('".datamodel\ColumnDate::toSQLDate(getdate())."',true,{can_be_null:false});document.getElementById('start_date_container').appendChild(window.start_date.getHTMLElement());");
		$this->onload("window.every_nb = new field_integer(1,true,{can_be_null:false,min:1,max:999});document.getElementById('every_container').appendChild(window.every_nb.getHTMLElement());");
?>
<div style='background-color:white;padding:5px;'>
<table>
	<tr>
		<td>Amount of the loan</td>
		<td><input type='number' min=0/></td>
	</tr>
	<tr>
		<td>Reason</td>
		<td><input type='text' size=50 maxlength=250/></td>
	</tr>
</table>
<div class='page_section_title'>Schedule for repayment</div>

<input type='radio' name='schedule_type' checked='checked'/> Not yet defined<br/>

<input type='radio' name='schedule_type'/>
	Every <span id='every_container'></span>
	<select>
		<option value='Daily'>Day(s)</option>
		<option value='Weekly'>Week(s)</option>
		<option value='Monthly' selected='selected'>Month(s)</option>
		<option value='Yearly'>Year(s)
	</select>
	Starting on <span id='start_date_container'></span>
	
</div>
<?php 
	}
	
}
?>