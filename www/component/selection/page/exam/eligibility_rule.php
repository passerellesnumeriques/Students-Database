<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_exam_eligibility_rule extends SelectionPage {
	
	public function getRequiredRights() { return array("manage_exam_rules"); }
	public function executeSelectionPage(){
		$id = isset($_GET["id"]) ? intval($_GET["id"]) : -1;
		$parent = isset($_GET["parent"]) ? intval($_GET["parent"]) : null;
		
		if ($id > 0) {
			require_once 'component/data_model/DataBaseLock.inc';
			$locked_by = null;
			$lock_id = DataBaseLock::lockRow("ExamEligibilityRule_".PNApplication::$instance->selection->getCampaignId(), $id, $locked_by);
			if ($lock_id == null) {
				echo "<div class='error_box'><img src='".theme::$icons_16["error"]."' style='vertical-align:bottom'/> $locked_by is already editing this rule.</div>";
				return;
			}
			DataBaseLock::generateScript($lock_id);
			$rule = SQLQuery::create()->select("ExamEligibilityRule")->whereValue("ExamEligibilityRule","id",$id)->executeSingleRow();
			$parent = $rule["parent"];
			$topics = SQLQuery::create()->select("ExamEligibilityRuleTopic")->whereValue("ExamEligibilityRuleTopic","rule",$id)->execute();
		} else {
			$rule = array("id"=>-1, "parent"=>$parent, "expected"=>0);
			$topics = array();
		}

		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		$extracts = SQLQuery::create()->select("ExamSubjectExtract")->execute();
		$extracts_parts = SQLQuery::create()->select("ExamSubjectExtractParts")->join("ExamSubjectExtractParts","ExamSubjectPart",array("part"=>"id"))->execute();

		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_decimal.js");
		?>
<div style='background-color:white;padding:10px;'>
	<table>
		<tr id='row_header'><th>Subject</th><th>Coef.</th></tr>
		<tr id='row_total'><td colspan=2 style='border-top:1px solid black'> = <span id='total'></span> minimum</td></tr>
	</table>
</div>
<script type='text/javascript'>
var rule_id = <?php echo $id;?>;
var parent_id = <?php echo json_encode($parent);?>;
var subjects = <?php echo json_encode($subjects);?>;
var extracts = <?php echo json_encode($extracts);?>;
var extracts_parts = <?php echo json_encode($extracts_parts);?>;

var total_field = new field_decimal(<?php echo $id > 0 ? $rule["expected"] : 0; ?>,true,{min:0,can_be_null:false,integer_digits:4,decimal_digits:1});
document.getElementById('total').appendChild(total_field.getHTMLElement());

function ValueRow(subject, extract, coef) {
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
	for (var i = 0; i < subjects.length; ++i) {
		o = document.createElement("OPTION");
		o.value = 'subject_'+subjects[i].id; o.text = subjects[i].name;
		if (subject == subjects[i].id) selected = this.select.options.length;
		this.select.add(o);
	}
	for (var i = 0; i < extracts.length; ++i) {
		o = document.createElement("OPTION");
		o.value = 'extract_'+extracts[i].id; o.text = extracts[i].name;
		if (extract == extracts[i].id) selected = this.select.options.length;
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
		
		if (sel.startsWith("subject_")) {
			var id = sel.substring(8);
			for (var i = 0; i < subjects.length; ++i)
				if (subjects[i].id == id) { t.max_score.innerHTML = parseFloat(subjects[i].max_score).toFixed(2); break; }
		} else {
			var id = sel.substring(8);
			var max = 0;
			for (var i = 0; i < extracts_parts.length; ++i)
				if (extracts_parts[i].extract == id) {
					max += parseFloat(extracts_parts[i].max_score); 
				}
			t.max_score.innerHTML = max.toFixed(2);
		}
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
foreach ($topics as $topic)
	echo "new ValueRow(".json_encode($topic["subject"]).",".json_encode($topic["extract"]).",".$topic["coefficient"].");"; 
?>
new ValueRow();

var changed = false;
var popup = window.parent.getPopupFromFrame(window);
popup.onclose = function() {
	if (changed) window.parent.location.reload();
};
popup.addSaveButton(function () {
	if (total_field.hasError()) { alert("The total is invalid"); return; }
	var topics = [];
	var table = document.getElementById('row_header').parentNode;
	for (var i = 1; i < table.childNodes.length-1; ++i) {
		var row = table.childNodes[i].obj;
		var topic = { subject: null, extract: null, coefficient: 1 };
		var sel = row.select.value;
		if (sel == "") continue;
		if (sel.startsWith("subject_"))
			topic.subject = sel.substring(8);
		else
			topic.extract = sel.substring(8);
		if (row.field_coef.hasError()) { alert("Invalid coefficient"); return; }
		topic.coefficient = row.field_coef.getCurrentData();
		topics.push(topic);
	}
	popup.freeze("Saving rule...");
	service.json("selection","exam/save_rule",{
		id: rule_id,
		parent: parent_id,
		expected: total_field.getCurrentData(),
		topics: topics
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