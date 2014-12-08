<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_eligibility_rule extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_interview_criteria"); }
	public function executeSelectionPage(){
		$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;
		$parent = isset($_GET["parent"]) ? intval($_GET["parent"]) : null;
		
		if ($id > 0) {
			require_once 'component/data_model/DataBaseLock.inc';
			$locked_by = null;
			$lock_id = DataBaseLock::lockRow("InterviewEligibilityRule_".PNApplication::$instance->selection->getCampaignId(), $id, $locked_by);
			if ($lock_id == null) {
				echo "<div class='error_box'><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> $locked_by is already editing this rule.</div>";
				return;
			}
			DataBaseLock::generateScript($lock_id);
			$rule = SQLQuery::create()->select("InterviewEligibilityRule")->whereValue("InterviewEligibilityRule","id",$id)->executeSingleRow();
			$parent = $rule["parent"];
			$rule_criteria = SQLQuery::create()->select("InterviewEligibilityRuleCriterion")->whereValue("InterviewEligibilityRuleCriterion","rule",$id)->execute();
		} else {
			$rule = array("id"=>-1, "parent"=>$parent, "expected"=>0);
			$rule_criteria = array();
		}

		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();

		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_decimal.js");
		?>
<div style='background-color:white;padding:10px;'>
	<table>
		<tr id='row_header'><th>Criterion</th><th>Coef.</th></tr>
		<tr id='row_total'><td colspan=2 style='border-top:1px solid black'> = <span id='total'></span> minimum</td></tr>
	</table>
</div>
<script type='text/javascript'>
var rule_id = <?php echo $id;?>;
var parent_id = <?php echo json_encode($parent);?>;
var criteria = <?php echo json_encode($criteria);?>;

var total_field = new field_decimal(<?php echo $id > 0 ? $rule["expected"] : 0; ?>,true,{min:0,can_be_null:false,integer_digits:4,decimal_digits:1});
document.getElementById('total').appendChild(total_field.getHTMLElement());

function ValueRow(criterion, coef) {
	var t=this;
	this.tr = document.createElement("TR");
	this.tr.obj = this;
	var td = document.createElement("TD");
	td.style.whiteSpace = "nowrap";
	this.plus = document.createElement("SPAN");
	this.plus.innerHTML = "+ ";
	td.appendChild(this.plus);
	this.select = document.createElement("SELECT");
	var o = document.createElement("OPTION");
	o.value = ''; o.text = '';
	this.select.add(o);
	var selected = 0;
	for (var i = 0; i < criteria.length; ++i) {
		o = document.createElement("OPTION");
		o.value = criteria[i].id; o.text = criteria[i].name;
		if (criterion == criteria[i].id) selected = this.select.options.length;
		this.select.add(o);
	}
	this.select.selectedIndex = selected;
	td.appendChild(this.select);
	td.appendChild(document.createTextNode(" ("));
	this.max_score = document.createElement("SPAN");
	td.appendChild(this.max_score);
	td.appendChild(document.createTextNode(") points"));
	this.tr.appendChild(td);
	td = document.createElement("TD");
	td.appendChild(document.createTextNode(" * "));
	this.field_coef = new field_decimal(coef ? coef : 1,true,{min:0.1,max:100,can_be_null:false,integer_digits:3,decimal_digits:1});
	td.appendChild(this.field_coef.getHTMLElement());
	this.tr.appendChild(td);

	this.select.onchange = function(ev) {
		var sel = this.value;
		if (sel == "" && ev) {
			t.max_score.innerHTML = "";
			if (t.tr.nextSibling.id != 'row_total') {
				if (t.tr.previousSibling.id == 'row_header')
					t.tr.nextSibling.obj.plus.style.visibility = 'hidden';
				t.tr.parentNode.removeChild(t.tr);
			}
			layout.changed(document.body);
			return;
		}
		if (t.tr.nextSibling.id == 'row_total' && ev)
			new ValueRow();
		
		for (var i = 0; i < criteria.length; ++i)
			if (criteria[i].id == sel) { t.max_score.innerHTML = parseFloat(criteria[i].max_score).toFixed(2); break; }
		layout.changed(t.tr);
	};

	var next = document.getElementById('row_total');
	for (var i = 0; i < next.parentNode.childNodes.length; ++i)
		if (next.parentNode.childNodes[i].nodeType != 1) { next.parentNode.removeChild(next.parentNode.childNodes[i]); i--; }
	if (next.parentNode.childNodes.length == 2)
		this.plus.style.visibility = 'hidden';
	else
		next.parentNode.childNodes[1].obj.plus.style.visibility = "hidden";
	next.parentNode.insertBefore(this.tr, next);
	this.select.onchange();
}

<?php
foreach ($rule_criteria as $c)
	echo "new ValueRow(".json_encode($c["criterion"]).",".$c["coefficient"].");"; 
?>
new ValueRow();

var changed = false;
var popup = window.parent.get_popup_window_from_frame(window);
popup.onclose = function() {
	if (changed) window.parent.location.reload();
};
popup.addSaveButton(function () {
	if (total_field.hasError()) { alert("The total is invalid"); return; }
	var criteria = [];
	var table = document.getElementById('row_header').parentNode;
	for (var i = 1; i < table.childNodes.length-1; ++i) {
		var row = table.childNodes[i].obj;
		var criterion = { criterion:row.select.value, coefficient: 1 };
		if (criterion.criterion == "") continue;
		if (row.field_coef.hasError()) { alert("Invalid coefficient"); return; }
		criterion.coefficient = row.field_coef.getCurrentData();
		criteria.push(criterion);
	}
	popup.freeze("Saving rule...");
	service.json("selection","interview/save_rule",{
		id: rule_id,
		parent: parent_id,
		expected: total_field.getCurrentData(),
		criteria: criteria
	},function(res){
		popup.unfreeze();
		if (res) {
			rule_id = res.id;
			changed = true;
		}
	});
});
popup.addCloseButton();
</script>
		<?php 
	}
}
?>