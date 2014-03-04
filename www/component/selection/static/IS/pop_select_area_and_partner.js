function pop_select_area_and_partner(geographic_area, host, host_address, host_name, onclose){
	var t = this;
	t.host = host;
	t.host_address = host_address;
	t.host_name = host_name;
	t.geographic_area = geographic_area;
	t._table = document.createElement("table");
	t._country_id = null;
	t._init = function(){
		t._pop = new popup_window("Select Location and Host Partner","",t._table);
		t._setPopContent();
		t._pop.addButton("Finish later","finish_later_button",function(){
			t._closePop();
		});
		t._pop.addButton("<img src = '"+theme.icons_16.ok+"'/> Finish","finish_button",function(){
			t._closePop();
		});
		t._updatePopButtonsAndInfoRow();
		t._pop.show();
		/*Get the current domain country id */
		window.top.require("geography.js", function() {
			window.top.geography.getCountries(function(countries) {
				t._country_id = countries[0].country_id;
				var after_area_set = function(){t._refreshTDPartners(t.geographic_area);};
				t._initArea(after_area_set);
				
			});
		});		
	};
	
	t._setPopContent = function(){
		//Set the header
		var tr_info = document.createElement("tr");
		t._td_info = document.createElement("td");
		t._td_info.colSpan = 2;
		t._td_info.innerHTML = "<img src = '"+theme.icons_16.info+"'/><i> To be fully completed, an information session<br/>must be attached to a host partner</i>";
		tr_info.appendChild(t._td_info);
		t._table.appendChild(tr_info);
		//Set the body
		var tr = document.createElement("tr");
		t._td_area = document.createElement("td");
		t._td_partners = document.createElement("td");
		//This td is initialized hidden
		t._td_partners.style.position = 'absolute';
		t._td_partners.style.visibility = 'hidden';
		t._td_partners.style.top = '-10000px';
		t._td_partners.style.left = '-10000px';
		tr.appendChild(t._td_area);//Contains the select area
		tr.appendChild(t._td_partners);//Contains the select partner
		t._table.appendChild(tr);
		//TODO set pop style
		t._partners_lists = new select_area_and_matching_organizations(t._td_partners, t.geographic_area, "Select this partner as host",t.host);
		//Set the partners footer: add a create partner button
		t._create_partner_button = document.createElement("div");
		t._create_partner_button.className = "button";
		t._create_partner_button.innerHTML = "<img src = '"+theme.icons_16.add+"'/> Create partner";
		t._create_partner_button.onclick = function(){
			var p = new popup_window("New Organization", theme.icons_16.add, "");
			//The partner is created with a prefilled address, which t.geographic_area is the current one
			var frame = p.setContentFrame("/dynamic/contact/page/organization_profile?creator=Selection&organization=-1&address_country_id="+t._country_id+"&address_area_id="+t.geographic_area);
			p.addOkCancelButtons(function(){
				p.freeze();
				var win = getIFrameWindow(frame);
				var org = win.organization.getStructure();
				service.json("contact", "add_organization", org, function(res) {
					if (!res) { p.unfreeze(); return; }
					//t._updateHostAttribute(res.id); problem if the user has added several addresses
					t._refreshTDPartners(t.geographic_area);					
					t._updatePopButtonsAndInfoRow();
					p.close();
				});
			});
			p.show();
		};
		t._td_partners.appendChild(t._create_partner_button);
		//Set the listeners of the partners list
		t._partners_lists.onpartnerselected.add_listener(t._onPartnerRowSelected);
		t._partners_lists.onpartnerunselected.add_listener(t._onPartnerRowUnselected);
	};
	
	/** Create the part containing the geographic area */
	t._initArea = function(ondone) {
		while (t._td_area.childNodes.length > 0) t._td_area.removeChild(t._td_area.childNodes[0]);
		if(!t._country_id)
			return;
		if (t._initializing_area) { t._reinit_area = true; return; }
		t._initializing_area = true;
		require("geographic_area_selection.js", function() {
			new geographic_area_selection(t._td_area, t._country_id, function(area) {
				if (t.geographic_area) {
					area.setToReturn(t.geographic_area);
					area.startFilter(t.geographic_area);
				}
				area.onchange = function() {
					var a = area.getSelectedArea();
					t.geographic_area = a != null ? a.id : null;
					//data.geographic_area_text = a.text;
					t._refreshTDPartners(t.geographic_area);
					//Reset the host if the area is changed
					t._updateHostAttribute(null);
					//Update the buttons
					t._updatePopButtonsAndInfoRow();
				};
				if (window.get_popup_window_from_element) {
					var popup = window.get_popup_window_from_element(t._td_area);
					if (popup != null) popup.resize();
				}
				t._initializing_area = false;
				if (t._reinit_area) {
					t._reinit_area = false;
					t._initArea();
				}
				if(ondone)
					ondone();
			});
		});
	};
	
	t._updatePopButtonsAndInfoRow = function(){;
		//Update the buttons and the information row
		if(t.host != null){
			t._pop.disableButton("finish_later_button");
			t._pop.enableButton("finish_button");
			t._td_info.innerHTML = "<center><img src = '"+theme.icons_16.info+"'/><i> Location field is fully completed!</i></center>";
			t._td_info.style.color = "green";
		} else {
			t._pop.disableButton("finish_button");
			t._pop.enableButton("finish_later_button");
			t._td_info.innerHTML = "<center><img src = '"+theme.icons_16.info+"'/><i> To be fully completed, an information session<br/>must be attached to a host partner</i></center>";
			t._td_info.style.color = "red";
			
		}
	};
	
	t._onPartnerRowSelected = function(row_data){
		// We are sure that the partners selected has at least one address because it was retrieved from the database based on its addresses
		if(row_data.addresses.length > 1){
			//Pop a choice between the addresses
			var content = document.createElement("div");
			var pop = new popup_window("Select the address","",content);
			//The content is a list of all the addresses. After clicking on an address, the two popups are closed
			//The onclose method will unselect the partners if no address is selected
			pop.onclose = function(){
				//Reset the partners lists and the selected partner
				t._updateHostAttribute(null);
				t._updateHostAddressAttribute(null);
				t._partners_lists.refresh(t.geographic_area);
			};
			var head = document.createElement("div");
			head.appendChild(document.createTextNode("This partner has several addresses in this area, please pick one:"));
			content.appendChild(head);
			//Get the addresses text
			var ids = [];
			for(var i = 0; i < row_data.addresses.length; i++)
				ids.push(row_data.addresses[i].address_id);
			service.json("contact","get_address",{addresses:ids},function(res){
				if(!res){
					error_dialog("An error occured");
					//The selection couldn't finish correctly so reset
					pop.close();
					t._onPartnerRowUnselected();
				} else {
					require("address_text.js",function(){
						for(var i = 0; i < res.length; i++){
							var button = document.createElement("BUTTON");
							button.style.textAlign = "left";
							button.style.cursor = "pointer";
							button.pop = pop;
							button.partner_id = row_data.id;
							var text = new address_text(res[i]);
							button.appendChild(text.element);
							button.address = res[i];
							button.onclick = function(){
								this.pop.onclose = null;
								t._updateHostAddressAttribute(this.address.id);
								t._updateHostAttribute(this.partner_id);
								this.pop.close();
								t._closePop();
							};
							button.style.marginTop = "20px";
							button.style.marginBottom = "20px";
							button.style.marginLeft = "10px";
							button.style.marginRight = "10px";
							content.appendChild(button);
						}
						layout.invalidate(content);
					});
				}
			});
			pop.show();
		} else {
			//Update the attributes
			t._updateHostAttribute(row_data.id);
			t._updateHostAddressAttribute(row_data.addresses[0].address_id);
		}
		//update the organization name attribute
		t.host_name = row_data.name;
		//Update the buttons
		t._updatePopButtonsAndInfoRow();
	};
	
	t._onPartnerRowUnselected = function(){		
		//Reset the host attributes
		t._updateHostAttribute(null);
		t._updateHostAddressAttribute(null);
		//update the organization name attribute
		t.host_name = null;
		//Update the buttons
		t._updatePopButtonsAndInfoRow();
	};
	
	t._updateHostAttribute = function(new_host){
		t.host = new_host != t.host ? new_host : t.host;
		t._partners_lists.preselected_partner_id = t.host;
	};
	
	t._updateHostAddressAttribute = function(new_address){
		t.host_address = new_address != t.host_address ? new_address : t.host_address;
	};
	
	t._shown = false;
	t._refreshTDPartners = function(area_id){
		if(area_id != null){
			if(!t._shown){
			//Show the td
				require("animation.js",function(){
					if(t._td_partners.anim1) animation.stop(t._td_partners.anim1);
					if(t._td_partners.anim2) animation.stop(t._td_partners.anim2);
					t._td_partners.endWidth = t._td_partners.originalWidth != null ? t._td_partners.originalWidth : 150;
					t._td_partners.anim1 = animation.create(t._td_partners, 0, t._td_partners.endWidth, 600, function(value, element){
						element.style.width = Math.floor(value)+'px';
						element.style.overflow = "";
						if(value == element.endWidth)
							layout.invalidate(element.parentNode);
					});
					t._td_partners.anim2 = animation.fadeIn(t._td_partners, 500, function(){
						t._td_partners.style.position = 'static';
						t._td_partners.style.visibility = 'visible';
						t._td_partners.style.top = '';
						t._td_partners.style.left = '';
					});
				});
				t._shown = true;
			}
			//Refresh the lists content
			t._partners_lists.refresh(area_id);
		} else {
			if(t._shown){
			//Hide the td
				require("animation.js",function(){
					if(t._td_partners.anim1) animation.stop(t._td_partners.anim1);
					if(t._td_partners.anim2) animation.stop(t._td_partners.anim2);
					var start = t._td_partners.offsetWidth;
					t._td_partners.originalWidth = start;
					//Collapse the td from right to left
					t._td_partners.anim1 = animation.create(t._td_partners, start, 0, 600, function(value, element){
						element.style.width = Math.floor(value)+'px';
						element.style.overflow = "hidden";
						if (value == 0) layout.invalidate(t._td_partners.parentNode);
					});
					//Fade out
					t._td_partners.anim2 = animation.fadeOut(t._td_partners,500,function(){
						t._td_partners.style.position = 'absolute';
						t._td_partners.style.visibility = 'hidden';
						t._td_partners.style.top = '-10000px';
						t._td_partners.style.left = '-10000px';
					});
				});
				t._shown = false;
			}
		}
	};
	
	t._closePop = function(){
		if(onclose)
			onclose(t.geographic_area, t.host, t.host_address, t.host_name);
		t._pop.close();
	};
	
	t.getHostId = function(){
		return t.host;
	};
	
	require(["popup_window.js","select_area_and_matching_organizations.js"],function(){
		t._init();
	});
}