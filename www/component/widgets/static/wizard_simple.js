if (typeof theme != 'undefined')
	theme.css("wizard.css");

function wizard_simple(container) {
	if (typeof container == 'string') container = document.getElementById(container);

	this.container = container;
	this.container.style.display = "flex";
	this.container.style.flexDirection = "column";
	this.header = document.createElement("DIV");
	this.header.style.flex = "none";
	this.header.className = "wizard_header";
	this.header.style.padding = "2px";
	this.content = document.createElement("DIV");
	this.content.style.backgroundColor = 'white';
	this.content.style.flex = "1 1 auto";
	this.footer = document.createElement("DIV");
	this.footer.className = 'wizard_buttons';
	this.footer.style.flex = "none";
	container.appendChild(this.header);
	container.appendChild(this.content);
	container.appendChild(this.footer);
	
	this.setTitle = function(icon_32, title) {
		while (this.header.childNodes.length > 0) this.header.removeChild(this.header.childNodes[0]);
		if (icon_32) {
			var img = document.createElement("IMG");
			img.src = icon_32;
			img.style.marginRight = "5px";
			this.header.appendChild(img);
		}
		this.header.appendChild(document.createTextNode(title));
	};
	
	this.resetButtons = function() { this.footer.removeAllChildren(); };
	this.addButtonControl = function(control) {
		this.footer.appendChild(control);
	};
	this.insertButtonControl = function(control) {
		if (this.footer.childNodes.length == 0)
			this.footer.appendChild(control);
		else
			this.footer.insertBefore(control, this.footer.childNodes[0]);
	};
	this.addContinueButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "Continue <img src='"+theme.icons_16.forward+"'/>";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	this.addNextButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "Next <img src='"+theme.icons_16.forward+"'/>";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	this.addPreviousButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "<img src='"+theme.icons_16.backward+"'/> Previous";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	this.addOkButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "<img src='"+theme.icons_16.ok+"'/> Ok";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	this.addFinishButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "<img src='"+theme.icons_16.ok+"'/> Finish";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	this.addCancelButton = function(onclick) {
		var button = document.createElement("DIV");
		button.className = "button";
		button.innerHTML = "<img src='"+theme.icons_16.cancel+"'/> Cancel";
		button.onclick = onclick;
		this.addButtonControl(button);
	};
	
	this.setContent = function(content) {
		content.style.width = "100%";
		content.style.height = "100%";
		this.content.removeAllChildren();
		this.content.appendChild(content);
	};
	this.resetContent = function() {
		this.content.removeAllChildren();
	};
}