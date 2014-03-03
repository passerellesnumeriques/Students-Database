/**
 * 
 * @param container
 * @param partner_data {Object} partner_data with two attributes: <code>id</code> and <code>name</code>
 * @param contact_points_selected
 * @param all_contact_points
 * @param can_manage
 */
function create_partner_row(container, partner_data, contact_points_selected, all_contact_points, can_manage){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.contact_points_selected = contact_points_selected;
	t.onupdatecontactpointsselection = new Custom_Event(); //Fired when the contact points selected list is updated
	t._init = function(){
		t._table = document.createElement("table");
		var tr_header = document.createElement('tr');
		var th = document.createElement('th');
		var th_empty = document.createElement("th");
		th.innerHTML = "Contact points selected";
//		th.style.textAlign = "right";
		tr_header.appendChild(th_empty);
		tr_header.appendChild(th);
		t._table.appendChild(tr_header);
		var tr_body = document.createElement("tr");
		t._table.appendChild(tr_body);
		t._setTRBody(tr_body);
		if(can_manage)
			t._setFooter();
		container.appendChild(t._table);
	};
	
	t._setTRBody = function(body){
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		td1.innerHTML = partner_data.name.uniformFirstLetterCapitalized();
		td1.style.fontSize = "large";
		if(t.contact_points_selected.length > 0){
			for(var i = 0 ; i < t.contact_points_selected.length; i++){
				var cont = document.createElement("div");
				var index = t._findIndexInAllContactPoints(t.contact_points_selected[i]);
				var text = document.createTextNode(all_contact_points[index].people_last_name.uniformFirstLetterCapitalized()+", "+all_contact_points[index].people_first_name.uniformFirstLetterCapitalized()+", "+all_contact_points[index].people_designation+" ");
				var link = document.createElement("img");
				link.src = "/static/people/profile_16.png";
				link.style.verticalAlign = "bottom";
				link.title = "See profile";
				link.style.cursor = "pointer";
				link.people_id = all_contact_points[index].people_id;
				link.onclick = function(){
					var people_id = this.people_id;
					require("popup_window.js",function(){
						var pop = new popup_window("People Profile","/static/people/people_16.png");
						pop.setContentFrame("/dynamic/people/page/profile?plugin=people&people="+people_id);
						pop.show();
					});
				};
				cont.appendChild(text);
				cont.appendChild(link);
				td2.appendChild(cont);
				td2.style.paddingTop = "25px";
				td2.style.paddingLeft = "25px";
				td2.style.paddingRight = "25px";
				cont.style.paddingTop = "5px";
				cont.style.paddingBottom = "5px";
			}
		} else {
			td2.innerHTML = "<center><i>No one selected</i></center>";
		}
		body.appendChild(td1);
		body.appendChild(td2);
	};
	
	t._findIndexInAllContactPoints = function(people_id){
		for(var i = 0; i < all_contact_points.length; i++){
			if(all_contact_points[i].people_id == people_id)
				return i;
		}
		return null;
	};
	
	t._setFooter = function(){
		var tr = document.createElement("tr");
		var td = document.createElement("td");
		td.colSpan = 2;
		td.style.textAlign = "right";
		tr.appendChild(td);
		t._table.appendChild(tr);
		var button = document.createElement("button");
		button.style.cursor = "pointer";
		button.innerHTML = "Manage";
		button.onclick = function(){
			var pop_cont = document.createElement("div");
			var pop = new popup_window("Select the contacts points",theme.icons_16.question,pop_cont);
			var table = document.createElement("table");
			table.contact_points_selected = [];
			pop_cont.appendChild(table);
			t._setTableSelectContactPoints(table);
			pop.addOkCancelButtons(function(){
				//Update t.contact_points_selected
				t.contact_points_selected = [];
				for(var i = 0; i < table.contact_points_selected.length; i++){
					if(table.contact_points_selected[i].selected)
						t.contact_points_selected.push(table.contact_points_selected[i].people_id);
				}
				//Fire the custom event
				t.onupdatecontactpointsselection.fire(t.contact_points_selected);
				pop.close();
				//Reset the table
				t.reset();
			});
			pop.show();
		};
		td.appendChild(button);
	};
	
	t._setTableSelectContactPoints = function(table){
		var length = all_contact_points.length;
		for(var j = 0; j < length; j++){
			table.contact_points_selected[j] = {};
			table.contact_points_selected[j].people_id = all_contact_points[j].people_id;
			table.contact_points_selected[j].selected = false;
			var tr = document.createElement("tr");
			var td = document.createElement("td");
			td.people_id =  all_contact_points[j].people_id;
			var text = "";
			text +=  all_contact_points[j].people_last_name.uniformFirstLetterCapitalized();
			text += ", "+  all_contact_points[j].people_first_name.uniformFirstLetterCapitalized();
			if( all_contact_points[j].people_designation != null &&  all_contact_points[j].people_designation != "")
				text += ", "+  all_contact_points[j].people_designation;
			td.innerHTML = text;
			td.style.fontStyle = "italic";
			td.className = "button";
			td.index = j;
			if(t._isContactPointSelected(all_contact_points[j].people_id)){
				td.style.backgroundColor = "rgb(17, 225, 45)";
				table.contact_points_selected[j].selected = true;
			}
			
			td.onclick = function(){
				if(this.style.backgroundColor == "#11E12D" ||this.style.backgroundColor == "rgb(17, 225, 45)"){
					this.style.backgroundColor = "#FFFFFF";
					table.contact_points_selected[this.index].selected = false;
				}
				else if (this.style.backgroundColor == "" || this.style.backgroundColor == "rgb(255, 255, 255)"){
					this.style.backgroundColor = "#11E12D";
					table.contact_points_selected[this.index].selected = true;
				}
			};
			tr.appendChild(td);
			table.appendChild(tr);
		}
		if(length == 0){
			var td = document.createElement("td");
			td.innerHTML = "This organization has no contact point";
			td.style.fontStyle = "italic";
			table.appendChild((document.createElement("tr")).appendChild(td));
		}
	};
	
	t._isContactPointSelected = function(contact_point_id){
		var selected = false;
		for(var i = 0; i < t.contact_points_selected.length; i++){
			if(t.contact_points_selected[i] == contact_point_id){
				selected = true;
				break;
			}
		}
		return selected;
	};
	
	t.reset = function(){
		container.removeChild(t._table);
		delete t._table;
		t._init();
	};

	require('popup_window.js',function(){
		t._init();
	});
	
}