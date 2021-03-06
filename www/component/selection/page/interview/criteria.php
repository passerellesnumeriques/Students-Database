<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_interview_criteria extends SelectionPage {
	
	public function getRequiredRights() { return array("see_interview_criteria"); }
	
	public function executeSelectionPage() {
		$can_edit = PNApplication::$instance->selection->canEditInterviewCriteria();
		if ($can_edit) {
			require_once("component/data_model/DataBaseLock.inc");
			$locked_by = "";
			$lock_id = DataBaseLock::lockTable("InterviewCriterion_".PNApplication::$instance->selection->getCampaignId(), $locked_by);
			if ($lock_id == null) $can_edit = false;
			else DataBaseLock::generateScript($lock_id);
		} else {
			$lock_id = null;
			$locked_by = null;
		}
		
		$criteria = SQLQuery::create()->select("InterviewCriterion")->execute();
		
		$programs = array();
		if (count($this->component->getPrograms()) == 0)
			$programs[null] = array();
		else foreach ($this->component->getPrograms() as $p)
			$programs[$p["id"]] = array();
		foreach ($programs as $pid=>$info) {
			if ($pid == "") $pid = null;
			$rules = SQLQuery::create()->select("InterviewEligibilityRule")->whereValue("InterviewEligibilityRule","program",$pid)->execute();
			if (count($rules) > 0) {
				$rules_ids = array();
				foreach ($rules as $rule) array_push($rules_ids, $rule["id"]);
				$rules_topics = SQLQuery::create()->select("InterviewEligibilityRuleCriterion")->whereIn("InterviewEligibilityRuleCriterion","rule",$rules_ids)->execute();
			} else
				$rules_topics = array();
			// put topics inside the rules
			foreach ($rules as &$rule) {
				$rule["criteria"] = array();
				foreach ($rules_topics as $topic)
					if ($topic["rule"] == $rule["id"])
						array_push($rule["criteria"], $topic);
			}
			// make a tree of rules
			$root_rules = $this->buildRulesTree($rules, null);
			$programs[$pid] = $root_rules;
		}
		
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
				<img src='/static/selection/interview/interview_32.png'/>
				Interview Criteria and Rules
			</div>
			<div id='page_content' style="padding:10px;overflow:hidden;flex:1 1 auto">
				<?php if ($locked_by <> null) {
					echo "<div class='info_box'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> You cannot edit because this is currently edited by ".toHTML($locked_by)."</div>";
				} else if (!$can_edit && PNApplication::$instance->user_management->hasRight("manage_interview_criteria")) {
					echo "<div class='info_box'><img src='".theme::$icons_16["info"]."' style='vertical-align:bottom'/> You cannot edit because some results have been already entered.</div>";
				} ?>
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
				<div id='rules_sections_container'></div>
			</div>
		</div>
		<script type='text/javascript'>
		window.onuserinactive = function() { location.assign('/dynamic/selection/page/selection_main_page'); };
		var criteria_section = sectionFromHTML('criteria_section');

		var criteria = <?php echo json_encode($criteria);?>;
		var can_edit = <?php echo json_encode($can_edit);?>;

		function gradeStr(grade) {
			var s = grade.toFixed(2);
			if (s.endsWith(".00")) return ""+Math.floor(grade);
			return s;
		}
		function coefStr(coef) {
			var s = coef.toFixed(1);
			if (s.endsWith(".0")) return ""+Math.floor(coef);
			return s;
		}
		
		function addCriterion(criterion) {
			var table = document.getElementById('criteria_table');
			var tr = document.createElement("TR");
			tr.criterion_id = criterion.id;
			table.appendChild(tr);
			var td = document.createElement("TD");
			<?php if ($can_edit) {?>
			tr.field_name = new field_text(criterion.name, true, {max_length:100,can_be_null:false,min_length:1,min_size:30});
			td.appendChild(tr.field_name.getHTMLElement());
			tr.field_name.ondatachanged.addListener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_name'); });
			tr.field_name.ondataunchanged.addListener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_name'); });
			<?php } else {?>
			td.appendChild(document.createTextNode(criterion.name));
			<?php } ?>
			tr.appendChild(td);

			td = document.createElement("TD");
			td.style.textAlign = "right";
			<?php if ($can_edit) {?>
			tr.field_score = new field_decimal(criterion.max_score, true, {min:0,integer_digits:3,decimal_digits:2});
			td.appendChild(tr.field_score.getHTMLElement());
			tr.field_score.ondatachanged.addListener(function() { pnapplication.dataUnsaved('criterion_'+criterion.id+'_score'); });
			tr.field_score.ondataunchanged.addListener(function() { pnapplication.dataSaved('criterion_'+criterion.id+'_score'); });
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
			var locker = lockScreen(null, "Saving interview criteria...");
			service.json("selection","interview/save_criteria",{criteria:criteria},function(res) {
				if (!res) { unlockScreen(locker); return; }
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

		function createPointNode(container, title, can_add_next, program_id, parent_id) {
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
			title_div.style.textAlign = "center";
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
					popupFrame(null,"New Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule?x"+(parent_id ? "&parent="+parent_id : "")+(program_id ? "&program="+program_id : ""));
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
		function createRuleNode(container, rule, program_id) {
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
				node.innerHTML = "Minimum "+gradeStr(min)+"/"+gradeStr(parseFloat(c.max_score))+"<br/>in "+c.name;
			} else {
				var s = "";
				for (var i = 0; i < rule.criteria.length; ++i) {
					if (i > 0) s += " + "; else s += "<span style='color:transparent'> + </span>";
					var c = getCriterion(rule.criteria[i].criterion);
					s += c.name;
					s += " (/"+gradeStr(parseFloat(c.max_score))+")";
					s += " * "+coefStr(parseFloat(rule.criteria[i].coefficient));
					s += "<br/>";
				}
				s += " = "+gradeStr(parseFloat(rule.expected))+" minimum";
				node.innerHTML = s;
			}
			if (can_edit) {
				node.style.cursor = "pointer";
				node.onmouseover = function() { this.style.border = "1px solid #F0D080"; this.style.boxShadow = "2px 2px 2px 0px #A0A0A0"; };
				node.onmouseout = function() { this.style.border = "1px solid #000000"; this.style.boxShadow = ""; };
				node.title = "Click to edit this rule";
				node.onclick = function() {
					popupFrame(null,"Edit Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule?id="+rule.id);
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
					confirmDialog("Are you sure you want to remove this rule and all the ones starting from it ?", function(yes) {
						if (!yes) return;
						lockScreen();
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
					popupFrame(null,"New Eligibility Rule","/dynamic/selection/page/interview/eligibility_rule?parent="+rule.id);
					stopEventPropagation(ev);
					return false;
				};
				add_child.title = "Add a new rule after this one";
			}
			return node;
		}
		function buildRulesGraphStep(container, previous_node, nodes, final_nodes, program_id) {
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
				var n = createRuleNode(n_container, nodes[i], program_id);
				var conn = drawing.connectElements(previous_node, n, drawing.CONNECTOR_NONE, drawing.CONNECTOR_ARROW, "#000000", 1, 'horiz');
				conn.style.zIndex = 1;
				buildRulesGraphStep(n_container, n, nodes[i].children, final_nodes, program_id);
			}
		}
		<?php
		$i = 0;
		foreach ($programs as $pid=>$root_rules) {
			foreach ($this->component->getPrograms() as $p) if ($p["id"] == $pid) { $program = $p; break; }
			$i++;
		?>
		function buildRulesGraph<?php echo $i;?>() {
			var container = document.createElement("DIV");
			container.style.overflowX = "auto";
			var rules_section = new section(null, 'Eligibility Rules'<?php if ($pid <> null) echo "+".json_encode(" for ".$program["name"]);?>,container);
			document.getElementById('rules_sections_container').appendChild(rules_section.element);
			var root_rules = <?php echo json_encode($root_rules);?>;
			var program_id = <?php echo $pid == null ? "null" : $pid?>;
		
			container.style.position = "relative";
			container.style.display = "flex";
			container.style.flexDirection = "row";
			//container.style.justifyContent = "center";
			var start_container = document.createElement("DIV");
			start_container.style.flex = "none";
			start_container.style.display = "flex";
			start_container.style.flexDirection = "column";
			start_container.style.justifyContent = "center";
			container.appendChild(start_container);
			var start = createPointNode(start_container, "Applicants", true, program_id, null);
			var final_nodes = [];
			buildRulesGraphStep(container, start, root_rules, final_nodes, program_id);
			var end_container = document.createElement("DIV");
			end_container.style.flex = "none";
			end_container.style.display = "flex";
			end_container.style.flexDirection = "column";
			end_container.style.justifyContent = "center";
			end_container.style.marginLeft = "30px";
			container.appendChild(end_container);
			var end = createPointNode(end_container, "Eligible"<?php if ($pid <> null) echo "+' for<br/>'+".json_encode($program["name"]);?>, null, false, program_id);
			for (var i = 0; i < final_nodes.length; ++i)
				drawing.connectElements(final_nodes[i], end, drawing.CONNECTOR_NONE, drawing.CONNECTOR_ARROW, "#000000", 1, 'horiz').style.zIndex = 1;
		}
		buildRulesGraph<?php echo $i;?>()
		<?php } ?>
		
		</script>
		<?php 
	}
	
	/**
	 * Build the graph/tree of eligibility rules
	 * @param array $rules lsit of existing rules
	 * @param integer $parent_id id of the rule from which we want the children
	 * @return array children rules
	 */
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