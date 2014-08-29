<?php
require_once("SelectionPage.inc"); 
class page_map extends SelectionPage {
	
	public function getRequiredRights() { return array("can_access_selection_data"); }
	
	public function executeSelectionPage() {
		$type = @$_GET["type"];
		$markers = array();
		if ($type == "is" || $type == null) {
			$q = SQLQuery::create()
				->select("InformationSession")
				->join("InformationSession", "InformationSessionPartner", array("id"=>"information_session",null=>array("host"=>true)))
				;
			PNApplication::$instance->contact->joinPostalAddress($q, "InformationSessionPartner", "host_address");
			$q->whereNotNull("PostalAddress","geographic_area");
			PNApplication::$instance->geography->joinGeographicArea($q, "PostalAddress", "geographic_area");
			$q->field("InformationSession","name","name");
			$q->field("InformationSession","number_boys_real","nb_boys");
			$q->field("InformationSession","number_girls_real","nb_girls");
			$q->field("PostalAddress", "lat", "lat");
			$q->field("PostalAddress", "lng", "lng");
			$q->field("GeographicArea", "north", "north");
			$q->field("GeographicArea", "south", "south");
			$q->field("GeographicArea", "west", "west");
			$q->field("GeographicArea", "east", "east");
			$q->join("InformationSession","Applicant",array("id"=>"information_session"));
			$q->groupBy("InformationSession","id");
			$q->countOneField("Applicant", "information_session", "nb_applicants");
			$sessions = $q->execute();
			foreach ($sessions as $is) {
				$marker = array();
				if ($is["lat"] <> null) {
					$marker["lat"] = floatval($is["lat"]);
					$marker["lng"] = floatval($is["lng"]);
				} else {
					$marker["lat"] = floatval($is["south"])+(floatval($is["north"])-floatval($is["south"]))/2;
					$marker["lng"] = floatval($is["west"])+(floatval($is["east"])-floatval($is["west"]))/2;
				}
				$marker["name"] = $is["name"];
				$marker["color"] = "#FFA0A0";
				$marker["text"] = (intval($is["nb_boys"])+intval($is["nb_girls"]))." attended, ".$is["nb_applicants"]." applicants";
				array_push($markers, $marker);
			}
		}
?>
<div style='width:100%;height:100%' id='map_container'>
</div>
<script type='text/javascript'>
setTimeout(function() {
	window.top.google.loadGoogleMap(document.getElementById('map_container'),function(map){
		// init to country
		window.top.geography.getCountry(window.top.default_country_id,function(country) {
			var content;
			<?php
			foreach ($markers as $marker) {
				//echo "map.addMarker(".$marker["lat"].", ".$marker["lng"].", 1, ".json_encode($marker["name"]).");\n";
				
				//echo "icon={scale:10,strokeWeight:3};";
				//echo "icon.path = window.top.google.maps.SymbolPath.CIRCLE;"; 
				//echo "map.addShape(new window.top.google.maps.Marker({position: new window.top.google.maps.LatLng(".$marker["lat"].", ".$marker["lng"]."),icon:icon}));";

				//echo "content=document.createElement('DIV');\n";
				//echo "content.style.backgroundColor = '".$marker["color"]."';\n";
				//echo "content.innerHTML=".json_encode($marker["name"])."+'<br/>'+".json_encode($marker["text"]).";\n";			
				//echo "map.addShape(new window.top.google.maps.InfoWindow({position:new window.top.google.maps.LatLng(".$marker["lat"].", ".$marker["lng"]."),content:content}));\n";
				
				echo "content=document.createElement('DIV');\n";
				echo "content.style.padding = '1px';\n";
				echo "content.style.fontSize = '8pt';\n";
				echo "content.innerHTML='<b>'+".json_encode($marker["name"])."+'</b><br/>'+".json_encode($marker["text"]).";\n";
				echo "map.addShape(new PNMapMarker(".$marker["lat"].", ".$marker["lng"].",".json_encode($marker["color"]).",content));\n";
			}
			?>
			if (country.north) {
				map.onNextIdle(function() {
					map.fitToShapes();
				});
				map.fitToBounds(country.south, country.west, country.north, country.east);
			}
		});
	});
},100);
</script>
<?php 
	}
	
}
?>