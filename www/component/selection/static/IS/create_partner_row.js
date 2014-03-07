/**
 * 
 * @param partner_data {Object} partner_data with two attributes: <code>id</code> and <code>name</code>
 * @param contact_points_selected
 * @param all_contact_points
 * @param can_manage
 * @param {Object | null} add_data_to_give_when_event_fired (optional) data added to the contact_points_selected array when onupdatecontactpointsselection is fired
 * The data is added this way: {contact_points_selected: ,additional:add_data_to_give_when_event_fired} 
 */
function create_partner_row(container, partner_data, contact_points_selected, all_contact_points, can_manage, add_data_to_give_when_event_fired, display_row_header, onaddresschange_listener,oncontactpointchange_listener){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	t.contact_points_selected = contact_points_selected;
	t.onupdatecontactpointsselection = new Custom_Event(); //Fired when the contact points selected list is updated
	t.onneedtorefresh = new Custom_Event();
	t._init = function(){
		t._table = document.createElement("table");
		t._table.style.width = "100%";
		if(display_row_header){
			var tr_header = document.createElement('tr');
			var th = document.createElement('th');
			th.innerHTML = "Contact points selected";
//			th.style.paddingRight = "30px";
			th.style.textAlign = "right";
			th.colSpan = 2;
			tr_header.appendChild(th);
			if(can_manage){
				var th_empty = document.createElement("th");
				tr_header.appendChild(th_empty);
			}
			t._table.appendChild(tr_header);
		}
		var tr_body = document.createElement("tr");
		t._table.appendChild(tr_body);
		t._setTRBody(tr_body);
		if(can_manage)
			t._setFooter(tr_body);
		container.appendChild(t._table);
	};
	
	t._setTRBody = function(body){
		var td1 = document.createElement("td");
		var td2 = document.createElement("td");
		var link_td1 = document.createElement("a");
		td1.appendChild(link_td1);
		var text = document.createTextNode(partner_data.name);
		window.top.datamodel.registerCellText(window, "Organization", "name", partner_data.id, text);
		link_td1.appendChild(text);
		link_td1.className = "black_link";
		link_td1.style.cursor = "pointer";
		link_td1.title = "See organization profile";
		link_td1.organization_id = partner_data.id;
		link_td1.onclick = function(){
			var organization_id = this.organization_id;
			require("popup_window.js",function(){
				var pop = new popup_window("Organization Profile",null,null);
				var frame = pop.setContentFrame("/dynamic/contact/page/organization_profile?organization="+organization_id);
				pop.show();
				waitFrameReady(getIFrameWindow(frame), function(win){ return typeof win.organization != 'undefined'; }, function(win) {
					win.organization.onaddresschange.add_listener(function() {
						//Add the listeners fired when the pop is closed
						pop.onclose = function(){
							if(onaddresschange_listener)
								onaddresschange_listener();							
						};
					});
					win.organization.oncontactpointchange.add_listener(function() {
						//Add the listeners fired when the pop is closed
						pop.onclose = function(){
							if(oncontactpointchange_listener)
								oncontactpointchange_listener();							
						};
					});
				},10000);
			});
			return false;
		};
		td1.style.fontSize = "large";
		if(t.contact_points_selected.length > 0){
			for(var i = 0 ; i < t.contact_points_selected.length; i++){
				var cont = document.createElement("div");
				var index = t._findIndexInAllContactPoints(t.contact_points_selected[i]);
				var link = document.createElement("a");
//				link.src = "/static/people/profile_16.png";
//				link.style.verticalAlign = "bottom";
				link.title = "See profile";
//				link.style.cursor = "pointer";
				link.className = "black_link";
				link.people_id = all_contact_points[index].people_id;
				link.onclick = function(){
					var people_id = this.people_id;
					require("popup_window.js",function(){
						var pop = new popup_window("People Profile","/static/people/people_16.png");
						pop.setContentFrame("/dynamic/people/page/profile?plugin=people&people="+people_id);
						pop.show();
					});
					return false;
				};
				var text;
				text = document.createTextNode(all_contact_points[index].people_last_name);
				window.top.datamodel.registerCellText(window, "People", "last_name", all_contact_points[index].people_id, text);
				link.appendChild(text);
				link.appendChild(document.createTextNode(", "));
				text = document.createTextNode(all_contact_points[index].people_first_name);
				window.top.datamodel.registerCellText(window, "People", "first_name", all_contact_points[index].people_id, text);
				link.appendChild(text);
				if(all_contact_points[index].people_designation != null) {
					link.appendChild(document.createTextNode(" ("));
					text = document.createTextNode(all_contact_points[index].people_designation);
					window.top.datamodel.registerCellText(window, "ContactPoint", "designation", {organization:partner_data.id,people:all_contact_points[index].people_id}, text);
					link.appendChild(text);
					link.appendChild(document.createTextNode(")"));
				}
				
				cont.appendChild(link);
				td2.appendChild(cont);
				if(i > 0)
					td2.style.paddingTop = "15px";
				td2.style.paddingLeft = "15px";
				td2.style.paddingRight = "30px";
				td2.style.textAlign = "right";
				cont.style.paddingTop = "5px";
				cont.style.paddingBottom = "5px";
			}
		} else {
			td2.innerHTML = "<center><i>No one selected</i></center>";
			td2.style.width = "150px";
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
	
	t._setFooter = function(tr){
//		var tr = document.createElement("tr");
		var td = document.createElement("td");
//		td.colSpan = 2;
		td.style.textAlign = "right";
		td.style.width = "30px";
		if(t.contact_points_selected.length > 1)
			td.style.paddingTop = "15px";
		tr.appendChild(td);
//		t._table.appendChild(tr);
		var button = document.createElement("div");
		button.className = "button_verysoft";
		button.innerHTML = "<img src = '/static/people/people_list_16.png'/>";
		button.title = "Pick any contact point from this organization";
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
				if(add_data_to_give_when_event_fired != null){
					t.onupdatecontactpointsselection.fire({contact_points_selected:t.contact_points_selected, additional:add_data_to_give_when_event_fired});
				} else
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