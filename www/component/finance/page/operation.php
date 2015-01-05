<?php 
class page_operation extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		$op = SQLQuery::create()->select("FinanceOperation")->whereValue("FinanceOperation","id",$_GET["id"])->executeSingleRow();
		if ($op == null) {
			PNApplication::error("This operation does not exist anymore");
			return;
		}
		$people = PNApplication::$instance->people->getPeople($op["people"]);
		echo "<div style='background-color:white;padding:5px;'>";
		if ($op["amount"] < 0) $this->dueOperation($op, $people);
		else $this->paymentOperation($op, $people);
		echo "<br/><hr/><button class='action' onclick=\"location.href='edit_operation?id=".$op["id"].(isset($_GET["onchange"]) ? "&onsave=".$_GET["onchange"] : "")."';\"><img src='".theme::$icons_16["edit"]."'/> Edit details</button>";
		echo "</div>";
	}
	
	private function dueOperation($op, $people) {
		// this is a due operation, search from what it is
		// check its schedule, and get payments done
		$schedule = SQLQuery::create()->select("ScheduledPaymentDate")->whereValue("ScheduledPaymentDate","due_operation",$op["id"])->executeSingleRow();
		$payments = SQLQuery::create()->select("PaymentOperation")->whereValue("PaymentOperation","due_operation",$op["id"])->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))->orderBy("FinanceOperation","date")->execute();
		$descr = "";
		$due_date_ts = datamodel\ColumnDate::toTimestamp($op["date"]);
		$due_date = date("d M Y", $due_date_ts);
		if ($schedule <> null) {
			if ($schedule["regular_payment"] <> null) {
				// this operation comes from a general regular payment
				$regular_payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$schedule["regular_payment"])->executeSingleRow();
				switch ($regular_payment["frequency"]) {
					case "Daily":
					case "Weekly":
						$date_str = date("d M Y", $due_date_ts);
						break;
					case "Monthly":
						$date_str = date("F Y", $due_date_ts);
						break;
					case "Yearly":
						$date_str = date("Y", $due_date_ts);
						break;
				}
				$descr = $regular_payment["name"];
				$this->setPopupTitle($regular_payment["name"]." of ".$date_str." for ".$people["first_name"]." ".$people["last_name"]);
				$due_date = $date_str;
				// check if we have next payments also which can be cancelled
				$next_operations = SQLQuery::create()
					->select("FinanceOperation")
					->whereValue("FinanceOperation","people",$op["people"])
					->where("`FinanceOperation`.`date` > '".$op["date"]."'")
					->join("FinanceOperation", "ScheduledPaymentDate", array("id"=>"due_operation"))
					->whereValue("ScheduledPaymentDate", "regular_payment", $schedule["regular_payment"])
					->join("FinanceOperation","PaymentOperation",array("id"=>"due_operation"))
					->execute();
				if (count($next_operations) > 0) {
					$has_next_payment = false;
					foreach ($next_operations as $o) if ($o["payment_operation"] <> null) { $has_next_payment = true; break; }
					if (!$has_next_payment)
						$can_cancel_next_operations = true;
				}
			}
		}
		echo "<table>";
		echo "<tr><td>Due Date:</td><td align=right>$due_date</td></tr>";
		echo "<tr><td>Amount Due:</td><td align=right>".(-floatval($op["amount"]))."</td></tr>";
		$balance = floatval($op["amount"]);
		if (count($payments) == 0)
			echo "<tr><td colspan=2 style='font-style:italic;color:red;'>No payment yet</td></tr>";
		else {
			foreach ($payments as $p) {
				echo "<tr>";
				echo "<td>Paid on ".$p["date"].":</td>";
				echo "<td align=right>".$p["amount"]."</td>";
				echo "</tr>";
				$balance += floatval($p["amount"]);
			}
			echo "<tr><td>Remaining balance</td><td style='text-align:right;border-top:1px solid black;color:";
			if ($balance < 0) echo "red"; else echo "green";
			echo "'>";
			echo $balance;
			echo "</td></tr>";
		}
		echo "</table>";
		if ($balance < 0)
			echo "<button class='action' onclick=\"location.href='student_payment?student=".$op["people"]."&amount=".(-$balance).(isset($_GET["onchange"]) ? "&ondone=".$_GET["onchange"] : "").($schedule<>null ? ($schedule["regular_payment"] <> null ? "&regular_payment=".$schedule["regular_payment"] : "") : "")."';\">Create Payment</button>";
		if (count($payments) == 0)
			echo "<button class='action red' onclick=\"location.href='cancel_due_operation?id=".$op["id"].(isset($_GET["onchange"]) ? "&ondone=".$_GET["onchange"] : "")."';\">Cancel this ".toHTML($descr)."</button>";
		if ($balance < 0 && count($payments) > 0)
			echo "<button class='action red' onclick=\"confirmDialog('Do you confirm the remaining balance is cancelled for this student ?',function(yes){if(!yes)return; service.json('finance','save_operation',{id:".$op["id"].",amount:".(floatval($op["amount"])-$balance)."},function(res){if(!res)return;".(isset($_GET["onchange"]) ? "window.frameElement.".$_GET["onchange"]."();" : "")."location.reload();});});\">Cancel remaining balance of ".(-$balance)."</button>";
		if (@$can_cancel_next_operations && count($payments) == 0) {
			echo "<br/><button class='action red' onclick='cancelNextOperations();'>Cancel all ".toHTML($descr)." starting from $due_date</button>";
			?>
			<script type='text/javascript'>
			function cancelNextOperations() {
				confirmDialog("Are you sure you want to cancel this "+<?php echo json_encode($descr);?>+" and all the next ones ?",function(yes) {
					if (!yes) return;
					var popup = window.parent.getPopupFromFrame(window);
					popup.freeze("Cancelling operations...");
					<?php
					$nb = 0; 
					echo "service.json('finance','remove_operation',{id:".$op["id"]."},function(res) {";
					echo "if (!res) { popup.unfreeze(); return; }";
					$nb++;
					foreach ($next_operations as $o) {
						echo "service.json('finance','remove_operation',{id:".$o["id"]."},function(res) {";
						echo "if (!res) { popup.unfreeze(); return; }";
						$nb++;
					}
					if (isset($_GET["onchange"])) echo "window.frameElement.".$_GET["onchange"]."();";
					echo "popup.close();";
					while ($nb > 0) {
						echo "});";
						$nb--;
					}
					?>
				});
			}
			</script>
			<?php 
		}
	}
	
	private function paymentOperation($op, $people) {
		$payment_of = SQLQuery::create()->select("PaymentOperation")->whereValue("PaymentOperation","payment_operation",$op["id"])->join("PaymentOperation","FinanceOperation",array("due_operation"=>"id"))->executeSingleRow();
		$this->setPopupTitle("Payment Of ".$payment_of["description"]);
		echo "<table>";
		echo "<tr><td>Date:</td><td>".date("d M Y", datamodel\ColumnDate::toTimestamp($op["date"]))."</td></tr>";
		echo "<tr><td>Amount:</td><td>".$op["amount"]."</td></tr>";
		echo "</table>";
		echo "<button class='action red' onclick=\"confirmDialog('Are you sure you want to remove this operation ?',function(yes){if(!yes)return;service.json('finance','remove_operation',{id:".$op["id"]."},function(res){if(!res)return;".(isset($_GET["onchange"]) ? "window.frameElement.".$_GET["onchange"]."();" : "")."window.parent.getPopupFromFrame(window).close();});});\">Remove this payment</button>";
	}
	
	private function setPopupTitle($title) {
?>
<script type='text/javascript'>
window.parent.getPopupFromFrame(window).setTitle("/static/finance/finance_16.png",<?php echo json_encode($title);?>);
</script>
<?php 
	}
	
}
?>