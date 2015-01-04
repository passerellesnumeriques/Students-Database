<?php 
function studentFinanceOperationsHistory(&$page, $student_id) {
	$operations = SQLQuery::create()->select("FinanceOperation")->whereValue("FinanceOperation","people",$student_id)->orderBy("FinanceOperation","date")->execute();
	$ids = array();
	foreach ($operations as $op) $ids[$op["id"]] = $op;
	$list = SQLQuery::create()->select("ScheduledPaymentDateOperation")->whereIn("ScheduledPaymentDateOperation","schedule",array_keys($ids))->execute();
	$payments = array();
	foreach ($list as $payment) {
		if (!isset($payments[$payment["schedule"]]))
			$payments[$payment["schedule"]] = array(
				"payments"=>array($payment["operation"]),
				"total"=>$ids[$payment["operation"]]["amount"]
			);
		else {
			array_push($payments[$payment["schedule"]]["payments"], $payment["operation"]);
			$payments[$payment["schedule"]]["total"] += $ids[$payment["operation"]]["amount"];
		}
	}
	$list = SQLQuery::create()->select("ScheduledPaymentDate")->whereIn("ScheduledPaymentDate","due_operation",array_keys($ids))->execute();
	$regular_payments_ids = array();
	foreach ($list as $e)
		if ($e["regular_payment"] <> null) {
			if (!in_array($e["regular_payment"], $regular_payments_ids)) array_push($regular_payments_ids, $e["regular_payment"]);
			$ids[$e["due_operation"]]["regular_payment"] = $e["regular_payment"];
		}
	if (count($regular_payments_ids) > 0)
		$regular_payments = SQLQuery::create()->select("FinanceRegularPayment")->whereIn("FinanceRegularPayment","id",$regular_payments_ids)->execute();
	
	theme::css($page, "grid.css");
	echo "<div style='border:1px solid black;display:inline-block;margin:3px;box-shadow:2px 2px 2px 0px #808080;'>";
	echo "<div style='background-color:white;padding:1px 3px;border-bottom:1px solid #808080;font-weight:bold;color:#3030A0;'>";
	echo "Finance Operations History";
	echo "</div>";
	echo "<table class='grid'>";
	echo "<thead>";
	echo "<tr>";
	echo "<th>Date</th>";
	echo "<th>Amount</th>";
	echo "<th>Description</th>";
	echo "<th>Status</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	$last_future = false;
	$now = time();
	foreach ($operations as $op) {
		$ts = datamodel\ColumnDate::toTimestamp($op["date"]);
		if ($ts > $now && !$last_future) {
			$last_future = true;
			echo "<tr><td colspan=4 style='text-align:center;font-weight:bold;font-style:italic;background-color:#D0D0D0;'>Future operations</td></tr>";
		}
		echo "<tr>";
		echo "<td>".$op["date"]."</td>";
		echo "<td style='text-align:right;color:";
		if ($op["amount"] < 0) echo "red"; else if ($op["amount"] > 0) echo "green"; else echo "black";
		echo "'>".$op["amount"]."</td>";
		echo "<td>".toHTML($op["description"])."</td>";
		echo "<td>";
		if ($op["amount"] < 0) {
			// this is an amount due
			$link = "/dynamic/finance/page/student_payment?student=".$student_id."&ondone=refresh";
			$regular_payment = @$ids[$op["id"]]["regular_payment"];
			if ($regular_payments <> null) $link .= "&regular_payment=".$regular_payment."&selected_date=".$op["date"];
			$onclick = "onclick=\"window.top.popupFrame('/static/finance/finance_16.png','Student Payment','$link',null,null,null,function(frame,popup){frame.refresh=function(){location.reload();};});\"";
			if (!isset($payments[$op["id"]]))
				echo "<a style='color:red' class='black_link' href='#' $onclick>Not yet paid</a>";
			else {
				$descr = "";
				foreach ($payments[$op["id"]]["payments"] as $payment_id)
					$descr .= $ids[$payment_id]["amount"]." paid on ".date("d M Y",datamodel\ColumnDate::toTimestamp($ids[$payment_id]["date"]))."\r\n";
				$paid = $payments[$op["id"]]["total"];
				if ($paid == -$op["amount"]) {
					echo "<span title='$descr' style='color:green'><img src='".theme::$icons_10["ok"]."' style='vertical-align:top;margin-top:1px;margin-right:2px;'/>Paid</span>";
				} else {
					echo "<a title='$descr' style='color:darkorange' class='black_link' href='#' $onclick>".$paid." paid, remaining = ".(-$op["amount"]-$paid)."</a>";
				}
			}
		} else {
			// this is a payment
		}
		echo "</td>";
		echo "</tr>";
	}
	echo "</tbody>";
	echo "</table>";
	echo "</div>";
}
?>