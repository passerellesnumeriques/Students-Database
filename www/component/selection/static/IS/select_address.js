function select_address(container, data, organization_contacts, can_manage){
	var t = this;
	t.data = data;
	t.address = {};
	t.address.id = null;
	t.address.country_id = null;
	t.address.country_code = null;
	t.address.geographic_area = null;
	t.address.street_name = null;
	t.address.street_number = null;
	t.address.building = null;
	t.address.unit = null;
	t.address.additional = null;
	t.address.address_type = null;
	
	require(["popup_window.js","address_text.js","edit_address.js","section.js"],function(){
		t.container_of_section_content = document.createElement("div");
		t.section = new section("/static/contact/address_16.png","Location",t.container_of_section_content,false);
		container.appendChild(t.section.element);
		t._setTableAddress();
	});
	
	t.getData = function(){
		return t.data;
	}
	
	t._setTableAddress = function(){
		t.table_address = document.createElement("table");
		t.table_address.style.width = "100%";
		
		// var theader = document.createElement("thead");
		var tbody = document.createElement("tbody");
		var tr = document.createElement("tr");
		var th_header = document.createElement("th");
		var tr_body = document.createElement("tr");
		var td_body = document.createElement("td");
		var tfoot = document.createElement("tfoot");
		var host = t.getHostInData();
		
		/* One address is set in the data object (and exists into the database)*/
		if(host != null && host.index == null){
			// get the address object
			if(t.address.id == null && host.id != -1 && host.id != "-1"){
				service.json("contact","get_address",{id:host.id},function(address){
					if(!address){		
						t.address.id = -1;
					} else {
						t.address = address;
					}
					var text = new address_text(t.address);
					td_body.appendChild(text.element);
					if(can_manage) t._addSetAddressButton(tfoot, host);
				});
			} else {
				var text = new address_text(t.address);
				td_body.appendChild(text.element);
				if(can_manage) t._addSetAddressButton(tfoot, host);
			}
		}
		/* No address is set in the data object but one host is set */
		if(host != null && host.index != null){
			t.address = t._getAddressFromPartnerAndAddressIdInOrganization_contacts(data.partners[host.index].organization, host.id);
			if(t.address != null && t.address != {}){
				var text = new address_text(t.address);
				td_body.appendChild(text.element);
				if(can_manage) t._addSetAddressButton(tfoot, host);
			}
		}		
		/* No address nor host is set */
		if(host == null){
			td_body.innerHTML = "No location is set for this Information Session";
			td_body.style.fontStyle = "italic";
			if(can_manage) t._addSetAddressButton(tfoot, host);
		}
		
		/* Add remove address button */
		if(can_manage && host != null) t._addRemoveAddressButton(tfoot);
		tfoot.style.display = "inline-block";
		
		
		// th_header.innerHTML = "<img src = '/static/contact/address_16.png' style = 'vertical-align:bottom'/> Location";
		// tr.appendChild(th_header);
		// theader.appendChild(tr);
		tr_body.appendChild(td_body);
		tbody.appendChild(tr_body);
		// t.table_address.appendChild(theader);
		t.table_address.appendChild(tbody);
		t.table_address.appendChild(tfoot);
		// setCommonStyleTable(t.table_address,th_header,"#DADADA");
	
		t.container_of_section_content.appendChild(t.table_address);
	}
	
	t.resetTableAddress = function(div_locker){
		t.container_of_section_content.removeChild(t.table_address);
		delete t.table_address;
		t._setTableAddress();
		if(typeof(div_locker) != "undefined" && div_locker != null) unlock_screen(div_locker);
	}
	
	t._addSetAddressButton = function(cont, host){
		var div = document.createElement("div");
		// var host = t.getHostInData();
		if(host == null){
			div.innerHTML = "<img src = '"+theme.icons_16.search+"' style ='vertical-align:bottom'/> Set a location";
			div.className = "button";
		}
		if(host != null){
			div.innerHTML = "<img src = '"+theme.icons_16.search+"'/> Change the location";
			div.className = "button";
		}
		div.onclick = function(){
			if(data.partners.length > 0 && t._noAllAddressesEmptyInOrganization_contacts()) t._setOrSelectPartnerAddressDialog(host);
			else t._setAddressNoPartnerDialog(host);
		};
		cont.appendChild(div);
	}
	
	t._addRemoveAddressButton = function(cont){
		var div = document.createElement("div");
		div.innerHTML = "<img src = '"+theme.icons_16.remove+"'/> Unset location";
		div.onmouseover = function(){div.innerHTML = "<img src = '"+theme.icons_16.remove_black+"'/> Unset location";};
		div.onmouseout = function(){div.innerHTML = "<img src = '"+theme.icons_16.remove+"'/> Unset location";};
		div.className = "button";
		div.onclick = function(){
			confirm_dialog("<center>Do you really want to unset <br/>the location of this Information Session?</center>",function(res){
				if(res){
					var div_locker = lock_screen();
					t._removeAddress(div_locker);
				}
			});
		};
		cont.appendChild(div);
	}
	
	t._removeAddress = function(div_locker){
		/* Reset data object */
		data.address = null;
		var host = t.getHostInData();
		if(host != null && host.index != null){
			data.partners[host.index].host = null;
			data.partners[host.index].host_address = null;
		}
		/* Reset t.address */
			t.address = {};
			t.address.id = null;
			t.address.country_id = null;
			t.address.country_code = null;
			t.address.geographic_area = null;
			t.address.street_name = null;
			t.address.street_number = null;
			t.address.building = null;
			t.address.unit = null;
			t.address.additional = null;
			t.address.address_type = null;
		/* Reset the table */
		t.resetTableAddress(div_locker);
	}
	
	t._setOrSelectPartnerAddressDialog = function(host){
		var cont = document.createElement("div");
		var cont_manually_set = document.createElement("div");
		var pop = new popup_window("Set the location - 1/2",theme.icons_16.question,cont);
		
		var set_address = "<img src = '"+theme.icons_16.search+"'/> Manually choose";
		var select_address = "<img src = '"+theme.icons_16.edit+"'/> Select from partners";
		cont.innerHTML = "<center>You can set a pre-selected address using the partners' ones <br/> <i>or</i> manually choose an other address</center> <br/><i>Note: if no address is set to one partner, this partner wont be displayed in the list</i>";
		pop.addButton(set_address,"set_address_button",function(){
			pop.close();
			t._manuallySetStepTwo(cont_manually_set,host);
		});
		pop.addButton(select_address,"select_address_button",function(){
			pop.close();
			t._popSelectAddressFromPartner(host);
		});
		// if (host != null){
			// cont.innerHTML = "<center>You can set a pre-selected address using the partners ones <br/> <i>or</i> manually choose an other address<br/><i>Note: If you go on next step, the current host will be unselected</i></center>";
		// }
		
		pop.show();
	}
	
	t._manuallySetStepTwo = function(cont_manually_set,host){
		var pop_manually_set = new popup_window("Set the location - 2/2 - <i>Manually set the address</i>","/static/contact/address_16.png",cont_manually_set);
		// if(data.address == null || data.address == "null"){
			// data.address = {};			
			// data.address.id = -1;
			// data.address.country_id = null;
			// data.address.country_code = null;
			// data.address.area_id = null;
			// data.address.street_name = null;
			// data.address.street_number = null;
			// data.address.building = null;
			// data.address.unit = null;
			// data.address.additional = null;
			// data.address.address_type = null;
		// }	
		pop_manually_set.addOkCancelButtons(function(){
			pop_manually_set.close();
			var div_locker = lock_screen();
			t._addAddressManually(host, div_locker);
		});
		new edit_address(cont_manually_set,t.address);
		pop_manually_set.show();
	}
	
	t._addAddressManually = function(host, div_locker){
		if(host != null && host.index != null){
			//set the host attribute of the corresponding partner to null
			data.partners[host.index].host = null;
			data.partners[host.index].host_address = null;
		}
		//update data.address, only in the case of the address does not exist
		if(data.address == null) data.address = -1;
		/* Reset the table */
		t.resetTableAddress(div_locker);
	}
	
	t._setAddressNoPartnerDialog = function(host){
		var cont = document.createElement("div");
		var pop = new popup_window("Set the location - 1/2",theme.icons_16.info,cont);
		cont.innerHTML = "<center> This information session as no partner yet <br/> or no address is set to any partner, <br/> so you can only manually set the location</center>";
		pop.addOkCancelButtons(function(){
			var cont_manually_set = document.createElement("div");
			pop.close();
			t._manuallySetStepTwo(cont_manually_set,host);
		});
		pop.show();
	}
	
	t._popSelectAddressFromPartner = function(host){
		var cont = document.createElement("div");
		//var pop = new popup_window("Set the location - 2/2 - <i>Select from partners addresses</i>","/static/contact/address_16.png",cont);
		var pop = new popup_window("Set the location - 2/2","/static/contact/address_16.png",cont);
		var table_partners = document.createElement("table");
		t._setTablePartnersAddresses(table_partners, pop);
		cont.appendChild(table_partners);
		pop.show();
	}
	
	t._setTablePartnersAddresses = function(table_partners, pop){
		for(var i = 0; i < organization_contacts.length; i++){
			if(typeof(organization_contacts[i].addresses[0]) != "undefined" && organization_contacts[i].addresses[0].id != null && organization_contacts[i].addresses[0].id != "null"){
				var tr_header_table_partners = document.createElement("tr");
				var th_table_partners = document.createElement("th");
				var name = data.partners[t._findPartnerIndexInData(organization_contacts[i].id)].organization_name;
				th_table_partners.innerHTML = name;
				tr_header_table_partners.appendChild(th_table_partners);
				table_partners.appendChild(tr_header_table_partners);
				var table = document.createElement("table");
				table.style.border = "1px solid";
				table.style.width = "100%";
				setBorderRadius(table,5,5,5,5,5,5,5,5);
				table.style.borderColor = "#0F6CA2";
				table.style.marginBottom = "15px";
				var tr_partner = document.createElement("tr");
				var td_partner = document.createElement("td");
				td_partner.appendChild(table);
				tr_partner.appendChild(td_partner);
				for(var j = 0; j < organization_contacts[i].addresses.length; j++){
					var tr = document.createElement("tr");
					var td = document.createElement("td");
					var text = new address_text(organization_contacts[i].addresses[j]);
					td.onclick = function(){
						var temp_this = this;
						pop.close();
						var div_locker = lock_screen();
						/* Unset data.address */
						data.address = null;
						/* Change the host */
						var host = t.getHostInData();
						if(host != null && host.index != null){
							data.partners[host.index].host = null;
							data.partners[host.index].host_address = null;
						}
						var index = t._findPartnerIndexInData(temp_this.organization_id);
						data.partners[index].host = true;
						data.partners[index].host_address = temp_this.address_id;
						/* Reset the table */
						t.resetTableAddress(div_locker);
					};
					td.address_id = organization_contacts[i].addresses[j].id;
					td.organization_id = organization_contacts[i].id;
					td.appendChild(text.element);
					td.className = "button";
					tr.appendChild(td);
					table.appendChild(tr);
				}
				table_partners.appendChild(tr_partner);
			}
		}
	}
	
	t._findPartnerIndexInData = function(organization_id){
		var index = null;
		for(var i = 0; i < data.partners.length; i++){
			if(data.partners[i].organization == organization_id){
				index = i;
				break;
			}
		}
		return index;
	}
	
	/**
	 * @method #IS_profile#getHost
	 * @return {host} if no host nor postal_address is set, host = null
	 * Else if address == null but one host is set as true, host.id = the id of the host address, host.index = the index in the data.partners array
	 * Else if address != null, host.id = the id of the postal_address, host.index = null
	 */
	t.getHostInData = function(){
		var host = {};
		if(data.address != null && data.address != {}){
			host.id = data.address;
			host.index = null;
		} else if (data.address == null){
			var found = false;
			for(var i = 0; i < data.partners.length; i++){
				if(data.partners[i].host == true && data.partners[i].host_address != null){
					host.index = i;
					host.id = data.partners[i].host_address;
					found = true;
					break;
				}
			}
			if(!found) host = null;
		}
		return host;
	}
	
	t._noAllAddressesEmptyInOrganization_contacts = function(){
		var not_empty = false;
		for(var i = 0; i < organization_contacts.length; i++){
			if(organization_contacts[i].addresses.length > 0){
				for(var j = 0; j < organization_contacts[i].addresses.length; j++){
					if(typeof(organization_contacts[i].addresses[j]) != "undefined" && organization_contacts[i].addresses[j].id != null && organization_contacts[i].addresses[j].id != "" && organization_contacts[i].addresses[j].id != "null"){
						not_empty = true;
						break;
					}
				}
				if(not_empty) break;
			}
		}
		return not_empty;
	}
	
	t._getAddressFromPartnerAndAddressIdInOrganization_contacts= function(partner_id, address_id){
		var address = null;
		var index = null;
		for(var i = 0; i < organization_contacts.length; i++){
			if(organization_contacts[i].id == partner_id){
				index = i;
				break;
			}
		}
		if(index != null){
			for(var j = 0; j < organization_contacts[i].addresses.length; j++){
				if(organization_contacts[i].addresses[j].id == address_id){
					address = organization_contacts[i].addresses[j];
					break;
				}
			}
		}
		return address;
	}
	
	/**
	 * function called in the case of a partner is removed from the table_partners
	 * to check if it was the host, to transfer its address to the custom address attribute
	 */
	t.transferPartnerAddressToHostAddress = function(new_partners){
		//Try to get the host (old_partners is the same instance as data.partners)
		var host = t.getHostInData();
		if(host!= null && host.index != null){
			var partner_id = data.partners[host.index].organization;
			//host is found: check it was removed from the new_partners array
			var removed = true;
			for(var i = 0; i < new_partners.length; i++){
				if(new_partners[i].organization == partner_id){
					removed = false;
					break;
				}
			}
			if(removed){
				//perform the transfer
				data.address = host.id;
			}
		}
	}
	
	t.setCustomAddress = function(address_id){
		data.address = address_id;
	}
	
	t.getAddressObject = function(){
		return t.address;
	}
}