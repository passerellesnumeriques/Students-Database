function autoresize_input(input, min_size) {
	input.mirror = document.createElement("SPAN");
	if (input.style.fontSize) input.mirror.style.fontSize = input.style.fontSize;
	if (input.style.fontWeight) input.mirror.style.fontWeight = input.style.fontWeight;
	input.mirror.style.position = 'absolute';
	input.mirror.style.whiteSpace = 'pre';
	input.mirror.style.left = '0px';
	input.mirror.style.top = '-10000px';
	input.mirror.style.padding = "2px";
	document.body.appendChild(input.mirror);
	var update = function() {
		input.mirror.innerHTML = "";
		var s = input.value;
		input.mirror.appendChild(document.createTextNode(s));
		var w = getWidth(input.mirror);
		var min = min_size ? min_size * 10 : 15;
		if (w < min) w = min;
		input.style.width = w+"px";
	};
	var prev_onkeydown = input.onkeydown;
	input.onkeydown = function(e) { if (prev_onkeydown) prev_onkeydown(e); update(); };
	var prev_onkeyup = input.onkeyup;
	input.onkeyup = function(e) { if (prev_onkeyup) prev_onkeyup(e); update(); };
	var prev_oninput = input.oninput;
	input.oninput = function(e) { if (prev_oninput) prev_oninput(e); update(); };
	var prev_onpropertychange = input.onpropertychange;
	input.onpropertychange = function(e) { if (prev_onpropertychange) prev_onpropertychange(e); update(); };
	var prev_onchange = input.onchange;
	input.onchange = function(e) { if (prev_onchange) prev_onchange(e); update(); };
	update();
	input.autoresize = update;
}