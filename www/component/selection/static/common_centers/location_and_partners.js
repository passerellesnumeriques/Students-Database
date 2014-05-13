function location_and_partners(popup, section_location, section_other_partners, center_type, center_id, geographic_area_text, partners) {
	
	this.center_id = center_id;
	this.geographic_area_text = geographic_area_text;
	this.partners = partners;
	
	// Functionalities
	
	this.getHostPartner = function() {
		for (var i = 0; i < this.partners.length; ++i) {
			if (!this.partners[i].host) continue;
			return this.partners[i];
		}
		return null;
	};
	this.getHostAddress = function() {
		var host = this.getHostPartner();
		if (host != null) {
			for (var i = 0; i < host.organization.addresses.length; ++i)
				if (host.organization.addresses[i].id == host.host_address_id)
					return host.organization.addresses[i];
		}
		return null;
	};
	this.setHostPartner = function(host) {
		// unselect previous host
		for (var i = 0; i < this.partners.length; ++i)
			if (this.partners[i].host) {
				this.partners[i].host = false;
				this.partners[i].host_address = null;
				break;
			}
		this.geographic_area_text = null;
		if (host == null) return; // no host selected
		for (var i = 0; i < host.organization.addresses.length; ++i)
			if (host.organization.addresses[i].id == host.host_address_id) {
				this.geographic_area_text = host.organization.addresses[i].geographic_area;
				break;
			}
		// check if already present in the partners list
		for (var i = 0; i < this.partners.length; ++i) {
			if (this.partners[i].organization.id == host.organization.id) {
				// it is present, update it
				this.partners[i].host = true;
				this.partners[i].host_address_id = host.host_address_id;
				return;
			}
		}
		// not yet a partner, add it in the list
		this.partners.push(host);
	};
	
	this.dialogSelectLocation = function() {
		var t=this;
		require("popup_select_area_and_partner.js", function() {
			var host = t.getHostPartner();
			new popup_select_area_and_partner(
				t.geographic_area_text ? t.geographic_area_text.id : null,
				host,
				function(selected) {
					if (selected.host) {
						// a host is selected
						if (selected.host.center_id == -1) {
							// the host changed
							selected.host.center_id = t.center_id;
							t.setHostPartner(selected.host);
							window.pnapplication.dataUnsaved("selection_location");
							t._refreshAddress();
							t._refreshHost();
							t._refreshPartners();
							return;
						}
						return; // no change
					} else {
						// no host selected
						if (host != null) {
							// one was previously selected: remove it
							t.setHostPartner(null);
							window.pnapplication.dataUnsaved("selection_location");
							t._refreshHost();
							t._refreshPartners();
						}
						if (selected.geographic_area) {
							// an area is selected
							if (t.geographic_area_text == null || t.geographic_area_text.id != selected.geographic_area) {
								// it is a different one
								t.geographic_area_text = null; // temporary
								popup.freeze();
								window.top.geography.getGeographicAreaText(window.top.default_country_id, selected.geographic_area, function(geo) {
									t.geographic_area_text = geo;
									t._refreshAddress();
									popup.unfreeze();
								});
							}
						} else {
							if (t.geographic_area_text) {
								// there was one before: unselect it
								geographic_area_text = null;
								window.pnapplication.dataUnsaved("selection_location");
							}
						}
						t._refreshAddress();
					}
				},
				"<img style = 'vertical-align:bottom;'src = '"+theme.icons_16.info+"'/> <i>To be fully completed a host partner must be attached</i>",
				"<img style = 'vertical-align:bottom;'src = '"+theme.icons_16.info+"'/> <i>Location field is fully completed!</i>"
			);
		});
	};

	// Location
	
	/** Initialize the Location section with the address and the host partner */
	this._initLocation = function() {
		// if this is a new center, mark it as not saved
		if (center_id == -1)
			window.pnapplication.dataUnsaved("selection_location");
		// Location section is composed of 2 elements: the address / geographic area, and the host partner
		this._address_container = document.createElement("DIV");
		section_location.content.appendChild(this._address_container);
		this._address_container.style.padding = "10px";
		this._host_container = document.createElement("DIV");
		section_location.content.appendChild(this._host_container);
		// buttons
		this._button_set_location = document.createElement("BUTTON");
		this._button_set_location.className = "action";
		this._button_set_location.innerHTML = "Select a location";
		this._button_set_location.t = this;
		this._button_set_location.onclick = function() { this.t.dialogSelectLocation(); };
		section_location.addToolBottom(this._button_set_location);
		// refresh with actual values
		this._refreshAddress();
	};
	/** Refresh the address part in the Location section */
	this._refreshAddress = function() {
		// reset content
		this._address_container.innerHTML = "";
		// 3 possibilities: complete address (from the host), only geographic area, or nothing
		var address = this.getHostAddress();
		if (address != null) {
			// we have an address
			var t=this;
			require("address_text.js",function() {
				var a = new address_text(address);
				t._address_container.appendChild(a.element);
				layout.invalidate(section_location.element);
			});
		} else if (this.geographic_area_text != null) {
			// we only have a geographic area
			this._address_container.innerHTML = this.geographic_area_text.text+"<br/><img src='"+theme.icons_16.warning+"' style='vertical-align:bottom'/> <i style='color:#FF8000'>Not complete: please select a hosting partner</i>";
		} else {
			// nothing
			this._address_container.innerHTML = "<center style='color:red'><img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> <i>Please select a location</i></center>";
		}
		layout.invalidate(section_location.element);
	};
	this._refreshHost = function() {
		
	};
	
	// Other Partners
	
	this._refreshPartners = function() {
		
	};
	
	// Initialization
	this._initLocation();
}