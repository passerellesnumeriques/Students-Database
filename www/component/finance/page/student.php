<?php 
class page_student extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		$can_edit = PNApplication::$instance->user_management->hasRight("edit_student_finance");
		$people = PNApplication::$instance->people->getPeople($_GET["people"]);
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div style='flex:1 1 100%;display:flex;flex-direction:row;overflow:auto;'>
		<div style='flex:none;'>
			<?php 
			require_once("student_history.inc");
			$operations = studentFinanceOperationsHistory($this, $_GET["people"]);
			?>
		</div>
		<div style='flex 1 1 100%;'>
			<div style='border:1px solid black;display:inline-block;margin:3px;box-shadow:2px 2px 2px 0px #808080;'>
				<table class='grid' style='font-size:10pt;'>
				<thead>
					<tr>
						<th></th>
						<th>Current balance</th>
						<th>Future balance</th>
						<th>Total</th>
					</tr>
				</thead><tbody>
					<?php
					if (count($operations) > 0)
						$schedules = SQLQuery::create()
							->select("ScheduledPaymentDate")
							->whereIn("ScheduledPaymentDate","due_operation",array_keys($operations))
							->execute();
					else
						$schedules = array();
					$regular_payment_ids = array();
					$loans_ids = array();
					foreach ($schedules as $sched) {
						if ($sched["regular_payment"] <> null && !in_array($sched["regular_payment"], $regular_payment_ids)) array_push($regular_payment_ids, $sched["regular_payment"]);
						if ($sched["loan"] <> null && !in_array($sched["loan"], $loans_ids)) array_push($loans_ids, $sched["loan"]);
					}
					$current_total = 0;
					$future_total = 0;
					$total_total = 0;
					if (count($loans_ids) > 0) {
						$balance = SQLQuery::create()
							->select("ScheduledPaymentDate")
							->whereIn("ScheduledPaymentDate","loan",$loans_ids)
							->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"),"due")
							->join("due","PaymentOperation",array("id"=>"due_operation"),"payment")
							->join("payment","FinanceOperation",array("payment_operation"=>"id"),"paid")
							->expression("(SUM(IF(due.date <= CURRENT_DATE(),due.amount,0))+SUM(IF(paid.date <= CURRENT_DATE(),paid.amount,0)))", "current")
							->expression("(SUM(IF(due.date > CURRENT_DATE(),due.amount,0))+SUM(IF(paid.date > CURRENT_DATE(),paid.amount,0)))", "future")
							->executeSingleRow();
						echo "<tr><td>Loans</td><td style='text-align:right;";
						if ($balance["current"] < 0) echo "color:red;"; else if ($balance["current"] > 0) echo "color:green;";
						echo "'>".$balance["current"]."</td><td style='text-align:right;";
						if ($balance["future"] < 0) echo "color:red;"; else if ($balance["future"] > 0) echo "color:green;";
						echo "'>".$balance["future"]."</td><td style='text-align:right;";
						$total = $balance["current"]+$balance["future"];
						if ($total < 0) echo "color:red;"; else if ($total > 0) echo "color:green;";
						echo "'>$total</td></tr>";
						$current_total += $balance["current"];
						$future_total += $balance["future"];
						$total_total += $total;
					}
					foreach ($regular_payment_ids as $rp_id) {
						$rp = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$rp_id)->executeSingleRow();
						$balance = SQLQuery::create()
							->select("ScheduledPaymentDate")
							->whereValue("ScheduledPaymentDate","regular_payment",$rp_id)
							->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"),"due")
							->join("due","PaymentOperation",array("id"=>"due_operation"),"payment")
							->whereValue("due","people",$people["id"])
							->join("payment","FinanceOperation",array("payment_operation"=>"id"),"paid")
							->expression("(SUM(IF(due.date <= CURRENT_DATE(),due.amount,0))+SUM(IF(paid.date <= CURRENT_DATE(),paid.amount,0)))", "current")
							->expression("(SUM(IF(due.date > CURRENT_DATE(),due.amount,0))+SUM(IF(paid.date > CURRENT_DATE(),paid.amount,0)))", "future")
							->executeSingleRow();
						echo "<tr><td>".toHTML($rp["name"])."</td><td style='text-align:right;";
						if ($balance["current"] < 0) echo "color:red;"; else if ($balance["current"] > 0) echo "color:green;";
						echo "'>".$balance["current"]."</td><td style='text-align:right;";
						if ($balance["future"] < 0) echo "color:red;"; else if ($balance["future"] > 0) echo "color:green;";
						echo "'>".$balance["future"]."</td><td style='text-align:right;";
						$total = $balance["current"]+$balance["future"];
						if ($total < 0) echo "color:red;"; else if ($total > 0) echo "color:green;";
						echo "'>$total</td></tr>";
						$current_total += $balance["current"];
						$future_total += $balance["future"];
						$total_total += $total;
					}
					echo "<tr style='border-top:2px solid black;font-weight:bold;'><td>TOTAL</td><td style='text-align:right;";
					if ($current_total < 0) echo "color:red;"; else if ($current_total > 0) echo "color:green;";
					echo "'>$current_total</td><td style='text-align:right;";
					if ($future_total < 0) echo "color:red;"; else if ($future_total > 0) echo "color:green;";
					echo "'>$future_total</td><td style='text-align:right;";
					if ($total_total < 0) echo "color:red;"; else if ($total_total > 0) echo "color:green;";
					echo "'>$total_total</td></tr>";
					?>
				</tbody>
				</table>
			</div>
		</div>
	</div>
	<?php if ($can_edit) { ?>
	<div class='page_footer' style='flex:none'>
		<button class='action' onclick='newPayment();'>New Payment</button>
		<button class='action' onclick='newLoan();'>Create Loan</button>
	</div>
	<script type='text/javascript'>
	function newPayment() {
		popupFrame("/static/finance/finance_16.png","New Payment","/dynamic/finance/page/student_payment?ondone=payment_done&student=<?php echo $_GET["people"];?>",null,null,null,function(frame,popup) {
			frame.payment_done = function() {
				location.reload();
			};
		});
	}
	function newLoan() {
		popupFrame("/static/finance/finance_16.png","New Loan To "+<?php echo json_encode($people["first_name"]." ".$people["last_name"]);?>,"/dynamic/finance/page/new_loan?ondone=done&student=<?php echo $_GET["people"];?>",null,null,null,function(frame,popup) {
			frame.done = function() {
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