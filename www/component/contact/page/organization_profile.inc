<?php
/**
 * The rights of the user is taken into account to set this page
 * This page needs no service to be generated
 * @parameter id the organization id
 * @parameter container_id
 * @param title_container_id
 */
function organization_profile(&$page, $id, $container_id, $title_container_id){
	/* Get the user rights */
	require_once("component/data_model/Model.inc");
	$rights = array();
	$rights['remove'] = DataModel::get()->getTable("Organization")->canRemove();
	$rights['edit'] = DataModel::get()->getTable("Organization")->canModifyField('name');
	$rights['add'] = DataModel::get()->getTable("Organization")->canAdd();
		//TODO set the rights (maybe different from one table to an other)
		
	/* Get the organisation infos*/
	if(isset($id)){
		$q_orga_infos = SQLQuery::create()->select("Organization")
					->field("name")
					->field("id")
					->where("Organization.id = '".SQLQuery::escape($id)."'");
		$orga_infos = $q_orga_infos->execute();
		$q_types = SQLQuery::create()->select("Organization_types")
					->field("Organization_type","name","type_name")
					->field("Organization_type","id","type_id")
					->join("Organization_types","Organization_type",array("type"=>"id"))
					->where("Organization_types.organization = '".SQLQuery::escape($id)."'");
		$types = $q_types->execute();
		
		$q_contact_points = SQLQuery::create()->select("Contact_point")
					->field("Contact_point","people","people_id")
					->field("Contact_point","designation","designation")
					->field("People","last_name","last_name")
					->field("People","first_name","first_name")
					->join("Contact_point","People",array("people"=>"id"))
					->where("Contact_point.organization ='".SQLQuery::escape($id)."'");
		$contact_points = $q_contact_points->execute();
		
	/* Get all the existing organization types */
		$q_all_types = SQLQuery::create()->select("Organization_type")
					->field("id")
					->field("name");
		$all_types = $q_all_types->execute();
	}
	
	?>
<script type='text/javascript'>
	/**
	 * Generate the array containing all the existing organization types: all_types = [{id:, name:}]
	 */
	var all_types = <?php if(!isset($all_types[0]["id"])) echo "[];";
		else{
			echo "[";
			$first = true;
			foreach($all_types as $t){
				if(!$first) echo ", ";
				echo "{id:".json_encode($t["id"]).", ";
				echo "name:".json_encode($t["name"])."}";
				$first = false;
			}
			echo "];";
		}
	?>

	/**
	 * Generate the s object describing the organization s = {id:, name:, types:[{id:,name:}], points:[{people_id:,first_name:,last_name:,designation:}]}
	 */

	var editable = null;
	var context = null;
	var rights = {};
	rights.remove = <?php echo json_encode($rights['remove']).";";?>
	rights.edit = <?php echo json_encode($rights['edit']).";";?>
	rights.add = <?php echo json_encode($rights['add']).";";?>
	
	// rights.remove = false;
	// rights.edit = false;
	// rights.add = false;
	
	var s = <?php
		if(!isset($orga_infos[0]["id"])) echo "null;";
		else{
			echo "{id:".json_encode($orga_infos[0]["id"]).",name:".json_encode($orga_infos[0]["name"]).",types:";
			if(!isset($types[0]["type_id"])) echo "[]";
			else{
				echo "[";
				$first = true;
				foreach($types as $type){
					if(!$first) echo ", ";
					$first = false;
					echo "{type_id:".json_encode($type["type_id"]);
					echo ",type_name:".json_encode($type["type_name"]);
					echo "}";
				}
				echo "]";
			}
			echo ", points:";
			if(!isset($contact_points[0]["people_id"])) echo "[]";
			else{
				echo "[";
				$first = true;
				foreach($contact_points as $contact_point){
					if(!$first) echo ", ";
					$first = false;
					echo "{people_id:".json_encode($contact_point["people_id"]).", ";
					echo "first_name:".json_encode($contact_point["first_name"]).", ";
					echo "last_name:".json_encode($contact_point["last_name"]).", ";
					echo "designation:".json_encode($contact_point["designation"])."}";
				}
				echo "]";
			}
			echo "};";
		}
	?>
	
	var everything_ready = function(){
		s.init();
	}
	var container = document.getElementById('<?php echo $container_id;?>');
	var title_container = document.getElementById('<?php echo $title_container_id;?>');
	if(s == null) {
		title_container.innerHTML = "This organization does not exist in the database";
		title_container.style.fontStyle = "italic";
	}
	else {
		if(editable != null && context != null) everything_ready();
	}
	require("editable_cell.js",function(){
		editable = "ok";
		if(s != null && context != null) everything_ready();
	});
	require("context_menu.js",function(){
		context = "ok";
		if(s != null && editable != null) everything_ready();
	});
	
	/**
	 * @method init: starts creating the contact points and types tables
	 */
	s.init = function () {
		var header = document.createElement("div");
		var types = document.createElement("span");
		var contact_points = document.createElement("span");
		s.header = header;
		s.span_types = types;
		s.span_points = contact_points;
		
		/* The distinction between having rights or not will be done inside these methods */
		s.setTable("type");
		s.setTable("point");
	
		/* No need to handle rights for addresses and contacts since it is done by the contact.inc and addresses.inc pages */
		s.setHeaderOrganization();
		s.finish();
	}
	
	/**
	 * @method setHeaderOrganization
	 * Create the header containing the name of the organization. If rights.editable == true, will create an editable cell
	 */
	s.setHeaderOrganization = function(){
		/* Adding and removing rights have no sense here */
		var div = document.createElement("div");
		if(rights.edit){
			var style = {};
			style.fontSize = "x-large";
			var edit = new editable_cell(div,"Organization","name",s.id,"field_text",null,s.name,style);
			edit.onsave = function(new_data){
				if(new_data.checkVisible()){
					/* Update s */
					s.name = new_data.uniformFirstLetterCapitalized();
					return new_data.uniformFirstLetterCapitalized();
				}
				else return this.data;
			};
		} else div.innerHTML = s.name;
		s.header.appendChild(div);
	}
	
	/**
	 * @method setTable
	 * @parameter name {string} can be 'point' or 'type'
	 * Creates the tables for types and contact points and starts the methods to set them
	 */
	s.setTable = function(name){
		var table = document.createElement('table');
		var thead = document.createElement('thead');
		var tbody = document.createElement('tbody');
		var tfoot = document.createElement('tfoot');
		/* Stock the tbody element in the s object */
		if(name == 'type'){
			s.tbody_types = tbody;
			for(var i = 0; i < s.types.length; i++){
				if(s.types[i] != null) s.addTypeRow(i);
			}
			if(rights.add) s.addAddButton(tfoot,"type");
			s.setHeader(thead,"Types",1);
		}
		if(name == 'point'){
			s.tbody_points = tbody;
			for(var i = 0; i < s.points.length; i++){
				if(s.points[i] != null) s.addPointRow(i);
			}
			if(rights.add) s.addAddButton(tfoot,"point");
			s.setHeader(thead,"Contact Points",2);
		}
		
		
		table.appendChild(thead);
		if(name == "type") table.appendChild(s.tbody_types);
		if(name == "point") table.appendChild(s.tbody_points);
		table.appendChild(tfoot);
		if(name == "type") s.span_types.appendChild(table);
		if(name == "point") s.span_points.appendChild(table);
	}
	
	/**
	 * @method setHeader
	 * Set the header of the given table
	 * @param thead the table header to set
	 * @param {string} name the value to display
	 * @param {integer} th_number the number of the th that will contain the header
	 */
	s.setHeader = function(thead, name,th_number){
		var tr = document.createElement("tr");
		var th1 = document.createElement("th");
		var th2 = document.createElement("th");
		if(th_number == 1) {
			th1.innerHTML = name;
		}
		if(th_number == 2) th2.innerHTML = name;
		tr.appendChild(th1);
		tr.appendChild(th2);
		thead.appendChild(tr);
	}
	
	/**
	 * @method addAddButton
	 * Create the add button at the bottom of the tables
	 * @parameter cont the container of the button
	 * @parameter name {string} can be 'type' or 'point'
	 */
	s.addAddButton = function(cont,name){
		var tr = document.createElement('tr');
		var td1 = document.createElement('td');
		var td2 = document.createElement('td');
		var div = document.createElement('div');
		div.innerHTML = "Add a " + name;
		div.style.fontStyle = "italic";
		div.style.color = "#808080";
		div.style.cursor = 'pointer';
		if(name.toLowerCase() == "type"){
			div.onclick = function(){s.contextAddType(div);};
			td1.appendChild(div);
		}
		if(name.toLowerCase() == "point"){
			div.onclick = function(){
			//TODO
			};
			td2.appendChild(div);
		}
		tr.appendChild(td1);
		tr.appendChild(td2);
		cont.appendChild(tr);
	}
	
	/**
	 * @method contextAddType
	 * Creates the context_menu displayed on clicking on the addtype button
	 * @parameter cont the container of the addtype button
	 */
	s.contextAddType = function(cont){
		//require("context_menu.js",function(){
			if(!s.context){
				s.context = new context_menu();
				//s.context.onclose = function(){s.context. = null;};
			}
			s.context.clearItems();
			for (var i = 0; i < all_types.length; i++){
				s.setContext(all_types[i].id, all_types[i].name);
			}
			s.context.showBelowElement(cont);
		//});
	}
	
	/**
	 * @method setContext
	 * Add an item to the context_menu of the addtype button
	 * @parameter id the id of the type
	 * @parameter name the type name
	 */
	s.setContext = function(id, name){
		var item = document.createElement('div');
		item.onmouseover = function(){this.style.backgroundColor ="#808080";};
		item.onmouseout = function(){this.style.backgroundColor ="";};
		item.style.cursor = "pointer";
		item.innerHTML = name.uniformFirstLetterCapitalized();
		item.onclick = function(){s.addType(id);};
		s.context.addItem(item);
	}
	
	/**
	 * @method #all_types#findTypeIndex
	 * Find the index of a type id in the all_types object
	 * @parameter id
	 */
	all_types.findTypeIndex = function(id){
		var index = null;
		for(var i = 0; i < all_types.length; i++){
			if(all_types[i].id == id){
				index = i;
				break;
			}
		}
		return index;
	}
	
	/**
	 * @method findTypeIndex
	 * Find the index of a type id in the s object
	 * @parameter id
	 */
	s.findTypeIndex = function(id){
		var index = null;
		for(var i = 0; i < s.types.length; i++){
			if(s.types[i] != null && s.types[i].type_id == id){
				index = i;
				break;
			}
		}
		return index;
	}
	
	/**
	 * @method addType
	 * Method called by clicking on one item of the addType context_menu
	 * Will add the type in the database, the s object, and the table displayed
	 * @parameter id
	 */
	s.addType = function(id){
		/* Check this type is not already set for this organization */
		if(s.findTypeIndex(id) != null){
			error_dialog("This type is already set for " + s.name);
			return;
		}
		/* Create in the database */
		service.json("data_model","save_entity",{table:"Organization_types", field_organization:s.id, field_type:id},function(res){
			if(!res) return;
			/* Update the s object */
			var l = s.types.length;
			var name_index = all_types.findTypeIndex(id);
			s.types[l] = {};
			s.types[l].type_id = id;
			s.types[l].type_name = all_types[name_index].name;
			/* Update the table */
			s.addTypeRow(l);
		});
	}
	
	/**
	 * @method addTypeRow
	 * Creates a type row and displays it
	 * If rights.remove this method will call the addRemoveButton method
	 * @parameter type_index the index of the type in the s object
	 */
	s.addTypeRow = function (type_index){
		var tr = document.createElement('tr');
		var td1 = document.createElement('td');
		var td2 = document.createElement('td');
		var div = document.createElement('div');
		div.innerHTML = s.types[type_index].type_name;
		td1.appendChild(div);
		if(rights.remove) s.addRemoveButton(td2, type_index, "type");
		tr.appendChild(td1);
		tr.appendChild(td2);
		s.types[type_index].tr = tr;
		s.tbody_types.appendChild(tr);
	}
	
	/**
	 * @method addRemoveButton
	 * Creates the remove button
	 * Will call the removeType or removePoint method
	 * @parameter td the container of the remove button
	 * @parameter index the index in the s object of the attribute to remove
	 * @parameter name {string} can be 'point' or 'type'
	 */
	s.addRemoveButton = function(td, index, name){
		var remove_button = document.createElement('img');
		remove_button.src = theme.icons_16.remove;
		remove_button.onmouseover = function(e){this.src = theme.icons_16.remove_black; stopEventPropagation(e);};
		remove_button.onmouseout = function(e){this.src = theme.icons_16.remove; stopEventPropagation(e);};
		remove_button.style.cursor = 'pointer';
		remove_button.style.verticalAlign = 'bottom';
		remove_button.onclick = function(){
			if(name == "type"){
				confirm_dialog("Are you sure you want to remove this type?", function(text){if(text) s.removeType(index);});
			}
			if(name == "point"){
				confirm_dialog("Are you sure you want to remove this contact point?", function(text){if(text) s.removePoint(index);});
			}
		};
		td.appendChild(remove_button);
	}
	
	/**
	 * @method removeType
	 * Remove a type from the database, the table, and the s object
	 * @parameter type_index the index in the s object of the type to remove
	 */
	s.removeType = function(type_index){
		/* Remove from database */
		service.json("data_model","remove_row_with_no_primary",{table:"Organization_types",fields:[{"organization":s.id},{"type":s.types[type_index].type_id}]},function(res){
			if(!res) return;
			/* Remove from table */
			s.tbody_types.removeChild(s.types[type_index].tr);
			/* Remove from s */
			s.types[type_index] = null;
		});
	}
	
/* Methods to manage the contact points table */
	
	/**
	 * @method addPointRow
	 * Will add a row to the contact_points table
	 * If rights.remove this method will call the addRemoveButton method
	 * @parameter point_index the index in the s object of the contact_point
	 */
	s.addPointRow = function(point_index){
		var tr = document.createElement('tr');
		var td1 = document.createElement('td');
		var td2 = document.createElement('td');
		var td3 = document.createElement('td');
		var div = document.createElement('div');
		div.context = null;
		div.innerHTML = s.points[point_index].last_name.uniformFirstLetterCapitalized() + ", " + s.points[point_index].first_name.uniformFirstLetterCapitalized();
		div.onclick = function(){
			window.location = "/dynamic/people/page/profile?plugin=people&people="+s.points[point_index].people_id;
		};
		div.onmouseover = function(){this.style.textDecoration = "underline";};
		div.onmouseout = function(){this.style.textDecoration = "";};
		div.title = "See the profile of "+s.points[point_index].last_name.uniformFirstLetterCapitalized() + ", " + s.points[point_index].first_name.uniformFirstLetterCapitalized();
		div.style.cursor = "pointer";
		td2.appendChild(div);
		if(rights.remove) s.addRemoveButton(td3, point_index,"point");
		s.setDesignation(td1,point_index);
		tr.appendChild(td1);
		tr.appendChild(td2);
		tr.appendChild(td3);
		s.points[point_index].tr = tr;
		s.tbody_points.appendChild(tr);
	}
	
	/**
	 * @method removePoint
	 * Removes a given contact_point from the database,the displayed table, and from the s object
	 * @parameter index the index in the s object of the contact_point to remove
	 */
	s.removePoint = function(index){
		/* Remove from database */
		service.json("data_model","remove_row_with_no_primary",{table:"Contact_point",fields:[{"organization":s.id},{"people":s.points[index].people_id}]},function(res){
			if(!res) return;
			/* Remove from table */
			s.tbody_points.removeChild(s.points[index].tr);
			/* Remove from s */
			s.points[index] = null;
		});
	}
	
	/**
	 * @method setDesignation
	 * Set the designation column of the given row
	 * @parameter container the one which will contain the designation
	 * @parameter index the index in the s object of the contact_point to which this designation belongs to
	 */
	s.setDesignation = function(container,index){
		var div = document.createElement("div");
		div.innerHTML = s.points[index].designation;
		if(rights.edit){
			div.onclick = function(){
				s.addContextDesignation(container,index);
			};
			div.style.cursor = "pointer";
		}
		div.style.color = "#808080";
		div.style.textAlign = "right";
		div.style.paddingRight = "20px";
		container.appendChild(div);
	}
	
	/**
	 * @method addContextDesignation
	 * Creates the context menu to show for setting the designation attribute of a given contact_point
	 * @parameter container the one the context_menu will be displayed below
	 * @parameter index the index in the s object of the contact_point to which this designation belongs to
	 */
	s.addContextDesignation = function(container,index){
		//require("context_menu.js",function(){
			if(!s.contextDesignation){
				s.contextDesignation = new context_menu();
			}
			s.contextDesignation.clearItems();
			s.setContextDesignation(container,"President",index);
			s.setContextDesignation(container,"CEO",index);
			s.setContextDesignation(container,"General Manager",index);
			s.setContextDesignation(container,"Toto",index);
			s.setContextDesignation(container,"Custom",index);
			s.contextDesignation.showBelowElement(container);
		//});
	}
	
	/**
	 * @method setContextDesignation
	 * Creates an item in the designation context_menu
	 * @parameter container the one the context_menu will be displayed below
	 * @parameter data {string} the value of the item
	 * In the custom case, an input is generated
	 * @parameter index the index in the s object of the current contact_point
	 */
	s.setContextDesignation = function(container, data, index){
		var item = document.createElement('div');
		item.innerHTML = data;
		if(s.points[index].designation == data) item.style.fontWeight ='bold';
		var people_id = this.points[index].people_id;
		if(data == "Custom"){
			var input = document.createElement("INPUT");
			input.type = 'text';
			input.maxLength = 10;
			input.size = 10;
			item.appendChild(input);
			s.contextDesignation.onclose = function(){
				if(input.value.checkVisible()){
					s.saveDesignation(people_id, input.value.uniformFirstLetterCapitalized(),container);
				}
			};
			input.onkeypress = function(e){var ev = getCompatibleKeyEvent(e);
									if(ev.isEnter) s.contextDesignation.hide();
								};
		}
		else{
			item.onclick = function(){
				s.saveDesignation(people_id,data,container);
			};
		}
		var temp = item.style.backgroundColor;
		item.onmouseover = function(){this.style.backgroundColor ='#808080';};
		item.onmouseout = function(){this.style.backgroundColor = temp;};
		item.style.cursor = 'pointer';
		s.contextDesignation.addItem(item);
		if(data == "Custom") item.onclick = null;
	}
	
	/**
	 * @method saveDesignation
	 * Called when clicking on an item of the designation context_menu
	 * Will update the database, the table, and the s object
	 * @parameter people_id the id of the people corresponding to contact point (supposing it is unique)
	 * @parameter data the data to save
	 * @parameter container the td containing the designation
	 */
	s.saveDesignation = function(people_id, data, container){
		/* Update the database */
		service.json("data_model","save_row_with_no_primary",{table:"Contact_point",where:[{organization:s.id},{people:people_id}],field_designation:data},function(res){
			if(!res) return;
			var index = s.findPointIndex(people_id);
			/* Update the s object */
			s.points[index].designation = data;
			/* Update the table */
			container.removeChild(container.firstChild);
			s.setDesignation(container, index);
		});
	}
	
	/**
	 * @method findPointIndex
	 * Find the index of a point id in the s object
	 * !This method is ok only in the case of one people cannot be assigned to several contact points for one company!
	 * @parameter id
	 */
	s.findPointIndex = function(id){
		var index = null;
		for(var i = 0; i < s.points.length; i++){
			if(s.points[i].people_id == id){
				index = i;
				break;
			}
		}
		return index;
	}
	
	/**
	 * @method finish
	 * Will append all the children to the main table
	 */
	s.finish = function(){
		title_container.appendChild(s.header);
		var table = document.createElement("table");
		// var th = document.createElement("th");
		// var th1 = document.createElement("td");
		// var th2 = document.createElement("td");
		var tr = document.createElement("tr");
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		// th2.appendChild(s.header);
		// th.appendChild(th1);
		// th.appendChild(th2);
		td1.appendChild(s.span_types);
		td1.style.paddingRight = "40px";
		td1.style.verticalAlign = 'top';
		td2.style.verticalAlign = 'top';
		td2.appendChild(s.span_points);
		tr.appendChild(td1);
		tr.appendChild(td2);
		//table.appendChild(th);
		table.appendChild(tr);
		// s.span_points.style.verticalAlign = 'top';
		// s.span_types.style.verticalAlign = 'top';
		// table.style.verticalAlign = 'top';
		container.appendChild(table);
		// container.appendChild(s.span_types);
		// container.appendChild(s.span_points);
	}
	//TODO screen "create organization"
	
	//TODO addContactPoint: one people can be seen as several contact_point for one organization? if yes, change saveDesignation method (using findIndex becomes wrong...)
</script>
	<?php
}
?>