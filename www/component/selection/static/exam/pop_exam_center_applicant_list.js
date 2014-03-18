function pop_exam_center_applicant_list(center_id){
	var t = this;
	
	t._init = function(){
		t._table = document.createElement('table');
		t.pop = new popup_window("Applicants list","",t._table);
		t._setTableHeader();
	};
	
	t._setTableHeader = function(){
		//Create the select order by row
		var thead = document.createElement("tr");
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		td.colSpan = 2;
		var select = document.createElement("select");
		var by_name = document.createElement("option");
		by_name.value = "name";
		by_name.appendChild(document.createTextNode("Last name"));
		var by_id = document.createElement("option");
		by_id.value = "id";
		by_id.appendChild(document.createTextNode("Applicant ID"));
		by_id.selected = "selected";//Pre selected value
		select.appendChild(by_name);
		select.appendChild(by_id);
		select.onchange = function(){
			var selected = this.options[this.selectedIndex].value;
			t._refreshList(selected);
		};
		td.appendChild(document.createTextNode("Sort by: "));
		td.appendChild(select);
		tr.appendChild(td);
		thead.appendChild(tr);
		t._table.appendChild(thead);
	};
	
	t._refreshList = function(order_by){
		if(!t._tbody){
			t._tboby = document.createElement("tbody");
			t._table.appendChild(t._tbody);
		}
		if(!t._loading){
			t._loading = document.createElement("tr");
			var td = document.createElement("td");
			td.colSpan = 2;
			var img = document.createElement("img");
			img.src = theme.icons_16.loading;
			td.appendChild(img);
			t._loading.appendChild(td);
		}			
		while(t._tbody.firstChild)
			t._tbody.removeChild(t._tbody.firstChild);
		t._tbody.appendChild(t._loading);
		service.json("selection","exam/get_applicants_assigned_to_center",{EC_id:center_id,order_by:order_by},function(res){
			if(!res)
				error_dialog("An error occured");
			else{
				t._tbody.removeChild(t._loading);
				if(res.applicants == null){
					var tr = document.createElement("tr");
					var td = document.createElement("td");
					td.appendChild(document.createTextNode("No applicant"));
					td.style.fontStyle = "italic";
					td.colSpan = 2;
					tr.appendChild(td);
					t._tbody.appendChild(tr);
				}
				for(var i = 0; i < res.applicants.length;i++){
					var tr = document.createElement("tr");
					var td1 = document.createElement("td");
					var td2 = document.createElement("td");					
					tr.appendChild(td1);
					tr.appendChild(td2);
					t._tbody.appendChild(tr);
					//Set the name td
					td1.appendChild(document.createTextNode(" - "));
					var link = document.createElement("a");
					link.appendChild(document.createTextNode(res.applicants[i].applicant_id+", "+res.applicants[i].first_name+", "+res.applicants[i].last_name));
					link.className = "black_link";
					link.people_id = res.applicants[i].people_id;
					link.onclick = function(){
						//TODO set popup profile
						return false;
					};
					td1.appendChild(link);
					//TODO set td2 (unassign button)
				}
			}
		});
			 
	};
	
	require("popup_window.js",function(){
		t._init();
	});
}