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
			$title = "Payment of ".toHTML($people["first_name"]." ".$people["last_name"])." for ".toHTML($regular_payment["name"]);
			$due_operation = SQLQuery::create()
				->select("ScheduledPaymentDate")
				->whereValue("ScheduledPaymentDate", "regular_payment", $regular_payment_id)
				->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"))
				->whereValue("FinanceOperation","people",$people_id)
				->whereValue("FinanceOperation","date",$date)
				->executeSingleRow();
			if ($due_operation <> null)
				$default_amount = -floatval($due_operation["amount"]);
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
					->join("ScheduledPaymentDate","ScheduledPaymentDateOperation",array("due_operation"=>"schedule"))
					->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
					->whereValue("FinanceOperation","people",$people_id)
					->expression("SUM(`FinanceOperation`.`amount`)", "paid")
					->executeSingleValue();
				if ($paid == null) $paid = 0; else $paid = floatval($paid);
			}
				
		} else {
			// TODO
		}
		echo "<div class='page_section_title'>$title</div>";
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
				->select("ScheduledPaymentDateOperation")
				->whereIn("ScheduledPaymentDateOperation","schedule",$due_operations_ids)
				->join("ScheduledPaymentDateOperation","FinanceOperation",array("operation"=>"id"))
				->execute();
			$amount = floatval($_GET["amount"]);
			$operations = array();
			foreach ($due_operations as $due) {
				$paid = 0;
				foreach ($payments_done as $p)
					if ($p["schedule"] == $due["due_operation"])
						$paid += floatval($p["amount"]);
				$due_amount = -floatval($due["amount"]);
				if ($paid == $due_amount) continue; // already paid
				$payment_amount = $amount > $due_amount ? $due_amount : $amount;
				array_push($operations, array(
					"schedule"=>$due,
					"amount"=>$payment_amount,
					"remaining"=>$due_amount-$paid-$payment_amount
				));
				$amount -= $payment_amount;
				if ($amount == 0) break;
			}
			echo "The following payments will be registered:<ul>";
			foreach ($operations as $op) {
				echo "<li>";
				echo toHTML($regular_payment["name"])." of ";
				switch ($regular_payment["frequency"]) {
					case "Daily":
					case "Weekly":
						echo date("d M Y", datamodel\ColumnDate::toTimestamp($op["schedule"]["date"]));
						break;
					case "Monthly":
						echo date("F Y", datamodel\ColumnDate::toTimestamp($op["schedule"]["date"]));
						break;
					case "Yearly":
						$d = datamodel\ColumnDate::splitDate($op["schedule"]["date"]);
						echo $d["year"];
						break;
				}
				echo ": ".$op["amount"]." paid, remaining = ".$op["remaining"];
				echo "</li>";
			}
			echo "</ul>";
			if ($amount > 0) {
				echo "<div class='warning_box'><img src='".theme::$icons_16["warning"]."' style='vertical-align:bottom'/> Warning: Remaining amount of $amount cannot be assigned to a ".toHTML($regular_payment["name"])." and will be ignored.</div>";
			}
		}
		echo "</div>";
		echo "</div>";
	}
	
}
?>