<?php
require_once("component/selection/page/SelectionPage.inc"); 
class page_trip_list extends SelectionPage {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function executeSelectionPage() {
		$trips = SQLQuery::create()->select("Trip")->execute();
		
		theme::css($this,"grid.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none'>
		<img src='/static/selection/trip/bus_32.png'/>
		Selection Trips
	</div>
	<div style='flex:1 1 auto;background-color:white;overflow:auto;'>
		<?php if (count($trips) == 0) {?>
		<div style='padding:10px;font-style:italic;'>
			There is no trip planned yet.
		</div>
		<?php } else { ?>
		<table class='grid'>
			<thead>
				<tr>
					<th>Trip</th>
					<th>When</th>
					<th>Who</th>
					<th>Where</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($trips as $trip) {
					echo "<tr>";
					echo "<td>";
					echo toHTML($trip["name"]);
					echo "</td>";
					echo "</tr>";
				} 
				?>
			</tbody>
		</table>
		<?php } ?>
	</div>
	<?php if (PNApplication::$instance->user_management->has_right("manage_trips")) { ?>
	<div class='page_footer' style='flex:none;'>
		<button class='action green' onclick='newTrip();'>
			<img src='<?php echo theme::make_icon("/static/selection/trip/bus_black_16.png",theme::$icons_10["add"]);?>'/>
			Create New Trip
		</button>
	</div>
	<?php } ?>
</div>
<script type='text/javascript'>
function newTrip() {
	window.top.popup_frame("/static/selection/trip/bus_black_16.png", "Plan New Trip", "/dynamic/selection/page/trip/trip", null, 90, 90, function(frame,popup) {
		popup.onclose = function() { location.reload(); };
	});
}
</script>
<?php 
	}
	
}
?>