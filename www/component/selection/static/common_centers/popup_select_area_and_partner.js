/**
 * Create a popup window for selecting an area and a partner having an address in the selected area
 * The popup is made of two parts: the left one containing a geographic area selection (the user has no choice about the country, it is automatically the domain one)
 * The right one is made of a select_area_and_matching_organizations object, updated everytime the geographic area is updated
 * @param {Number | NULL} geographic_area the ID of the preselected geographic area, if any
 * @param {SelectionPartner|NULL} host the hosting partner, if any
 * @param {Function} onclose function called when the popup is closed, with this object as parameter
 * @param {String} warning_html message displayed at the top of the popup content while no host is selected
 * @param {String} ok_html message displayed at the top of the popup content when a host is selected
 */
function popup_select_area_and_partner(geographic_area, host, onclose, warning_html, ok_html){
	var t = this;
	t.geographic_area = geographic_area;
	t.host = host;
	
	/**Private functionalities and attributes*/
	t._table = document.createElement("table");
	t._country_id = null;
	
	/**
	 * Launch the process
	 * Create the popup, add the buttons, and get the current domain country ID
	 */
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
	
	/**
	 * Set the popup content
	 * The content is made of a table, with two rows
	 * First row contains errorHTML message if no host is selected (only area is not enough), else contains ok_message
	 * Second row contains two td elements. First contains geographic area selection, second contains the select_area_and_matching_organizations element, if an area is selected 
	 */
	t._setPopContent = function(){
		//Set the header
		var tr_info = document.createElement("tr");
		t._td_info = document.createElement("td");
		t._td_info.colSpan = 2;
		t._td_info.style.textAlign = "center";
		t._td_info.innerHTML = warning_html;
		t._td_info.className = "info_header";
		tr_info.appendChild(t._td_info);
		t._table.appendChild(tr_info);
		//Set the body
		var tr = document.createElement("tr");
		t._td_area = document.createElement("td");
		t._td_partners = document.createElement("td");
		t._td_partners.style.borderLeft = "1px solid #808080";
		//This td is initialized hidden
		t._td_partners.style.position = 'absolute';
		t._td_partners.style.visibility = 'hidden';
		t._td_partners.style.top = '-10000px';
		t._td_partners.style.left = '-10000px';
		tr.appendChild(t._td_area);//Contains the select area
		tr.appendChild(t._td_partners);//Contains the select partner
		t._table.appendChild(tr);
		t._partners_lists = new select_area_and_matching_organizations(t._td_partners, t.geographic_area, "Select this partner as host",t.host ? t.host.organization.id : null,"Selection");
		//Set the partners footer: add a create partner button
		t._create_partner_button = document.createElement("BUTTON");
		t._create_partner_button.className = "action";
		t._create_partner_button.innerHTML = "Create Partner";
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
	
	/**
	 * Create the part containing the geographic area
	 * @param {Function} ondone called when the geographic area is selected / updated 
	 */
	t._initArea = function(ondone) {
		while (t._td_area.childNodes.length > 0) t._td_area.removeChild(t._td_area.childNodes[0]);
		if(!t._country_id)
			return;
		if (t._initializing_area) { t._reinit_area = true; return; }
		t._initializing_area = true;
		require("geographic_area_selection.js", function() {
			new geographic_area_selection(t._td_area, t._country_id, t.geographic_area, function(area) {
				area.onchange = function() {
					var a = area.getSelectedArea();
					t.geographic_area = a;
					t._refreshTDPartners(t.geographic_area);
					//Reset the host if the area is changed
					t.host = null;
					//Update the buttons
					t._updatePopButtonsAndInfoRow();
				};
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
	
	/**
	 * Update the buttons and the information row, depending on the fact that a host is selected or not
	 * If an host is selected, the ok_html message is displayed and the finish button is enabled, and finish_latter_button is disabled
	 * If no host is selected, the errorHTML message is displayed and the finish_later button is enabled, and finish is disabled
	 * Note: the two buttons have the same onclick function. The only distinction is only made to help the user understanding that he must set a host to be done 
	 */
	t._updatePopButtonsAndInfoRow = function(){;
		//Update the buttons and the information row
		if(t.host != null){
			t._pop.disableButton("finish_later_button");
			t._pop.enableButton("finish_button");
			t._td_info.innerHTML = ok_html;
			t._td_info.style.color = "green";
		} else {
			t._pop.disableButton("finish_button");
			t._pop.enableButton("finish_later_button");
			t._td_info.innerHTML = warning_html;
			t._td_info.style.color = "red";
			
		}
	};
	
	/**
	 * Method called when a partner row is selected
	 * If the row contains several addresses, the user is invited to pick one of the addresses. In that case, the different addresses fields are filled up calling the contact#get_address service
	 * Else the partner row is selected
	 * This method updates the host, host_address, and host_name attributes
	 * @param {Object} row_data containing three attributes:<ul><li><code>id</code> {String} the host ID</li><li><code>name</code> {String} the host name</li><li><code>addresses</code> {array} array of the all the addresses linked to the row</li></ul>
	 */
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
				t.host = null;
				t._partners_lists.refresh(t.geographic_area);
			};
			var head = document.createElement("div");
			head.appendChild(document.createTextNode("This partner has several addresses in this area, please pick one:"));
			content.appendChild(head);
			require("address_text.js",function(){
				for(var i = 0; i < row_data.addresses.length; i++){
					var button = document.createElement("BUTTON");
					button.style.textAlign = "left";
					button.style.cursor = "pointer";
					var text = new address_text(row_data.addresses[i]);
					button.appendChild(text.element);
					button.address = row_data.addresses[i];
					button.onclick = function(){
						pop.onclose = null;
						t.host = new SelectionPartner(
							-1, // center_id: -1 to indicate it has been changed
							row_data, // organization
							true, // this is the new host
							this.address.id, // selected address
							[] // no contact point selected yet
						);
						t.geographic_area = this.address.geographic_area.id;
						pop.close();
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
			pop.show();
		} else {
			//Update the attributes
			t.host = new SelectionPartner(
				-1, // center_id: -1 to indicate it has been changed
				row_data, // organization
				true, // this is the new host
				row_data.addresses[0].id, // selected address
				[] // no contact point selected yet
			);
			t.geographic_area = row_data.addresses[0].geographic_area.id;
		}
		//Update the buttons
		t._updatePopButtonsAndInfoRow();
	};
	
	/**
	 * Method called when a partner row is unselected
	 * Reset all the attributes (host, host_address, host_name) and update the buttons
	 */
	t._onPartnerRowUnselected = function(){		
		//Reset the host attributes
		t.host = null;
		//Update the buttons
		t._updatePopButtonsAndInfoRow();
	};
	
	t._shown = false;//Attribute set as true when the right part of the table is displayed
	/**
	 * Hide or show the TD element containing the select_area_and_matching_organizations object
	 * @param {Number | NULL} area_id the ID of the area selected in the left TD. If null, the right TD element is hidden, else it is shown 
	 */
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
	
	/**
	 * Close the popup window after calling the onclose method, if any
	 */
	t._closePop = function(){
		if(onclose)
			onclose(t);
		t._pop.close();
	};	
	
	require(["popup_window.js","select_area_and_matching_organizations.js","partners_objects.js"],function(){
		t._init();
	});
}