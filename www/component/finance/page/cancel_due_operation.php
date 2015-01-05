<?php 
class page_cancel_due_operation extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		$op = SQLQuery::create()->select("FinanceOperation")->whereValue("FinanceOperation","id",$_GET["id"])->executeSingleRow();
		if ($op == null) {
			PNApplication::error("This operation doesn't exist anymore");
			return;
		}
		if ($op["amount"] > 0) {
			PNApplication::error("Invalid operation");
			return;
		}
		$payments = SQLQuery::create()->select("PaymentOperation")->whereValue("PaymentOperation","due_operation",$_GET["id"])->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))->execute();
		if (count($payments) > 0) {
			// TODO
			PNApplication::error("This operation has already payments done and cannot be cancelled");
			return;
		}
		$people = PNApplication::$instance->people->getPeople($op["people"]);
?>
<div style='background-color:white;padding:5px;'>
	Are you sure you want to cancel <?php echo toHTML($op["description"])?> for <?php echo toHTML($people["first_name"]." ".$people["last_name"]);?> ?
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);
popup.removeButtons();
popup.addYesNoButtons(function() {
	popup.freeze("Cancelling operation...");
	service.json("finance","remove_operation",{id:<?php echo $_GET["id"];?>},function(res) {
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