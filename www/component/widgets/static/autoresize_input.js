function autoresize_input(input) {
	this.input = input;
	this.mirror = document.createElement("SPAN");
	this.mirror.style.position = 'absolute';
	this.mirror.style.whiteSpace = 'pre';
	this.mirror.style.left = '0px';
	this.mirror.style.top = '-10000px';
	this.mirror.style.padding = "2px";
	document.body.appendChild(this.mirror);
	this.update = function() {
		this.mirror.innerHTML = "";
		this.mirror.appendChild(document.createTextNode(this.input.value));
		var w = getWidth(this.mirror);
		if (w < 15) w = 15;
		this.input.style.width = w+"px";
	};
	var t=this;
	var u=function(){t.update();};
	input.onkeydown = u;
	input.onkeyup = u;
	input.oninput = u;
	input.onpropertychange = u;
	input.onchange = u;
	u();
}