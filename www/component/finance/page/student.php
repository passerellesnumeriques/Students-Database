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
		<button class='action'>Loan money to the student</button>
	</div>
	<?php } ?>
</div>
<?php 
	}
	
}
?>