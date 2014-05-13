theme.css("grid.css");

/**
 * Create a table containing two lists of partners. One with all the partners from existing in this geographic area,
 * an other one with all the partners in the parent area (but the ones in the given area) 
 * @param {String|Element} container
 * @param {Number|NULL} area_id the geographic_area id, in the case of the table lists shall be initialized
 * @param {String|NULL) row_title the title attribute to set to each list tr elements
 * @param {Number|NULL} preselected_partner_id the partner id to preselect into the list
 * @param {String} creator organization creator
 */
function select_area_and_matching_organizations(container, area_id, row_title, preselected_partner_id, creator){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.onpartnerselected = new Custom_Event();//Custom event fired when a row is selected
	t.onpartnerunselected = new Custom_Event();//Custom event fired when a row is unselected
	t.preselected_partner_id = preselected_partner_id; 
	t._rowsSelectable = [];
	
	/**
	 * Launch the process, create a table for the two lists
	 */
	t._init = function(){
		var table = document.createElement("table");
		var tr_head_list_1 = document.createElement("tr");
		t._tr_list_1 = document.createElement("tr");
		var tr_head_list_2 = document.createElement("tr");
		t._tr_list_2 = document.createElement("tr");
		var th_list_1 = document.createElement("td");
		th_list_1.innerHTML = "Partners in this area";
		var th_list_2 = document.createElement("td");
		th_list_2.innerHTML = "Partners surrounding";
		tr_head_list_1.appendChild(th_list_1);
		tr_head_list_2.appendChild(th_list_2);
		table.appendChild(tr_head_list_1);
		table.appendChild(t._tr_list_1);
		table.appendChild(tr_head_list_2);
		table.appendChild(t._tr_list_2);
		container.appendChild(table);
		if(area_id != null)
			t.refresh(area_id);
	};
	
	t._isRefreshing = false;
	t._restartRefreshing = false;
	t._idForReRefreshing = null;
	t._geographic_area_selected = null;//Store the selected area id
	/**
	 * Refresh the content of the lists, calling the contact#get_json_organizations_by_geographic_area service
	 * @param {Number} id the id of the geographic area used for the new selection
	 */
	t.refresh = function(id){
		if(t._isRefreshing){
			t._restartRefreshing = true;
			t._idForReRefreshing = id;
			return;
		}
		t._isRefreshing = true;
		t._geographic_area_selected = id;
		//reset the array containing the lists rows
		delete t._rowsSelectable;
		t._rowsSelectable = [];
		//remove all the children
		while(t._tr_list_1.firstChild)
			t._tr_list_1.removeChild(t._tr_list_1.firstChild);
		while(t._tr_list_2.firstChild)
			t._tr_list_2.removeChild(t._tr_list_2.firstChild);
		//Set loading icon
		t._tr_list_1.appendChild(t._createLoadingTD());
		t._tr_list_2.appendChild(t._createLoadingTD());
		//get the data
		service.json("contact","get_json_organizations_by_geographic_area",{geographic_area:id,creator:creator},function(r){
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			if(r){
				t._createListFromData(r.from_area,td1,id);
				t._createListFromData(r.from_parent_area,td2,id); 
			} else {
				td1.innerHTML = "<i>This functionality is not available</i>";
				td2.innerHTML = "<i>This functionality is not available</i>";
			}
			//Remove the loading tds
			t._tr_list_1.removeChild(t._tr_list_1.firstChild);
			t._tr_list_2.removeChild(t._tr_list_2.firstChild);
			//Set the content
			t._tr_list_1.appendChild(td1);
			t._tr_list_2.appendChild(td2);
			t._isRefreshing = false;
			if(t._restartRefreshing){
				t._restartRefreshing = false;
				var new_id = t._idForReRefreshing;
				t._idForReRefreshing = null;
				t.refresh(new_id);
			}
		});
	};
	
	/**
	 * Create a list from the given list (retrieved from the contact#get_json_organizations_by_geographic_area service)
	 * The style of the list is computed by grid.css
	 * @param {Array} data array of Organization from the contact/get_json_organizations_by_geographic_area service
	 * @param {Element} cont the container of the created list
	 * @param {Number} reference_area_id the id of the area on which the organization selection is based (can be used as a reference)
	 */
	t._createListFromData = function(data, cont, reference_area_id){
		if(data.length > 0){
			var table = document.createElement("table");
			var tbody = document.createElement("tbody");
			table.appendChild(tbody);
			table.className = "grid";
			table.style.width = "100%";
			for(var i = 0; i < data.length; i++){
				var tr = document.createElement("tr");
				var td = document.createElement("td");
				td.innerHTML = data[i].name.uniformFirstLetterCapitalized();
				td.style.verticalAlign = "top";
				
				/**
				 * If a partner has several addresses, or was found from the children / parent of the selected area, all the matching geographic_area_text are displayed
				 * To avoid having too many data, if the geographic area of the address is the same as the reference_area_id and that this organization row has only one address, the geographic_area_text is not displayed
				 */
				var td_area_text = document.createElement("td");
				if(data[i].addresses.length == 1 && data[i].addresses[0].geographic_area.id != reference_area_id){
					var div = document.createElement("div");
					div.style.whiteSpace = "nowrap";
					div.style.fontStyle = "italic";
					div.appendChild(document.createTextNode("- " + data[i].addresses[0].geographic_area.text));
					td_area_text.appendChild(div);
				} else if (data[i].addresses.length > 1){
					for(var j = 0; j < data[i].addresses.length; j++){
						var div = document.createElement("div");
						div.style.whiteSpace = "nowrap";
						div.style.fontStyle = "italic";
						div.appendChild(document.createTextNode("- " + data[i].addresses[j].geographic_area.text));
						td_area_text.appendChild(div);
					}
				}
				tr.data = data[i];
				tr.style.cursor = "pointer";
				if(row_title)
					tr.title = row_title;
				if(t.preselected_partner_id == data[i].id && t._rowContainsAtLeastOneAddressInSelectedArea(data[i]))
					tr.className = "selected";
				tr.onclick = function(){
					var new_classname = this.className == "selected" ? "" : "selected";
					//If an other part was selected before, reset
					t._resetSelectedRow();//Will reset all the rows classname
					this.className = new_classname;
					if(this.className == "selected"){
						t.onpartnerselected.fire(this.data);
					} else
						t.onpartnerunselected.fire();
				};
				tr.appendChild(td);
				tr.appendChild(td_area_text);
				t._rowsSelectable.push(tr);
				tbody.appendChild(tr);
			}
			cont.appendChild(table);
		} else {
			cont.innerHTML = "<center><i>No result</i></center>";
			cont.style.fontSize = "small";
		}
		layout.invalidate(cont);
	};
	
	/**
	 * Create a TD with a loading icon inside
	 * @returns {HTMLElement} td the expected td element
	 */
	t._createLoadingTD = function(){
		var td = document.createElement("td");
		td.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		td.style.textAlign = "center";
		return td;
	};
	
	/**
	 * Reset all the className attributes of rows from both lists
	 */
	t._resetSelectedRow = function(){
		t.preselected_partner_id = null;
		for(var i = 0; i < t._rowsSelectable.length; i++){
			if(t._rowsSelectable[i].className == "selected")
				t._rowsSelectable[i].className = "";
		}
	};
	
	/**
	 * Check that a row contains at least one address in the selected area
	 * @param {Array} row_data the data linked to the row
	 * @returns {boolean} true if the row contains at least one address in the selected area
	 */
	t._rowContainsAtLeastOneAddressInSelectedArea = function(row_data){
		for(var i = 0; i < row_data.addresses.length; i++){			
			if(row_data.addresses[i].geographic_area_id == t._geographic_area_selected)
				return true;
		}
		return false;
	};
	
	t._init();
}