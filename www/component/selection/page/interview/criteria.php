<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_criteria extends SelectionPage {
	
	public function getRequiredRights() { return array("see_interview_criteria"); }
	
	public function executeSelectionPage() {
		$can_edit = PNApplication::$instance->selection->canEditInterviewCriteria();
		
		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();
		
		// get eligibility rules
		$rules = SQLQuery::create()->select("InterviewEligibilityRule")->execute();
		$rules_criteria = SQLQuery::create()->select("InterviewEligibilityRuleCriterion")->execute();
		// put topics inside the rules
		foreach ($rules as &$rule) {
			$rule["criteria"] = array();
			foreach ($rules_criteria as $criterion)
				if ($criterion["rule"] == $rule["id"])
				array_push($rule["criteria"], $criterion);
		}
		// make a tree of rules
		$root_rules = $this->buildRulesTree($rules, null);
		
		$this->requireJavascript("drawing.js");
		
		$this->requireJavascript("section.js");
		if ($can_edit) {
			$this->requireJavascript("typed_field.js");
			$this->requireJavascript("field_text.js");
			$this->requireJavascript("field_decimal.js");
		}
		?>
		<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none'>
				Interview Criteria and Rules
			</div>
			<div id='page_content' style="padding:10px;overflow:hidden;flex:1 1 auto">
				<div 
					id='criteria_section'
					title='Criteria'
					collapsable='true'
				>
					<table><tbody id='criteria_table'>
					<tr>
						<th>Criterion</th>
						<th>Max Score</th>
						<th></th>
					</tr>
					</tbody></table>
				</div>
				<div 
					id='rules_section'
					title='Eligibility Rules'
				>
					<div id='rules_container'>
					</div>
				</div>
			</div>
		</div>
		<script type='text/javascript'>
		var criteria_section = sectionFromHTML('criteria_section');
		var rules_section = sectionFromHTML('rules_section');

		var criteria = <?php echo json_encode($criteria);?>;
		var root_rules = <?php echo json_encode($root_rules);?>;
		var can_edit = <?php echo json_encode($can_edit);?>;
		
		function addCriterion(criterion) {
			var table = document.getElementById('criteria_table');
			var tr = document.createElement("TR");
			tr.criterion_id = criterion.id;
			table.appendChild(tr);
			var td = document.createElement("TD");
			<?php if ($can_edit) {?>
			tr.field_name = new field_text(criterion.name, true, {max_length:100,can_be_null:false,min_length:1,min_size:30});
			td.appendChild(tr.field_name.getHTMLElement());
			tr.field_name.ondatachanged.add_listener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_name'); });
			tr.field_name.ondataunchanged.add_listener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_name'); });
			<?php } else {?>
			td.appendChild(document.createTextNode(criterion.name));
			<?php } ?>
			tr.appendChild(td);

			td = document.createElement("TD");
			td.style.textAlign = "right";
			<?php if ($can_edit) {?>
			tr.field_score = new field_decimal(criterion.max_score, true, {min:0,integer_digits:3,decimal_digits:2});
			td.appendChild(tr.field_score.getHTMLElement());
			tr.field_score.ondatachanged.add_listener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_score'); });
			tr.field_score.ondataunchanged.add_listener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_score'); });
			<?php } else {?>
			td.appendChild(document.createTextNode(parseFloat(criterion.max_score).toFixed(2)));
			<?php } ?>
			tr.appendChild(td);

			<?php if ($can_edit) {?>
			td = document.createElement("TD");
			var remove = document.createElement("BUTTON");
			remove.className = "flat icon";
			remove.innerHTML = "<img src='"+theme.icons_16.remove+"'/>";
			remove.onclick = function() {
				if (criterion.id > 0)
					pnapplication.dataUnsaved('criterion_removed_'+criterion.id);
				else {
					pnapplication.dataSaved('new_criterion_'+criterion.id);
					pnapplication.dataSaved('criterion_'+criterion.id+"_name");
					pnapplication.dataSaved('criterion_'+criterion.id+"_score");
				}
				tr.parentNode.removeChild(tr);
			};
			td.appendChild(remove);
			tr.appendChild(td);
			<?php } ?>
		}

		for (var i = 0; i < criteria.length; ++i)
			addCriterion(criteria[i]);

		var new_criteria_id_counter = -1;
		function newCriterion() {
			criterion = { id: new_criteria_id_counter--, name: '', max_score: 0};
			pnapplication.dataUnsaved('new_criterion_'+criterion.id);
			addCriterion(criterion);
		}

		function saveCriteria() {
			var table = document.getElementById('criteria_table');
			var criteria = [];
			for (var i = 1; i < table.childNodes.length; ++i) {
				var tr = table.childNodes[i];
				if (!tr.criterion_id) continue;
				var name = tr.field_name.getCurrentData().trim();
				if (name.length == 0) { alert("Please specify a name for each criterion"); return; }
				var score = tr.field_score.getCurrentData();
				if (score == null) { alert("Please specify a score for each criterion"); return; }
				criteria.push({id:tr.criterion_id,name:name,max_score:score});
			}
			var locker = lock_screen(null, "Saving interview criteria...");
			service.json("selection","interview/save_criteria",{criteria:criteria},function(res) {
				if (!res) { unlock_screen(locker); return; }
				pnapplication.cancelDataUnsaved();
				location.reload();
			});
		}

		<?php if ($can_edit) {?>
		criteria_section.addButton(null,"Add Criterion","action green",newCriterion);
		pnapplication.autoDisableSaveButton(criteria_section.addButton(theme.icons_16.save, "Save", "action", saveCriteria));
		pnapplication.autoDisableSaveButton(criteria_section.addButton(null,"Cancel Modifications","action",function() {
			pnapplication.cancelDataUnsaved();
			location.reload();
		}));
		<?php } ?>

		function createPointNode(container, title, can_add_next, parent_id) {
			var node = document.createElement("DIV");
			node.style.flex = "none";
			node.style.display = "flex";
			node.style.flexDirection = "column";
			node.style.alignItems = "center";
			node.style.margin = "10px";
			node.style.position = "relative";
			var title_div = document.createElement("DIV");
			title_div.innerHTML = title;
			title_div.style.marginBottom = "3px";
			node.appendChild(title_div);
			var circle = document.createElement("DIV");
			circle.style.border = "2px solid black";
			circle.style.width = "10px";
			circle.style.height = "10px";
			circle.style.marginBottom = "16px";
			setBorderRadius(circle,10,10,10,10,10,10,10,10);
			node.appendChild(circle);
			var inner_circle = document.createElement("DIV");
			inner_circle.style.border = "1px solid black";
			inner_circle.style.width = "4px";
			inner_circle.style.height = "4px";
			inner_circle.style.backgroundColor = "black";
			inner_circle.style.marginLeft = "2px";
			inner_circle.style.marginTop = "2px";
			setBorderRadius(inner_circle,4,4,4,4,4,4,4,4);
			circle.appendChild(inner_circle);
			if (can_add_next && can_edit) {
				var next = document.createElement("IMG");
				next.src = theme.icons_10.add;
				next.title = "Add a new rule";
				next.style.position = "absolute";
				next.style.right = "0px";
				next.style.top = "19px";
				setOpacity(next, 0.6);
				next.style.cursor = "pointer";
				next.onmouseover = function() { setOpacity(this,1); };
				next.onmouseout = function() { setOpacity(this,0.6); };
				node.appendChild(next);
				next.onclick = function() {
					popup_frame(null,"New Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule"+(parent_id ? "?parent="+parent_id : ""));
				};
			}
			container.appendChild(node);
			return node;
		}
		function getCriterion(id) {
			for (var i = 0; i < criteria.length; ++i)
				if (criteria[i].id == id)
					return criteria[i];
			return null;
		}
		function createRuleNode(container, rule) {
			var node_container = document.createElement("DIV");
			node_container.style.position = "relative";
			node_container.style.flex = "none";
			node_container.style.zIndex = 2;
			container.appendChild(node_container);
			
			var node = document.createElement("DIV");
			node.style.zIndex = 2;
			node.style.border = "1px solid black";
			setBorderRadius(node,3,3,3,3,3,3,3,3);
			node.style.padding = "3px";
			if (rule.criteria.length == 1) {
				var c = getCriterion(rule.criteria[0].criterion);
				var min = parseFloat(rule.expected)/parseFloat(rule.criteria[0].coefficient);
				node.innerHTML = "Minimum "+min.toFixed(2)+"/"+c.max_score+" in "+c.name;
			} else {
				var s = "";
				for (var i = 0; i < rule.criteria.length; ++i) {
					if (i > 0) s += " + ";
					var c = getCriterion(rule.criteria[i].criterion);
					s += c.name;
					s += " (/"+c.max_score+")";
					s += " * "+parseFloat(rule.criteria[i].coefficient).toFixed(1);
				}
				s += "<br/>= "+parseFloat(rule.expected).toFixed(2)+" minimum";
				node.innerHTML = s;
			}
			if (can_edit) {
				node.style.cursor = "pointer";
				node.onmouseover = function() { this.style.border = "1px solid #F0D080"; this.style.boxShadow = "2px 2px 2px 0px #A0A0A0"; };
				node.onmouseout = function() { this.style.border = "1px solid #000000"; this.style.boxShadow = ""; };
				node.title = "Click to edit this rule";
				node.onclick = function() {
					popup_frame(null,"Edit Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule?id="+rule.id);
				};
			}
			node_container.appendChild(node);

			if (can_edit) {
				var remove = document.createElement("IMG");
				remove.src = theme.icons_10.remove;
				remove.style.marginLeft = "3px";
				node.appendChild(remove);
				setOpacity(remove, 0.6);
				remove.style.cursor = "pointer";
				remove.onmouseover = function() { setOpacity(this,1); };
				remove.onmouseout = function() { setOpacity(this,0.6); };
				remove.onclick = function(ev) {
					confirm_dialog("Are you sure you want to remove this rule and all the ones starting from it ?", function(yes) {
						if (!yes) return;
						lock_screen();
						service.json("selection","interview/remove_eligibility_rule",{id:rule.id},function(res){
							window.location.reload();
						});
					});
					stopEventPropagation(ev);
					return false;
				};
				remove.title = "Remove this rule";
			
				var add_child = document.createElement("IMG");
				add_child.src = theme.icons_10.add;
				add_child.style.marginLeft = "3px";
				node.appendChild(add_child);
				setOpacity(add_child, 0.6);
				add_child.style.cursor = "pointer";
				add_child.onmouseover = function() { setOpacity(this,1); };
				add_child.onmouseout = function() { setOpacity(this,0.6); };
				add_child.onclick = function(ev) {
					popup_frame(null,"New Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule?parent="+rule.id);
					stopEventPropagation(ev);
					return false;
				};
				add_child.title = "Add a new rule after this one";
			}
			return node;
		}
		function buildRulesGraphStep(container, previous_node, nodes, final_nodes) {
			if (nodes == null || nodes.length == 0) {
				final_nodes.push(previous_node);
				return;
			}
			var step_container = document.createElement("DIV");
			step_container.style.flex = "none";
			step_container.style.display = "flex";
			step_container.style.flexDirection = "column";
			step_container.style.alignItems = "center";
			step_container.style.justifyContent = "center";
			step_container.style.marginLeft = "20px";
			step_container.style.marginRight = "20px";
			step_container.style.position = "relative";
			step_container.style.zIndex = 2;
			container.appendChild(step_container);
			for (var i = 0; i < nodes.length; ++i) {
				var n_container = document.createElement("DIV");
				n_container.style.flex = "none";
				n_container.style.display = "flex";
				n_container.style.flexDirection = "row";
				n_container.style.alignItems = "center";
				n_container.style.margin = "10px";
				n_container.style.position = "relative";
				n_container.style.justifyContent = "center";
				n_container.style.zIndex = 2;
				step_container.appendChild(n_container);
				var n = createRuleNode(n_container, nodes[i]);
				var conn = drawing.connectElements(previous_node, n, drawing.CONNECTOR_NONE, drawing.CONNECTOR_ARROW, "#000000", 'horiz');
				conn.style.zIndex = 1;
				buildRulesGraphStep(n_container, n, nodes[i].children, final_nodes);
			}
		}
		function buildRulesGraph() {
			var container = document.getElementById('rules_container');
			container.removeAllChildren();
			container.style.position = "relative";
			container.style.display = "flex";
			container.style.flexDirection = "row";
			container.style.justifyContent = "center";
			var start_container = document.createElement("DIV");
			start_container.style.flex = "none";
			start_container.style.display = "flex";
			start_container.style.flexDirection = "column";
			start_container.style.justifyContent = "center";
			container.appendChild(start_container);
			var start = createPointNode(start_container, "Applicants", true, null);
			var final_nodes = [];
			buildRulesGraphStep(container, start, root_rules, final_nodes);
			var end_container = document.createElement("DIV");
			end_container.style.flex = "none";
			end_container.style.display = "flex";
			end_container.style.flexDirection = "column";
			end_container.style.justifyContent = "center";
			end_container.style.marginLeft = "30px";
			container.appendChild(end_container);
			var end = createPointNode(end_container, "Eligible");
			for (var i = 0; i < final_nodes.length; ++i)
				drawing.connectElements(final_nodes[i], end, drawing.CONNECTOR_NONE, drawing.CONNECTOR_ARROW, "#000000", 'horiz').style.zIndex = 1;
		}
		buildRulesGraph();
		
		</script>
		<?php 
	}
	
	private function buildRulesTree(&$rules, $parent_id) {
		$children = array();
		foreach ($rules as $rule) {
			if ($rule["parent"] == $parent_id)
				array_push($children, $rule);
		}
		foreach ($children as &$rule) {
			$rule["children"] = $this->buildRulesTree($rules, $rule["id"]);
		}
		return $children;
	}
	
}
?>