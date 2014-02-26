function select_area_and_matching_organizations(container, area_id){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.onpartnerselected = new Custom_Event();
	
	t._init = function(){
		var table = document.createElement("table");
		var tr_head_list_1 = document.createElement("tr");
		t._tr_list_1 = document.createElement("tr");
		var tr_head_list_2 = document.createElement("tr");
		t._tr_list_2 = document.createElement("tr");
		var th_list_1 = document.createElement("th");
		th_list_1.innerHTML = "Partners in this area";
		var th_list_2 = document.createElement("th");
		th_list_2.innerHTML = "Partners surrounding";
		tr_head_list_1.appendChild(th_list_1);
		tr_head_list_2.appendChild(th_list_2);
		table.appendChild(tr_head_list_1);
		table.appendChild(t._tr_list_1);
		table.appendChild(tr_head_list_2);
		table.appendChild(t._tr_list_2);
		container.appendChild(table);
		if(area_id != null)
			t._refresh(area_id);
	};
	
	t._refresh = function(id){
		//remove all the children
		while(t._tr_list_1.firstChild)
			t._tr_list_1.removeChild(t._tr_list_1.firstChild);
		while(t._tr_list_2.firstChild)
			t._tr_list_2.removeChild(t._tr_list_2.firstChild);
		//Set loading icon
		t._tr_list_1.appendChild(t._createLoadingTD());
		t._tr_list_2.appendChild(t._createLoadingTD());
		//get the data
		service.json("contact","get_json_organizations_by_geographic_area",{geographic_area:id},function(r){
			var td1 = document.createElement("td");
			var td2 = document.createElement("td");
			if(r){
				t._createListFromData(r.from_area,td1);
				t._createListFromData(r.from_parent_area,td2);
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
		});
	};
	
	t._createListFromData = function(data, cont){
		if(data.length > 0){
			var table = document.createElement("table");
			table.style.width = "100%";
			for(var i = 0; i < data.length; i++){
				var tr = document.createElement("tr");
				var td = document.createElement("td");
				if(t._isEvenNumber(i))
					td.style.backgroundColor = "#808080";
				td.innerHTML = data[i].name.uniformFirstLetterCapitalized();
				td.data = data[i];
				td.onclick = function(){
					t.onpartnerselected.fire(this.data);
				};
				tr.appendChild(td);
				table.appendChild(tr);
			}
			cont.appendChild(table);
		} else {
			cont.innerHTML = "<i>No result</i>";
		}
	};
	
	t._createLoadingTD = function(){
		var td = document.createElement("td");
		td.innerHTML = "<img src = '"+theme.icons_16.loading+"'/>";
		td.style.textAlign = "center";
		return td;
	};
	
	t._isEvenNumber = function(int){
		return (t._isInteger(int) && (int % 2 == 0));
	};
	
	t._isInteger = function(n){
		return n == parseFloat(n);
	};
	
	t._init();
}