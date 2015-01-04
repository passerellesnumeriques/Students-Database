<?php 
class page_edit_operation extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		$op = SQLQuery::create()->select("FinanceOperation")->whereValue("FinanceOperation","id",$_GET["id"])->executeSingleRow();
		if ($op["description"] == null) $op["description"] = "";
		$min = null;
		$max = null;
		$payment_of = SQLQuery::create()
			->select("ScheduledPaymentDateOperation")
			->whereValue("ScheduledPaymentDateOperation","operation",$op["id"])
			->join("ScheduledPaymentDateOperation","FinanceOperation",array("schedule"=>"id"))
			->executeSingleRow();
		if ($payment_of <> null) {
			$min = 0;
			$other_payments = SQLQuery::create()
				->select("ScheduledPaymentDateOperation")
				->whereValue("ScheduledPaymentDateOperation","schedule",$payment_of["schedule"])
				->whereNotValue("ScheduledPaymentDateOperation","operation",$op["id"])
				->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
				->execute();
			$other_amount = 0;
			foreach ($other_payments as $p) $other_amount += floatval($p["amount"]);
			$max = -floatval($payment_of["amount"])-$other_amount;
		} else {
			$payments = SQLQuery::create()
				->select("ScheduledPaymentDateOperation")
				->whereValue("ScheduledPaymentDateOperation","schedule",$op["id"])
				->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
				->execute();
			$paid = 0;
			foreach ($payments as $p) $paid += floatval($p["amount"]);
			$max = -$paid;
		}
?>
<div style='padding:5px;background-color:white'>
<form name='edit' onsubmit='return false;'>
<table>
	<tr>
		<td>Date</td>
		<td><input name='date' type='date' original='<?php echo $op["date"];?>' value='<?php echo $op["date"];?>' onchange="if (this.value == this.getAttribute('original')) pnapplication.dataSaved('date'); else pnapplication.dataUnsaved('date');"/></td>
	</tr>
	<tr>
		<td>Amount</td>
		<td><input name='amount' type='number' <?php if ($min !== null) echo "min='$min' "; if ($max !== null) echo "max='$max' ";?>original='<?php echo $op["amount"];?>' value='<?php echo $op["amount"];?>' onchange="if (parseFloat(this.value) == parseFloat(this.getAttribute('original'))) pnapplication.dataSaved('amount'); else pnapplication.dataUnsaved('amount');"/></td>
	</tr>
	<tr>
		<td>Description</td>
		<td><input name='description' type='text' original=<?php echo json_encode($op["description"]);?> value=<?php echo json_encode($op["description"]);?> onchange='if (this.value == this.getAttribute("original")) pnapplication.dataSaved("date"); else pnapplication.dataUnsaved("date");'/></td>
	</tr>
</table>
</form>
</div>
<?php 
if ($payment_of <> null) {
	echo "<div class='info_footer'>";
	echo "<table><tr><td valign=top>";
	echo "<img src='".theme::$icons_16["info"]."'/>";
	echo "</td><td>";
	echo "This operation is a payment for ".toHTML($payment_of["description"])." (due on ".$payment_of["date"].").<br/>";
	if ($other_amount > 0) {
		$nb = count($other_payments);
		echo $nb." other payment".($nb > 1 ? "s exist" : " exists")." for the same operation with a total of $other_amount.<br/>";
	}
	echo "Maximum amount for this operation is $max";
	echo "</td></tr></table>";
	echo "</div>";
} else if (count($payments) > 0) {
	echo "<div class='info_footer'>";
	echo "<table><tr><td valign=top>";
	echo "<img src='".theme::$icons_16["info"]."'/>";
	echo "</td><td>";
	echo "This operation already started to be paid:<ul>";
	foreach ($payments as $p)
		echo "<li>".$p["amount"]." on ".$p["date"]."</li>";
	echo "</ul>";
	echo "Maximum amount for this operation is $max";
	echo "</td></tr></table>";
	echo "</div>";
}
?>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.removeButtons();
popup.addFrameSaveButton(function() {
	popup.freeze("Saving...");
	var form = document.forms['edit'];
	service.json("finance","save_operation",{id:<?php echo $op["id"];?>,date:form.elements['date'].value,amount:form.elements['amount'].value,description:form.elements['description'].value},function(res) {
		popup.unfreeze();
		if (!res) return;
		<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
		form.elements['date'].setAttribute("original", form.elements['date'].value);
		form.elements['amount'].setAttribute("original", form.elements['amount'].value);
		form.elements['description'].setAttribute("original", form.elements['description'].value);
		pnapplication.cancelDataUnsaved();
	});
});
popup.addCloseButton();
</script>
<?php 
	}
	
}
?>