<?php 
class page_student extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
?>
<div style='width:100%;display:flex;flex-direction:column;'>
	<div style='flex:none'>
		<?php 
		require_once("student_history.inc");
		studentFinanceOperationsHistory($this, $_GET["people"]);
		?>
	</div>
	<div style='flex 1 1 100%;'>
	</div>
</div>
<?php 
	}
	
}
?>