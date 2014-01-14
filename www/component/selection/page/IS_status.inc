<?php
function getFigure ($db_array){
	$total = 0;
	if(isset($db_array[0])){
		$total = 0;
		foreach($db_array as $value){
			$fig = intval($value);
			$total = $total + $fig;
		}
	}
	return $total;
}

function IS_status(&$page, $container, $custom_title = null, $custom_header_color = null){
	$campaign_id = PNApplication::$instance->selection->get_campaign_id();
	$config = json_encode(PNApplication::$instance->selection->get_config());
	$boys_real = null;
	$boys_expected = null;
	$girls_real = null;
	$girls_expected = null;
	$partners = null;
	$campaign_name = null;
	$id = $page->generate_id();
	
	$all_boys_real = SQLQuery::create()
				->select("Information_session")
				->field("number_boys_real")
				->execute_single_field();
	
	$boys_real = getFigure($all_boys_real);
	
	$all_boys_expected = SQLQuery::create()
				->select("Information_session")
				->field("number_boys_expected")
				->execute_single_field();
	
	$boys_expected = getFigure($all_boys_expected);
	
	$all_girls_real = SQLQuery::create()
				->select("Information_session")
				->field("number_girls_real")
				->execute_single_field();
	
	$girls_real = getFigure($all_girls_real);
	
	$all_girls_expected = SQLQuery::create()
				->select("Information_session")
				->field("number_girls_expected")
				->execute_single_field();
				
	$girls_expected = getFigure($all_girls_expected);
				
	$partners = SQLQuery::create()
				->select("Information_session_partner")
				->count()
				->execute_single_value();
				
	$IS = SQLQuery::create()
				->select("Information_session")
				->count()
				->execute_single_value();
				
	$campaign_name = SQLQuery::create()
				->select("SelectionCampaign")
				->field("name")
				->where_value("SelectionCampaign","id",$campaign_id)
				->execute_single_value();
	if($custom_title <> null)
		if($custom_header_color <> null)
			$page->onload("IS_status_".$id."(".json_encode($container).", ".json_encode($campaign_name).", ".$config.", ".json_encode($custom_title).", ".json_encode($custom_header_color).");");
		else
			$page->onload("IS_status_".$id."(".json_encode($container).", ".json_encode($campaign_name).", ".$config.", ".json_encode($custom_title).", ".json_encode(false).");");
	else
		if($custom_header_color <> null)
			$page->onload("IS_status_".$id."(".json_encode($container).", ".json_encode($campaign_name).", ".$config.", ".json_encode(false).", ".json_encode($custom_header_color).");");
		else
			$page->onload("IS_status_".$id."(".json_encode($container).", ".json_encode($campaign_name).", ".$config.", ".json_encode(false).", ".json_encode(false).");");
?>
	<script type = 'text/javascript'>
	function IS_status_<?php echo $id; ?>(container, campaign_name, config, custom_title, custom_color){
		var t = this;
		var separate_boys_girls = config[findIndexInConfig(config, "separate_boys_girls_IS")].value;
		var display_number_partners = config[findIndexInConfig(config, "display_number_partners_IS_status")].value;
		if(typeof(container) == "string")
			container = document.getElementById(container);
		t.girls_real = <?php echo json_encode($girls_real); ?>;
		t.boys_real = <?php echo json_encode($boys_real); ?>;
		t.girls_expected = <?php echo json_encode($girls_expected); ?>;
		t.boys_expected = <?php echo json_encode($boys_expected); ?>;
		t.partners = <?php echo json_encode($partners); ?>;
		t.IS = <?php echo json_encode($IS); ?>;
		
		t.table = document.createElement("table");
		
		t._init = function(){
			t._setTableHeader();
			t._setTableBody();
			t._setTableStyle();
		}
		
		t._setTableHeader = function(){
			var tr = document.createElement("tr");
			t.th_head = document.createElement("th");
			t.th_head .colSpan = 4;
			if(custom_title)
				t.th_head .innerHTML = custom_title;
			else
				t.th_head .innerHTML = "<img src = '/static/selection/IS_16.png' /> Information Sessions status for "+campaign_name+" campaign";
			
			tr.appendChild(t.th_head );
			t.table.appendChild(tr);
			container.appendChild(t.table);
		}
		
		t._setTableBody = function(){
			var tr1 = document.createElement("tr");
			var tr2 = document.createElement("tr");
			var tr3 = document.createElement("tr");
			var td12 = document.createElement("td");
			var td11 = document.createElement("td");
			
			td11.innerHTML = "<font color='#808080'><b>Planned / Conducted:</b></font>";
			td11.style.textAlign = "center";
			td11.style.paddingBottom = "10px";
			td12.innerHTML = t.IS;
			td12.style.paddingBottom = "10px";
			// td12.style.paddingRight = "15px";
			// td12.style.paddingLeft = "10px";
			td12.style.textAlign = "left";
			tr1.appendChild(td11);
			tr1.appendChild(td12);
			if(display_number_partners){
				var td13 = document.createElement("td");
				var td14 = document.createElement("td");
				td13.innerHTML = "<font color='#808080'><b>All partners:</b></font>";
				td13.style.paddingBottom = "10px";
				td13.style.textAlign = "center";
				td14.innerHTML = t.partners;
				td14.style.paddingBottom = "10px";
				tr1.appendChild(td13);
				tr1.appendChild(td14);
			} else {
				td12.colSpan = 2;
				td11.colSpan = 2;
			}
			t.table.appendChild(tr1);
			
			var td21 = document.createElement("td");
			var td22 = document.createElement("td");
			var td23 = document.createElement("td");
			var td24 = document.createElement("td");
			
			if(separate_boys_girls){
				td21.innerHTML = "<font color='#808080'><b>Girls expected:</b></font>";
				td21.style.textAlign = "center";
				td22.innerHTML = t.girls_expected;
				td23.innerHTML = "<font color='#808080'><b>Girls real:</b></font>";
				td23.style.textAlign = "center";
				td24.innerHTML = t.girls_real;
				tr2.appendChild(td21);
				tr2.appendChild(td22);
				tr2.appendChild(td23);
				tr2.appendChild(td24);
				
				var td31 = document.createElement("td");
				var td32 = document.createElement("td");
				var td33 = document.createElement("td");
				var td34 = document.createElement("td");
				td31.innerHTML = "<font color='#808080'><b>Boys expected:</b></font>";
				td31.style.textAlign = "center";
				td32.innerHTML = t.boys_expected;
				td33.innerHTML = "<font color='#808080'><b>Boys real:</b></font>";
				td33.style.textAlign = "center";
				td34.innerHTML = t.boys_real;
				tr3.appendChild(td31);
				tr3.appendChild(td32);
				tr3.appendChild(td33);
				tr3.appendChild(td34);
				
				t.table.appendChild(tr2);
				t.table.appendChild(tr3);
			} else {
				// /* in that case, all the data are stored into the boys figures fields */
				td21.innerHTML = "<font color='#808080'><b>Attendees expected:</b></font>";
				td21.style.textAlign = "center";
				td22.innerHTML = t.boys_expected;
				td23.innerHTML = "<font color='#808080'><b>Attendees real:</b></font>";
				td23.style.textAlign = "center";
				td24.innerHTML = t.boys_real;
				tr2.appendChild(td21);
				tr2.appendChild(td22);
				tr2.appendChild(td23);
				tr2.appendChild(td24);
				t.table.appendChild(tr2);
			}
		}
		
		t._setTableStyle = function(){
			if(custom_color)
				setCommonStyleTable(t.table,t.th_head,custom_color);
			else
				setCommonStyleTable(t.table,t.th_head,"#AF7CBF");
		}
		
		t._init();
	}
	
	</script>

<?php
	
	
}




?>