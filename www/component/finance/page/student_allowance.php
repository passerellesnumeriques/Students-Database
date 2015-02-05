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
		$can_edit = PNApplication::$instance->user_management->hasRight("edit_student_finance");
		if ($can_edit) {
			$this->requireJavascript("animation.js");
			$this->requireJavascript("typed_field.js");
			$this->requireJavascript("field_decimal.js");
		}
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
<style type='text/css'>
table {
	border-spacing: 0px;
}
td {
	padding: 1px;
	border: 1px solid rgba(0,0,0,0);
}
td.editable:hover {
	border: 1px solid #C0C0FF;
}
</style>
<div style='padding:5px'>
	<table>
		<tr>
			<?php if ($can_edit) echo "<td></td>";?>
			<td>Base Amount</td>
			<td style='text-align:right;<?php if ($can_edit) echo "cursor:pointer;' title='Click to edit' class='editable' onclick='editBaseAmount(this);";?>'><?php echo $student_allowance["amount"];?></td>
			<td><?php
			$total = $student_allowance["amount"];
			if ($base_allowance["amount"] <> $student_allowance["amount"]) echo "<i>(modified from initial: ".$base_allowance["amount"].")</i>"; 
			?></td>
		</tr>
		<?php 
		$script = "";
		$remaining_deductions = array_merge($deductions);
		$empty_deductions = array();
		foreach ($base_deductions as $bd) {
			$d = null;
			for ($i = 0; $i < count($remaining_deductions); $i++)
				if ($remaining_deductions[$i]["name"] == $bd["name"]) {
					$d = $remaining_deductions[$i];
					array_splice($remaining_deductions, $i, 1);
					break;
				}
			if ($d == null) array_push($empty_deductions, $bd);
			$tr_id = $this->generateID();
			echo "<tr id='$tr_id'>";
			if ($can_edit) {
				echo "<td>";
				if ($d <> null) {
					$img_id = $this->generateID();
					echo "<img id='$img_id' src='".theme::$icons_10["remove"]."' style='cursor:pointer;visibility:hidden;' title='Remove this deduction' onclick='removeDeduction(this,".$d["id"].");'/>";
					$script .= "animation.appearsOnOver(document.getElementById('$tr_id'), [document.getElementById('$img_id')]);";
				}
				echo "</td>";
			}
			echo "<td>".toHTML($bd["name"])."</td>";
			echo "<td style='text-align:right;".($can_edit ? "cursor:pointer;' title='Click to edit' class='editable' onclick='editDeduction(this,".($d<>null?$d["id"]:$bd["id"]).");'" : "'").">";
			if ($d == null) echo "<i>No</i></td><td>";
			else {
				if ($d["amount"] == $bd["amount"]) echo "- ".$d["amount"]."</td><td>";
				else echo "- ".$d["amount"]."</td><td><i>(modified from initial: ".$bd["amount"].")</i>";
				$total -= $d["amount"];
			}
			echo "</td>";
			echo "</tr>";
		}
		foreach ($remaining_deductions as $d) {
			$tr_id = $this->generateID();
			echo "<tr id='$tr_id'>";
			if ($can_edit) {
				echo "<td>";
				if ($d <> null) {
					$img_id = $this->generateID();
					echo "<img id='$img_id' src='".theme::$icons_10["remove"]."' style='cursor:pointer;visibility:hidden;' title='Remove this deduction' onclick='removeDeduction(this,".$d["id"].");'/>";
					$script .= "animation.appearsOnOver(document.getElementById('$tr_id'), [document.getElementById('$img_id')]);";
				}
				echo "</td>";
			}
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
			<?php if ($can_edit) echo "<td></td>";?>
			<td style='font-weight:bold;'>TOTAL</td>
			<td style='border-top:1px solid black;font-weight:bold;text-align:right;' id='total_amount'><?php echo number_format($total,2);?></td>
			<td></td>
		</tr>
	</table>
</div>
</div>
<script type='text/javascript'>
var popup = window.parent.getPopupFromFrame(window);

<?php if ($can_edit) {?>
var base_amount = <?php echo $base_allowance["amount"];?>;
var initial_base_amount = base_amount;
var deductions = <?php echo json_encode($deductions);?>;
var empty_deductions = <?php echo json_encode($empty_deductions);?>;
var base_deductions = <?php echo json_encode($base_deductions);?>;
var total_amount = document.getElementById('total_amount');
var amount_deducted = {};
var new_deduction_id = -1;
for (var i = 0; i < deductions.length; ++i)
	amount_deducted[deductions[i].id] = deductions[i].amount;
for (var i = 0; i < empty_deductions.length; ++i)
	amount_deducted[empty_deductions[i].id] = 0;
function editBaseAmount(td) {
	var field = new field_decimal(base_amount,true,{min:0,can_be_null:false,integer_digits:10,decimal_digits:2});
	td.removeAllChildren();
	td.appendChild(field.getHTMLElement());
	td.onclick = null;
	td.title = '';
	td.style.cursor = '';
	td.className = '';
	layout.changed(td);
	field.onchange.addListener(function() {
		base_amount = field.getCurrentData();
		refreshTotal();
	});
	field.ondatachanged.addListener(function() { pnapplication.dataUnsaved('base_amount'); });
	field.ondataunchanged.addListener(function() { pnapplication.dataSaved('base_amount'); });
}
function editDeduction(td, deduction_id) {
	var field = new field_decimal(amount_deducted[deduction_id],true,{min:0,can_be_null:false,integer_digits:10,decimal_digits:2});
	td.removeAllChildren();
	td.appendChild(document.createTextNode("- "));
	td.appendChild(field.getHTMLElement());
	td.onclick = null;
	td.title = '';
	td.style.cursor = '';
	td.className = '';
	layout.changed(td);
	field.onchange.addListener(function() {
		amount_deducted[deduction_id] = field.getCurrentData();
		refreshTotal();
	});
	field.ondatachanged.addListener(function() { pnapplication.dataUnsaved('deduction_'+deduction_id); });
	field.ondataunchanged.addListener(function() { pnapplication.dataSaved('deduction_'+deduction_id); });
}
function removeDeduction(button, deduction_id) {
	amount_deducted[deduction_id] = 0;
	var tr = button.parentNode.parentNode;
	layout.changed(tr.parentNode);
	tr.parentNode.removeChild(tr);
	refreshTotal();
}
function refreshTotal() {
	var total = base_amount;
	for (var id in amount_deducted) total -= amount_deducted[id];
	total_amount.innerHTML = total.toFixed(2);
}
popup.addIconTextButton(theme.icons_16.add, "New Deduction...", "add_deduction", function() {
	var content = document.createElement("DIV");
	content.style.padding = "10px";
	var radios = [];
	// first, propose to add the base deductions which have been removed
	for (var i = 0; i < base_deductions.length; ++i) {
		var existing = null;
		for (var j = 0; j < deductions.length; ++j)
			if (deductions[j].name == base_deductions[i].name) { existing = deductions[j]; break; }
		if (existing != null) {
			if (amount_deducted[existing.id] != 0) continue; // already set
			// was previously set, but it has been removed in the screen
		} else {
			if (amount_deducted[base_deductions[i].id]) continue; // already added
		}
		var radio = document.createElement("INPUT");
		radio.type = 'radio';
		radio.name = 'choice';
		radio.style.verticalAlign = 'bottom';
		radio._deduction = existing ? existing : base_deductions[i];
		radios.push(radio);
		content.appendChild(radio);
		content.appendChild(document.createTextNode(radio._deduction.name));
		content.appendChild(document.createElement("BR"));
	}
	// then, propose to create a custom one
	var radio_custom = document.createElement("INPUT");
	radio_custom.type = 'radio';
	radio_custom.name = 'choice';
	radio_custom.style.verticalAlign = 'bottom';
	content.appendChild(radio_custom);
	content.appendChild(document.createTextNode("Custom deduction: "));
	var input_custom = document.createElement("INPUT");
	input_custom.type = 'text';
	input_custom.maxLength = 30;
	input_custom.style.verticalAlign = "bottom";
	content.appendChild(input_custom);
	require("popup_window.js", function() {
		var p = new popup_window("New Deduction", null, content);
		p.addOkCancelButtons(function() {
			var d = null;
			if (radio_custom.checked) {
				var name = input_custom.value.trim();
				if (name.length == 0) { alert("Please enter a name for the new deduction"); return; }
				for (var i = 0; i < deductions.length; ++i)
					if (deductions[i].name.isSame(name)) {
						if (amount_deducted[deductions[i].id] != 0) { alert("This deduction already exists"); return; }
						d = deductions[i];
						break;
					}
				if (d == null)
					for (var i = 0; i < base_deductions.length; ++i)
						if (base_deductions[i].name.isSame(name)) { alert("This deduction already exists"); return; }
			} else {
				for (var i = 0; i < radios.length; ++i) if (radios[i].checked) { d = radios[i]._deduction; break; }
				if (d == null) { alert("Please select a deduction to add"); return; }
			}
			if (d == null) {
				d = {
					id: new_deduction_id--,
					name: name,
					amount: 0
				};
				amount_deducted[d.id] = 0;
			}
			var table = total_amount.parentNode.parentNode;
			var tr = document.createElement("TR");
			var td = document.createElement("TD");
			var icon = document.createElement("IMG");
			icon.src = theme.icons_10.remove;
			icon.style.cursor = 'pointer';
			icon.style.visibility = 'hidden';
			icon.title = 'Remove this deduction';
			icon.onclick = function() { removeDeduction(this, d.id); };
			td.appendChild(icon);
			tr.appendChild(td);
			td = document.createElement("TD");
			td.appendChild(document.createTextNode(d.name));
			tr.appendChild(td);
			td = document.createElement("TD");
			editDeduction(td, d.id);
			tr.appendChild(td);
			td = document.createElement("TD");
			td = document.createElement("TD");
			table.insertBefore(tr, total_amount.parentNode);
			animation.appearsOnOver(tr, [icon]);
			p.close();
			pnapplication.dataUnsaved('new_deduction');
		});
		p.show();
	});
});
popup.addFrameSaveButton(function() {
	// TODO
});
<?php } /* can edit */ ?>
<?php echo $script; ?>
popup.addCloseButton();
</script>
<?php 
	}
	
}
?>