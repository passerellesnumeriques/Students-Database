<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_exam_eligibility_rules extends SelectionPage {
	public function getRequiredRights() { return array("see_exam_rules"); }
	public function executeSelectionPage(){
		$this->requireJavascript("section.js");

		// get subjects
		$subjects = SQLQuery::create()->select("ExamSubject")->execute();
		// get all subjects' parts
		$subjects_parts = SQLQuery::create()->select("ExamSubjectPart")->execute();
		// get all extracts and their associated exam parts
		$extracts = SQLQuery::create()->select("ExamSubjectExtract")->execute();
		if (count($extracts) > 0) {
			$extract_parts = SQLQuery::create()->select("ExamSubjectExtractParts")->join("ExamSubjectExtractParts","ExamSubjectPart",array("part"=>"id"))->execute();
			foreach ($extracts as &$e) {
				$e["parts"] = array();
				for ($i = 0; $i < count($extract_parts); $i++) {
					if ($extract_parts[$i]["extract"] <> $e["id"]) continue;
					array_push($e["parts"], $extract_parts[$i]);
					array_splice($extract_parts, $i, 1);
					$i--;
				}
				$e["subject"] = $e["parts"][0]["exam_subject"];
			}
		}
		
		// get eligibility rules
		$rules = SQLQuery::create()->select("ExamEligibilityRule")->execute();
		$rules_topics = SQLQuery::create()->select("ExamEligibilityRuleTopic")->execute();
		// put topics inside the rules
		foreach ($rules as &$rule) {
			$rule["topics"] = array();
			foreach ($rules_topics as $topic)
				if ($topic["rule"] == $rule["id"])
					array_push($rule["topics"], $topic);
		}
		// make a tree of rules
		$root_rules = $this->buildRulesTree($rules, null);
		
		$can_edit = PNApplication::$instance->user_management->has_right("manage_exam_rules") && PNApplication::$instance->selection->canEditExamSubjects();
		
		$this->requireJavascript("drawing.js");
		$script = "";
		?>
		<div style='width:100%;height:100%;overflow:hidden;display:flex;flex-direction:column;'>
			<div class='page_title' style='flex:none'>
				Eligibility rules for written exams
			</div>
			<div id='rules_page_content' style="padding:10px;overflow:hidden;flex:1 1 auto">
				<div 
					id='subjects_section'
					title='Subjects'
					collapsable='true'
				>
					<?php foreach ($subjects as $subject) { ?>
					<div style='display:inline-block;position:relative;vertical-align:top;'>
						<div style='display:inline-block;text-align:center;margin:10px;vertical-align:top'>
							<div id='subject_<?php echo $subject["id"];?>' style="border:1px solid rgba(0,0,0,0);border-radius:5px;padding:5px;cursor:pointer" onmouseover="this.style.border='1px solid #F0D080';" onmouseout="this.style.border='1px solid rgba(0,0,0,0)';" onclick="popup_frame('/static/selection/exam/exam_subject_16.png', 'Exam Subject', '/dynamic/selection/page/exam/subject?id=<?php echo $subject["id"];?>&readonly=true');">
								<img src='/static/selection/exam/exam_subject_48.png'/><br/>
								<span style='font-size:12pt;font-weight:bold'><?php echo htmlentities($subject["name"]);?></span><br/>
							</div>
							<?php if ($can_edit) { ?>
							<button class='action' onclick="extractSubject(<?php echo $subject["id"];?>);">Extract...</button>
							<?php } ?>
						</div>
						<?php
						$list = array();
						foreach ($extracts as &$e) if ($e["subject"] == $subject["id"]) array_push($list, $e);
						if (count($list) > 0) {
							echo "<div style='display:inline-block;height:90px;margin-left:50px;'>";
							echo "<div style='height:90px;display:flex;flex-direction:column;justify-content:center;'>";
								foreach ($list as $e) {
									echo "<div id='extract_".$e["id"]."' style='flex:none;padding:3px;font-weight:bold;border:1px solid #A0A0A0;border-radius:3px;margin-top:5px;margin-bottom:5px;'>";
									echo "<a class='black_link' href='#' title='Click to edit' onclick='editExtract(".$e["id"].");return false;'>";
									echo htmlentities($e["name"]);
									echo "</a>";
									if ($can_edit)
										echo " <button class='flat small_icon' title='Remove' onclick='removeExtract(".$e["id"].");'><img src='".theme::$icons_10["remove"]."'/></button>";
									echo "</div>";
									$script .= "drawing.connectElements(document.getElementById('subject_".$subject["id"]."'), document.getElementById('extract_".$e["id"]."'), drawing.CONNECTOR_CIRCLE, drawing.CONNECTOR_ARROW, '#000000');";
								}
							echo "</div>";
							echo "</div>";
						} 
						?>
					</div>
					<?php } ?>
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
		var subjects_section = sectionFromHTML('subjects_section');
		var rules_section = sectionFromHTML('rules_section');

		<?php echo $script;?>

		var root_rules = <?php echo json_encode($root_rules);?>;
		var subjects = <?php echo json_encode($subjects);?>;
		var extracts = <?php echo json_encode($extracts);?>;
		var can_edit = <?php echo json_encode($can_edit);?>;
		
		function extractSubject(subject_id) {
			popup_frame(null,'Extract Parts from Subject','/dynamic/selection/page/exam/subject_extract?subject='+subject_id);
		}
		function editExtract(extract_id) {
			popup_frame(null,'Extract Parts from Subject','/dynamic/selection/page/exam/subject_extract?id='+extract_id);
		}
		function removeExtract(extract_id) {
			lock_screen();
			service.json("selection","exam/remove_subject_extract",{id:extract_id},function(res) {
				location.reload();
			});
		}

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
					popup_frame(null,"New Eligibility Rule","/dynamic/selection/page/exam/eligibility_rule"+(parent_id ? "?parent="+parent_id : ""));
				};
			}
			container.appendChild(node);
			return node;
		}
		function getTopicName(topic) {
			if (topic.subject) {
				for (var i = 0; i < subjects.length; ++i)
					if (subjects[i].id == topic.subject)
						return subjects[i].name;
				return "unknown subject id "+topic.subject;
			}
			for (var i = 0; i < extracts.length; ++i)
				if (extracts[i].id == topic.extract)
					return extracts[i].name;
			return "unknown extract id "+topic.extract;
		}
		function getTopicMaxScore(topic) {
			if (topic.subject) {
				for (var i = 0; i < subjects.length; ++i)
					if (subjects[i].id == topic.subject)
						return subjects[i].max_score;
				return 0;
			}
			for (var i = 0; i < extracts.length; ++i)
				if (extracts[i].id == topic.extract) {
					var max = 0;
					for (var j = 0; j < extracts[i].parts.length; ++j)
						max += parseFloat(extracts[i].parts[j].max_score);
					return max.toFixed(2);
				}
			return 0;
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
			if (rule.topics.length == 1) {
				var min = parseFloat(rule.expected)/parseFloat(rule.topics[0].coefficient);
				node.innerHTML = "Minimum "+min.toFixed(2)+"/"+getTopicMaxScore(rule.topics[0])+" in "+getTopicName(rule.topics[0]);
			} else {
				var s = "";
				for (var i = 0; i < rule.topics.length; ++i) {
					if (i > 0) s += " + ";
					s += getTopicName(rule.topics[i]);
					s += " (/"+getTopicMaxScore(rule.topics[i])+")";
					s += " * "+parseFloat(rule.topics[i].coefficient).toFixed(1);
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
					popup_frame(null,"Edit Eligibility Rule","/dynamic/selection/page/exam/eligibility_rule?id="+rule.id);
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
						service.json("selection","exam/remove_eligibility_rule",{id:rule.id},function(res){
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
					popup_frame(null,"New Eligibility Rule","/dynamic/selection/page/exam/eligibility_rule?parent="+rule.id);
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