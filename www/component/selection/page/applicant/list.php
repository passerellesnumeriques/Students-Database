<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_applicant_list extends SelectionPage {
	
	public function getRequiredRights() { return array("see_applicant_info"); }
	
	/**
	 * Create a data_list of applicants
	 * The property of datalist can be given by a post "input" variable. This variable can be set using prepare_applicants_list.js script
	 */
	public function executeSelectionPage() {
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("init_list();");
		$container_id = $this->generateID();
		$input = isset($_POST["input"]) ? json_decode($_POST["input"], true) : array();
		?>
		<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
			<div id='list_container' style="flex:1 1 auto"></div>
			<?php if (PNApplication::$instance->user_management->has_right("edit_applicants")) {?>
			<div class='page_footer' style="flex:none;">
				<span id='nb_selected'>0 applicant selected</span>: 
				<button class='action' id='button_assign_is' disabled='disabled' onclick='assignIS(this);'>Assign to Information Session</button>
				<button class='action' id='button_assign_exam_center' disabled='disabled' onclick='assignExamCenter(this);'>Assign to Exam Center</button>
				<button class='action red' id='button_exclude' disabled='disabled' onclick="excludeStudents();">Exclude from the process</button>
			</div>
			<?php } ?>
		</div>
		<script type='text/javascript'>
		var dl;
		var filters = <?php if (isset($input["filters"])) echo json_encode($input["filters"]); else echo "[]"; ?>;
		<?php
		if (isset($_GET["type"])) {
			switch ($_GET["type"]) {
			case "exam_passers":
				echo "filters.push({category:'Selection',name:'Eligible for Interview',force:true,data:{values:[1]}});\n";
				break;
			}
		}
		?>
		function init_list() {
			dl = new data_list(
				'list_container',
				'Applicant', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
				[
					'Selection.ID',
					'Personal Information.First Name',
					'Personal Information.Last Name',
					'Personal Information.Gender',
					'Personal Information.Birth Date',
					'Personal Information.Address.0',
					'Personal Information.Address.1',
					'Selection.Information Session',
					'Selection.Exam Center',
					'Selection.Interview Center'
				],
				filters,
				<?php echo isset($_GET["all"]) ? "-1" : "100"; ?>,
				'Personal Information.Last Name',
				true,
				function (list) {
					list.grid.makeScrollable();
					var get_creation_data = function() {
						var data = {
							sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>},
							prefilled_data:[]
						};
						var filters = list.getFilters();
						for (var i = 0; i < filters.length; ++i) {
							if (filters[i].category == "Selection") {
								if (filters[i].name == "Information Session") {
									if (filters[i].data.values.length == 1 && filters[i].data.values[0] != 'NULL' && filters[i].data.values != 'NOT_NULL')
										data.prefilled_data.push({table:"Applicant",data:"Information Session",value:filters[i].data.values[0]});
								}
							}
						}
						return data;
					};
					list.addTitle("/static/selection/applicant/applicants_16.png", <?php if (isset($input["title"])) echo json_encode($input["title"]); else echo "'Applicants'";?>);

					<?php if (PNApplication::$instance->user_management->has_right("edit_applicants")) {?>
					var create_applicant = document.createElement("BUTTON");
					create_applicant.className = "flat";
					create_applicant.innerHTML = "<img src='"+theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add)+"' style='vertical-align:bottom'/> Create Applicant";
					create_applicant.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('New Applicant', theme.build_icon("/static/selection/applicant/applicant_16.png",theme.icons_10.add), "");
							var frame = p.setContentFrame("/dynamic/people/page/popup_create_people?types=applicant&not_from_existing=true&ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(create_applicant);

					var import_applicants = document.createElement("BUTTON");
					import_applicants.className = "flat";
					import_applicants.innerHTML = "<img src='"+theme.icons_16._import+"' style='vertical-align:bottom'/> Import Applicants";
					import_applicants.onclick = function() {
						window.top.require("popup_window.js",function() {
							var p = new window.top.popup_window('Import Applicants', theme.icons_16._import, "");
							var frame = p.setContentFrame("/dynamic/selection/page/applicant/popup_import?ondone=reload_list", null, get_creation_data());
							frame.reload_list = reload_list;
							p.show();
						});
					};
					list.addHeader(import_applicants);

					list.grid.setSelectable(true);
					list.grid.onselect = selectionChanged;

					<?php } ?>

					var has_forced_filter = false;
					for (var i = 0; i < filters.length; ++i)
						if (filters[i].force) { has_forced_filter = true; break; }
						else {
							var or = filters[i].or;
							while (or) { if (or.force) { has_forced_filter = true; break; } or = or.or; }
							if (has_forced_filter) break;
						}
					if (!has_forced_filter) {
						var predefined_filters = document.createElement("BUTTON");
						predefined_filters.className = "flat";
						predefined_filters.innerHTML = "<img src='/static/data_model/filter.gif'/> Pre-defined filters";
						predefined_filters.onclick = function() {
							var t=this;
							require("context_menu.js",function() {
								var menu = new context_menu();
								menu.addIconItem(null, "All applicants (no filter)", function() {
									list.resetFilters();
									list.reloadData();
								});
								menu.addIconItem(null, "All not yet excluded", function() {
									list.resetFilters(false, [{category:'Selection',name:'Excluded',data:{values:[0]}}]);
									list.reloadData();
								});
								/* TODO 
								menu.addSubMenuItem(null, "Excluded because of", function(sub_menu, onready) {
									sub_menu.addIconItem(null, "Too old", function() {
										
									});
									onready();
								});
								*/
								menu.addSubMenuItem(null, "From Information Session", function(sub_menu, onready) {
									sub_menu.addIconItem(null, "Not assigned to any IS", function() {
										list.resetFilters(false, [{category:'Selection',name:'Information Session',data:{values:["NULL"]}}]);
										list.reloadData();
									});
									var f = list.getField("Selection", "Information Session");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.resetFilters(false, [{category:'Selection',name:'Information Session',data:{values:[val]}}]);
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addSubMenuItem(null, "From Exam Center", function(sub_menu, onready) {
									sub_menu.addIconItem(null, "Not assigned to any center", function() {
										list.resetFilters(false, [{category:'Selection',name:'Exam Center',data:{values:["NULL"]}}]);
										list.reloadData();
									});
									var f = list.getField("Selection", "Exam Center");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.resetFilters(false, [{category:'Selection',name:'Exam Center',data:{values:[val]}}]);
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addSubMenuItem(null, "From High School", function(sub_menu, onready) {
									sub_menu.addIconItem(null, "No high school specified", function() {
										list.resetFilters(false, [{category:'Selection',name:'High School',data:{values:["NULL"]}}]);
										list.reloadData();
									});
									var f = list.getField("Selection", "High School");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.resetFilters(false, [{category:'Selection',name:'High School',data:{values:[val]}}]);
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addSubMenuItem(null, "Followed by NGO", function(sub_menu, onready) {
									sub_menu.addIconItem(null, "Not followed by any NGO", function() {
										list.resetFilters(false, [{category:'Selection',name:'Following NGO',data:{values:["NULL"]}}]);
										list.reloadData();
									});
									var f = list.getField("Selection", "Following NGO");
									for (var i = 0; i < f.filter_config.possible_values.length; ++i) {
										var val = f.filter_config.possible_values[i];
										sub_menu.addIconItem(null, val[1], function(ev, val) {
											list.resetFilters(false, [{category:'Selection',name:'Following NGO',data:{values:[val]}}]);
											list.reloadData();
										}, val[0]);
									}
									onready();
								});
								menu.addIconItem(null, "Eligible for Interview (exam passers)", function() {
									list.resetFilters(false, [{category:'Selection',name:'Eligible for Interview',data:{values:[1]}}]);
									list.reloadData();
								});
								menu.addIconItem(null, "Absent during exam", function() {
									list.resetFilters(false, [{category:'Selection',name:'Exam Attendance',data:{values:['No']}}]);
									list.reloadData();
								});
								// TODO others...
								menu.showBelowElement(t);
							});
						};
						list.addHeader(predefined_filters);
					}

					list.makeRowsClickable(function(row){
						window.top.popup_frame('/static/selection/applicant/applicant_16.png', 'Applicant', "/dynamic/people/page/profile?people="+list.getTableKeyForRow("People",row.row_id), {sub_models:{SelectionCampaign:<?php echo PNApplication::$instance->selection->getCampaignId();?>}}, 95, 95); 
					});
				}
			);
		}
		function reload_list() {
			dl.reloadData();
		};
		function selectionChanged(indexes, rows_ids) {
			document.getElementById('nb_selected').innerHTML = indexes.length+" applicant"+(indexes.length>1?"s":"")+" selected";
			document.getElementById('button_assign_is').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_assign_exam_center').disabled = indexes.length > 0 ? "" : "disabled";
			document.getElementById('button_exclude').disabled = indexes.length > 0 ? "" : "disabled";
		}
		function assignIS(button) {
			require("assign_is.js", function() {
				var applicants_rows = dl.grid.getSelectionByRowId();
				var applicants_ids = [];
				for (var i = 0; i < applicants_rows.length; ++i)
					applicants_ids.push(dl.getTableKeyForRow("Applicant", applicants_rows[i]));
				assign_is(button, applicants_ids, function() {
					reload_list();
				});
			});
		}
		function assignExamCenter(button) {
			var applicants_rows = dl.grid.getSelectionByRowId();
			var applicants_ids = [];
			for (var i = 0; i < applicants_rows.length; ++i)
				applicants_ids.push(dl.getTableKeyForRow("Applicant", applicants_rows[i]));
			popup_frame("/static/selection/exam/exam_center_16.png", "Assign applicants to an exam center", "/dynamic/selection/page/exam/assign_applicants_to_center?ondone=refreshList", {applicants:applicants_ids}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		function excludeStudents() {
			var applicants_rows = dl.grid.getSelectionByRowId();
			var applicants_ids = [];
			for (var i = 0; i < applicants_rows.length; ++i)
				applicants_ids.push(dl.getTableKeyForRow("Applicant", applicants_rows[i]));
			popup_frame(null, "Exclude applicants from the selection process", "/dynamic/selection/page/applicant/exclude?ondone=refreshList", {applicants:applicants_ids}, null, null, function(frame,popup) {
				frame.refreshList = reload_list;
			});
		}
		</script>
		<?php 
	}
}
?>