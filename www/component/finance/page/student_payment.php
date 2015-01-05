<?php 
class page_student_payment extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		$people_id = $_GET["student"];
		$people = PNApplication::$instance->people->getPeople($people_id);
		$default_amount = "";
		echo "<div style='background-color:white;'>";
		if (isset($_GET["regular_payment"])) {
			// case of regular payment
			$regular_payment_id = $_GET["regular_payment"];
			$date = $_GET["selected_date"];
			$regular_payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$regular_payment_id)->executeSingleRow();
			$due_operation = SQLQuery::create()
				->select("ScheduledPaymentDate")
				->whereValue("ScheduledPaymentDate", "regular_payment", $regular_payment_id)
				->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"))
				->whereValue("FinanceOperation","people",$people_id)
				->whereValue("FinanceOperation","date",$date)
				->executeSingleRow();
			$due_date_ts = datamodel\ColumnDate::toTimestamp($date);
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
			echo "<div class='page_section_title'>".toHTML($regular_payment["name"])." of ";
			echo toHTML($people["first_name"]." ".$people["last_name"]);
			echo " for $date_str";
			echo "</div>";
			if ($due_operation == null) {
				// TODO this has been cancelled...
			} else {
				$default_amount = -floatval($due_operation["amount"]);
				$payments = SQLQuery::create()
					->select("PaymentOperation")
					->whereValue("PaymentOperation","due_operation",$due_operation["due_operation"])
					->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
					->execute();
				echo "<div style='padding:3px 5px;'>";
				$balance = floatval($due_operation["amount"]);
				if (count($payments) > 0) {
					foreach ($payments as $payment) {
						echo $payment["amount"]." paid on ".date("d M Y", datamodel\ColumnDate::toTimestamp($payment["date"]))."<br/>";
						$balance += floatval($payment["amount"]);
					}
				}
				echo "Remaining balance: ";
				echo "<span style='color:";
				if ($balance < 0) echo "red";
				else if ($balance == 0) echo "black";
				else echo "green";
				echo "'>$balance</span>";
				echo "<div>";
				if (count($payments) > 0) {
					echo "<button class='action'>Cancel remaining balance</button>";
				} else {
					echo "<button class='action' onclick='cancelDueOperation(".$due_operation["due_operation"].")'>Cancel ".toHTML($regular_payment["name"]." of ".$date_str." for ".$people["first_name"]." ".$people["last_name"])."</button>";
				}
				echo "</div>";
				echo "</div>";
				echo "<hr/>";
			}
			$due = SQLQuery::create()
				->select("ScheduledPaymentDate")
				->whereValue("ScheduledPaymentDate", "regular_payment", $regular_payment_id)
				->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"))
				->whereValue("FinanceOperation","people",$people_id)
				->expression("SUM(IF(`FinanceOperation`.`date` <= CURRENT_DATE(),`FinanceOperation`.`amount`,0))", "past_due")
				->expression("SUM(IF(`FinanceOperation`.`date` > CURRENT_DATE(),`FinanceOperation`.`amount`,0))", "future_due")
				->executeSingleRow();
			if ($due <> null && $due["past_due"] !== NULL) {
				$current_due_amount = -floatval($due["past_due"]);
				$future_due_amount = -floatval($due["future_due"]);
				$paid = SQLQuery::create()
					->select("ScheduledPaymentDate")
					->whereValue("ScheduledPaymentDate", "regular_payment", $regular_payment_id)
					->join("ScheduledPaymentDate","PaymentOperation",array("due_operation"=>"due_operation"))
					->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
					->whereValue("FinanceOperation","people",$people_id)
					->expression("SUM(`FinanceOperation`.`amount`)", "paid")
					->executeSingleValue();
				if ($paid == null) $paid = 0; else $paid = floatval($paid);
			}
			
		} else {
			// TODO
		}
		?>
		<script type='text/javascript'>
		function cancelDueOperation(id) {
			location.href = "/dynamic/finance/page/cancel_due_operation?id="+id<?php if (isset($_GET["ondone"])) echo "+'&ondone=".$_GET["ondone"]."'";?>;
		}
		</script>
		<?php 
		echo "<div class='page_section_title'>Create New Payment</div>";
		echo "<div style='padding:5px'>";
		if (!isset($_GET["amount"])) {
			if (isset($current_due_amount)) {
				$balance = $paid-$current_due_amount;
				echo "Current situation:<ul><li>Due: ".$current_due_amount."</li><li>Paid: ".$paid."</li><li>= <span style='color:".($balance < 0 ? "red" : ($balance == 0 ? "black" : "green"))."'>$balance</span></ul>";
				echo "Future payments due: ".$future_due_amount."<br/>";
				echo "Maximum payment = ".($current_due_amount+$future_due_amount-$paid)."<br/>";
				echo "<hr/>";
			}
			$today = datamodel\ColumnDate::toSQLDate(getdate());
			echo "<form method='GET' name='payment_spec'>";
			foreach ($_GET as $name=>$value) echo "<input type='hidden' name='$name' value='$value'/>";
			echo "<table>";
			echo "<tr><td>Amount paid</td><td><input name='amount' type='number' value='$default_amount'/></td></tr>";
			echo "<tr><td>Date of payment</td><td><input name='payment_date' type='date' value='$today'/></td></tr>";
			echo "</table>";
			echo "</form>";
			echo "<button class='action' onclick=\"document.forms['payment_spec'].submit();\">Continue <img src='".theme::$icons_16["right"]."'/></button>";
			echo "</div>";
			echo "</div>";
			return;
		}
		if (isset($regular_payment)) {
			$due_operations = SQLQuery::create()
				->select("ScheduledPaymentDate")
				->whereValue("ScheduledPaymentDate", "regular_payment", $regular_payment_id)
				->join("ScheduledPaymentDate", "FinanceOperation", array("due_operation"=>"id"))
				->whereValue("FinanceOperation", "people", $people_id)
				->orderBy("FinanceOperation","date")
				->execute();
			if (count($due_operations) == 0) {
				echo "<div class='error_box'>".toHTML($people["first_name"]." ".$people["last_name"])." doesn't have to pay ".toHTML($regular_payment["name"])."</div>";
				echo "</div>";
				return;
			}
			$due_operations_ids = array();
			$selected_due = null;
			foreach ($due_operations as $op) {
				array_push($due_operations_ids, $op["due_operation"]);
				if ($op["date"] == $date) $selected_due = $op;
			}
			$payments_done = SQLQuery::create()
				->select("PaymentOperation")
				->whereIn("PaymentOperation","due_operation",$due_operations_ids)
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$amount = floatval($_GET["amount"]);
			$operations = array();
			foreach ($due_operations as $due) {
				$paid = 0;
				foreach ($payments_done as $p)
					if ($p["due_operation"] == $due["due_operation"])
						$paid += floatval($p["amount"]);
				$due_amount = -floatval($due["amount"]);
				if ($paid >= $due_amount) continue; // already paid
				$payment_amount = $amount > $due_amount-$paid ? $due_amount-$paid : $amount;
				$descr = $regular_payment["name"]." of ";
				switch ($regular_payment["frequency"]) {
					case "Daily":
					case "Weekly":
						$descr .= date("d M Y", datamodel\ColumnDate::toTimestamp($due["date"]));
						break;
					case "Monthly":
						$descr .= date("F Y", datamodel\ColumnDate::toTimestamp($due["date"]));
						break;
					case "Yearly":
						$d = datamodel\ColumnDate::splitDate($due["date"]);
						$descr .= $d["year"];
						break;
				}
				array_push($operations, array(
					"schedule"=>$due,
					"amount"=>$payment_amount,
					"remaining"=>$due_amount-$paid-$payment_amount,
					"description"=>$descr
				));
				$amount -= $payment_amount;
				if ($amount == 0) break;
			}
			echo "The following payments will be registered:<ul>";
			foreach ($operations as $op) {
				echo "<li>".toHTML($op["description"]);
				echo ": ".$op["amount"]." paid, remaining = ".$op["remaining"];
				echo "</li>";
			}
			echo "</ul>";
			if ($amount > 0) {
				echo "<div class='warning_box'><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Warning: Remaining amount of $amount cannot be assigned to a ".toHTML($regular_payment["name"])." and will be ignored.</div>";
			}
			?>
			<button class='action' onclick='createPayments();'>Confirm</button>
			<script type='text/javascript'>
			function createPayments() {
				var data = {
					student: <?php echo $people_id;?>,
					date: <?php echo json_encode($_GET["payment_date"]);?>,
					operations:[]
				};
				<?php
				foreach ($operations as $op) {
					echo "data.operations.push({amount:".$op["amount"].",schedule:".$op["schedule"]["due_operation"].",description:".json_encode($op["description"])."});\n";
				}
				?>
				var popup = window.parent.getPopupFromFrame(window);
				popup.freeze("Creation of payments...");
				service.json("finance","student_payment",data,function(res) {
					if (!res) { popup.unfreeze(); return; }
					<?php if (isset($_GET["ondone"])) echo "window.frameElement.".$_GET["ondone"]."();";?>
					popup.close();
				});
			}
			</script>
			<?php 
		}
		echo "</div>";
		echo "</div>";
	}
	
}
?>