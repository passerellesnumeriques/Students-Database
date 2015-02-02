<?php 
class page_student_allowance extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		$student_allowance_id = $_GET["id"];
		$student_allowance = SQLQuery::create()->select("StudentAllowance")->whereValue("StudentAllowance","id",$student_allowance_id)->executeSingleRow();
		$deductions = SQLQuery::create()->select("StudentAllowanceDeduction")->whereValue("StudentAllowanceDeduction","student_allowance",$student_allowance_id)->execute();
		$student = PNApplication::$instance->people->getPeople($student_allowance["student"]);
		$allowance = SQLQuery::create()->select("Allowance")->whereValue("Allowance","id",$student_allowance["allowance"])->executeSingleRow();
		if ($allowance["times"] > 1) {
			$allowances_list = SQLQuery::create()
				->select("StudentAllowance")
				->whereValue("StudentAllowance","student",$student_allowance["student"])
				->whereValue("StudentAllowance","allowance",$student_allowance["allowance"])
				->whereValue("StudentAllowance","date",$student_allowance["date"])
				->orderBy("StudentAllowance","id")
				->execute();
			for ($allowance_index = 0; $allowance_index < count($allowances_list); $allowance_index++)
				if ($allowances_list[$allowance_index]["id"] == $student_allowance_id) break;
		}
		$base_allowance = SQLQuery::create()->select("StudentAllowance")->whereValue("StudentAllowance","student",$student_allowance["student"])->whereValue("StudentAllowance","allowance",$allowance["id"])->whereNull("StudentAllowance","date")->executeSingleRow();
		$base_deductions = SQLQuery::create()->select("StudentAllowanceDeduction")->whereValue("StudentAllowanceDeduction","student_allowance",$base_allowance["id"])->execute();
?>
<div style='background-color:white;'>
<div class='page_section_title2'>
	<?php
	echo toHTML($allowance["name"])." for ".toHTML($student["first_name"]." ".$student["last_name"]).": ";
	$tz = date_default_timezone_get();
	date_default_timezone_set("GMT");
	$date = \datamodel\ColumnDate::toTimestamp($student_allowance["date"]);
	switch ($allowance["frequency"]) {
		case "Weekly":
			echo "Week of ";
		case "Daily":
			echo date("d F Y", $date);
			break;
		case "Monthly":
			echo date("F Y", $date);
			break;
		case "Yearly":
			echo date("Y", $date);
			break;
	}
	date_default_timezone_set($tz);
	if ($allowance["times"] > 1) echo ", allowance number ".($allowance_index+1);
	?>
</div>
<div style='padding:5px'>
	<table>
		<tr>
			<td>Base Amount</td>
			<td style='text-align:right'><?php echo $student_allowance["amount"];?></td>
			<td><?php
			$total = $student_allowance["amount"];
			if ($base_allowance["amount"] <> $student_allowance["amount"]) echo "<i>(modified from initial: ".$base_allowance["amount"].")</i>"; 
			?></td>
		</tr>
		<?php 
		foreach ($base_deductions as $bd) {
			$d = null;
			for ($i = 0; $i < count($deductions); $i++)
				if ($deductions[$i]["name"] == $bd["name"]) {
					$d = $deductions[$i];
					array_splice($deductions, $i, 1);
					break;
				}
			echo "<tr>";
			echo "<td>".toHTML($bd["name"])."</td>";
			echo "<td style='text-align:right'>";
			if ($d == null) echo "<i>No</i></td><td>";
			else {
				if ($d["amount"] == $bd["amount"]) echo "- ".$d["amount"]."</td><td>";
				else echo "- ".$d["amount"]."</td><td><i>(modified from initial: ".$bd["amount"].")</i>";
				$total -= $d["amount"];
			}
			echo "</td>";
			echo "</tr>";
		}
		foreach ($deductions as $d) {
			echo "<tr>";
			echo "<td>".toHTML($bd["name"])."</td>";
			echo "<td style='text-align:right'>";
			echo "-".$d["amount"];
			echo "</td>";
			echo "<td></td>";
			echo "</tr>";
			$total -= $d["amount"];
		}
		?>
		<tr>
			<td style='font-weight:bold;'>TOTAL</td>
			<td style='border-top:1px solid black;font-weight:bold;text-align:right;'><?php echo number_format($total,2);?></td>
			<td></td>
		</tr>
	</table>
</div>
</div>
<?php 
	}
	
}
?>