<?php 
require_once("component/selection/page/SelectionPage.inc");
class page_is_main_page extends SelectionPage {
	public function getRequiredRights() { return array("see_information_session"); }
	public function executeSelectionPage(){
		$this->addJavascript("/static/widgets/grid/grid.js");
		$this->addJavascript("/static/data_model/data_list.js");
		$this->onload("initISList();");
		$can_create_session = PNApplication::$instance->user_management->has_right("manage_information_session",true);
		$this->requireJavascript("section.js");
		$this->onload("sectionFromHTML('status_section');");
		$this->onload("loadISStatus();");
		?>
		<div style="display:flex;flex-direction:row;">
			<div style="padding:5px;padding-right:0px;display:inline-block;flex:none;">
				<div id='status_section' title='Status' collapsable='false' css='soft' style='display:inline-block;'>
					<div id='is_status' class='selection_status'></div>
				</div>
				<div style='margin-top:10px'>
					<button class='action' onclick="window.top.popupFrame('/static/contact/address_16.png','Map','/dynamic/selection/page/map?type=is',null,95,95);"><img src='/static/contact/address_16.png'/> Open Map</button>
				</div>
			</div>
			<div style="padding: 5px;display:inline-block;flex:1 1 auto;">
				<div id='is_list' class="section soft">
				</div>
			</div>
		</div>
		
		<script type='text/javascript'>
		var is_list;
			function initISList() {
				is_list = new data_list(
					'is_list',
					'InformationSession', <?php echo PNApplication::$instance->selection->getCampaignId();?>,
					[
						'Information Session.Name',
						'Information Session.Date',
						'Information Session.Hosting Partner',
						'Information Session.Expected',
						'Information Session.Attendees',
						'Information Session.Applicants'
					],
					[],
					-1,
					null, null,
					function (list) {
						list.addTitle("/static/selection/is/is_16.png", "Information Sessions");
						// by default, order the sessions by date
						list.orderBy("Information Session", "Date", -1, true);
						<?php if ($can_create_session) { ?>
						var new_IS = document.createElement("BUTTON");
						new_IS.className = 'flat';
						new_IS.innerHTML = "<img src='"+theme.build_icon("/static/selection/is/is_16.png",theme.icons_10.add)+"'/> New Information Session";
						new_IS.onclick = newIS;
						list.addHeader(new_IS);
						<?php } ?>

						list.makeRowsClickable(function(row){
							var is_id = list.getTableKeyForRow('InformationSession',row.row_id);
							window.top.popupFrame(
								"/static/selection/is/is_16.png",
								"Information Session",
								"/dynamic/selection/page/is/profile?id="+is_id+"&onsaved=saved",
								null,
								95, 95,
								function(frame, pop) { frame.saved = function() { ISchanged(); }; }
							);
						});
					}
				);
			}
			function newIS() {
				require("popup_window.js",function() {
					var popup = new popup_window("Information Session", "/static/selection/is/is_16.png", "");
					var frame = popup.setContentFrame("/dynamic/selection/page/is/profile?onsaved=saved");
					frame.saved = function() { ISchanged(); };
					popup.showPercent(95,95);
				});
			}
			function ISchanged() {
				refreshPage();
			}
			function loadISStatus() {
				var container = document.getElementById('is_status');
				container.innerHTML = "<center><img src='"+theme.icons_16.loading+"'/></center>";
				service.html("selection","is/status",null,container);
			}
			function refreshPage() {
				is_list.reloadData();
				loadISStatus();
				layout.changed(document.body);
			}
		</script>
	
	<?php		
	}
	
}