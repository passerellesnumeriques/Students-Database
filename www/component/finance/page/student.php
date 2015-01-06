<?php 
class page_student extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		$can_edit = PNApplication::$instance->user_management->hasRight("edit_student_finance");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div style='flex:1 1 100%;display:flex;flex-direction:row;overflow:auto;'>
		<div style='flex:none;'>
			<?php 
			require_once("student_history.inc");
			studentFinanceOperationsHistory($this, $_GET["people"]);
			?>
		</div>
		<div style='flex 1 1 100%;'>
		</div>
	</div>
	<?php if ($can_edit) { ?>
	<div class='page_footer' style='flex:none'>
		<button class='action' onclick='newPayment();'>New Payment</button>
		<button class='action'>Create Loan</button>
	</div>
	<script type='text/javascript'>
	function newPayment() {
		popupFrame("/static/finance/finance_16.png","New Payment","/dynamic/finance/page/student_payment?ondone=payment_done&student=<?php echo $_GET["people"];?>",null,null,null,function(frame,popup) {
			frame.payment_done = function() {
				location.reload();
			};
		});
	}
	</script>
	<?php } ?>
</div>
<?php 
	}
	
}
?>