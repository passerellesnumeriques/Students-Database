<?php 
function geographic_area_selection(&$page) {
	$div_id = $page->generate_id();
	echo "<div style='width:100%;height:100%' id='".$div_id."'>";
	echo "</div>";
}

class page_geography extends Page {
	
	public function get_required_rights() { return array(); }
	
	public function execute() {
		$this->add_javascript("/static/javascript/utils.js");
		//$this->add_javascript("/static/data_model/data_list.js");
		$this->onload("init_geography();");
?>
<div style='width:100%;height:100%' id='geography'>
</div>

<?php
$get_country_data = new service_get_country_data();
?>
<script type='text/javascript'>

function init_geography(){
	var main = document.getElementById("geography");
	var domain =<?php if(isset($_GET["domain"])){echo $_GET["domain"];}else echo null;?> ;
	var country_geography=[];
	if(typeof(domain)!='undefined'){
		<?php $country_divisions = $get_country_data->execute();
			$i=0;
			$j=0;
			$areas=[];
			$divisions=[];
			foreach($country_divisions as $c){
				
				
				$areas[$j]["division_id"]=json_encode($c["country_division_id"]);
				$areas[$j]
				if(!array_key_exists($c["country_division_id"],$divisions)){
					$divisions[$c["country_division_id"]]=$i;
					$areas[$j][0]=

					
						$areas[$c["geographic_area_id"]]=$j;
						echo "area_id:".json_encode($c["geographic_area_id"]).", ";
						echo "area_name:".json_encode($c["geographic_area_name"]).", ";
						echo "area_parent_id:".json_encode($c["geographic_area_parent_id"])."}";
						$j++;
					
					echo "country_geography[".$i."]={";
					echo "division_id:".json_encode($c["country_division_id"]).", ";
					echo "division_name:".json_encode($c["country_division_name"]).", ";
					echo "division_parent_id:".json_encode($c["country_division_parent_id"]).", ";
					echo "areas:[";
					$i++;
				}
				
				
			}
			// foreach($country_divisions as $c){
				// echo "country_geography[".$i."]={";
				
				// echo "country_division_id:".json_encode($c["country_division_id"]).", ";
				
				// echo "\"country_division_id\":\"".$c["country_division_id"]."\", ";
				// echo "\"country_division_parent_id\":\"".$c["country_division_parent_id"]."\", ";
				// echo "\"country_division_name\":\"".$c["country_division_name"]."\", ";
				// echo "\"geographic_area_id\":\"".$c["geographic_area_id"]."\", ";
				// echo "\"geographic_area_name\":\"".$c["geographic_area_name"]."\", ";
				// echo "\"geographic_area_parent_id\":\"".$c["geographic_area_parent_id"]."\"}";
				// $i++;
			// }
		?>
		//We collect the country's structure
		

		function sort_divisions(){
			var country_divisions=[];
			var country_divisions_sorted={};
			for(var i=0; i<country_geography.length; i++){
				if(country_divisions.contains(country_geography[i]["country_division_id"])){
					continue;
				}
				else{
					//we add the division to the list, indexed by their id
					var id = country_geography[i]["country_division_id"];
					country_divisions[id][0]=id;
					country_divisions[id][1]=country_geography[i]["country_division_name"];
					country_divisions[id][2]=country_geography[i]["country_division_parent_id"];
					continue;
				}
			}
			//We sort the country's divisions

			var root_division=[];
			//We get the root
			for each(division in country_divisions){
				if(typeof(division[1])=='undefined'||division[1]==null){
					root_division[0]=division[0];
					root_division[1]=division[1];
					root_division[2]=division[2];
					break;
				}
			}
			country_divisions_sorted[0][0]=root_division[0];
			country_divisions_sorted[0][1]=root_division[1];
			country_divisions_sorted[0][2]=root_division[2];
			//We get the other divisions
			var j=0;
			while(country_divisions_sorted.length != country_divisions.length){
				for each(division in country_divisions){
					if(division[2]==country_division_sorted[j][0]){
						country_division_sorted[j+1][0]=division[0];
						country_division_sorted[j+1][1]=division[1];
						country_division_sorted[j+1][2]=division[2];
						j++;
					}
				}
			}
			return country_divisions_sorted;
		}
		var country_divisions_sorted = sort_divisions();
		
		//We create the table
		function create_divisions_table(list_sorted){
			var table = document.createElement('table');
			table.style.width='100%';
			var tbody = document.createElement('tbody');
			for (var i=0; i<list_sorted.length; i++){
				var tr=document.createElement('tr');
				
			}
		}
		
		

	}
}
</script>