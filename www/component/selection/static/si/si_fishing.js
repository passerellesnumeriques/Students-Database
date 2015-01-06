function fishing(container, info, applicant_id, can_edit) {
	container.style.padding = "3px";
	this.info = info;
	var t=this;
	this._initNoInfo = function() {
		container.removeAllChildren();
		var div = document.createElement("DIV");
		var span = document.createElement("SPAN");
		span.style.fontStyle = "italic";
		span.appendChild(document.createTextNode("Not Fishing"));
		div.appendChild(span);
		container.appendChild(div);
		if (can_edit) {
			var button = document.createElement("BUTTON");
			button.className = "action";
			button.style.marginLeft = "5px";
			button.innerHTML = "Create";
			div.appendChild(button);
			button.onclick = function() {
				t.info = {
					boat: null,
					net: null,
					income: null,
					income_freq: null,
					other: null
				};
				t._initFishing();
			};
		}
	};
	this._initFishing = function() {
		container.removeAllChildren();
		// Boat
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<img src='/static/selection/si/boat_16.png' style='vertical-align:bottom'/> <b>Boat:</b> ";
		if (can_edit) {
			var boat = new field_text(this.info.boat, true, {can_be_null:true,max_length:250});
			div.appendChild(boat.getHTMLElement());
			boat.onchange.addListener(function() { t.info.boat = boat.getCurrentData(); });
		} else if (this.info.boat)
			div.appendChild(document.createTextNode(this.info.boat));
		// Net
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<img src='/static/selection/si/fish_net.png' style='vertical-align:bottom'/> <b>Net:</b> ";
		if (can_edit) {
			var net = new field_text(this.info.net, true, {can_be_null:true,max_length:250});
			div.appendChild(net.getHTMLElement());
			net.onchange.addListener(function() { t.info.net = net.getCurrentData(); });
		} else if (this.info.net)
			div.appendChild(document.createTextNode(this.info.net));
		// Income
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<img src='/static/selection/si/money.png' style='vertical-align:bottom'/> <b>Income:</b> ";
		if (can_edit) {
			var income = new field_integer(this.info.income, true, {can_be_null:true,min:0});
			div.appendChild(income.getHTMLElement());
			income.onchange.addListener(function() { t.info.income = income.getCurrentData(); });
			div.appendChild(document.createTextNode(" Frequency: "));
			var freq = new field_text(this.info.income_freq, true, {can_be_null:true,max_length:25});
			div.appendChild(freq.getHTMLElement());
			freq.onchange.addListener(function() { t.info.income_freq = freq.getCurrentData(); });
		} else if (this.info.income > 0) {
			div.appendChild(document.createTextNode(this.info.income));
			if (this.info.income_freq)
				div.appendChild(document.createTextNode(" frequency: "+this.info.income_freq));
		}
		// Other
		var div = document.createElement("DIV");
		container.appendChild(div);
		div.innerHTML = "<b>Other Information:</b> ";
		var div = document.createElement("DIV");
		container.appendChild(div);
		if (can_edit) {
			var other = document.createElement("TEXTAREA");
			div.appendChild(other);
			if (this.info.other) other.value = this.info.other;
			other.onchange = function() { t.info.other = this.value; };
		} else if (this.info.other)
			div.appendChild(document.createTextNode(this.info.other));
		
		if (can_edit) {
			div = document.createElement("DIV");
			container.appendChild(div);
			var remove = document.createElement("BUTTON");
			remove.className = "action red";
			remove.innerHTML = "Remove Fishing Information";
			div.appendChild(remove);
			remove.onclick = function() {
				t.info = null;
				t._initNoInfo();
			};
		}
	};
	this.save = function(ondone) {
		var locker = lockScreen(null,"Saving Fishing Information...");
		service.json("selection","si/save_fishing",{applicant:applicant_id,fishing:this.info},function(res) {
			unlockScreen(locker);
			ondone();
		});
	};
	if (!info) this._initNoInfo();
	else this._initFishing();
}
