function autoresize_input(input, min_size) {
	input.mirror = document.createElement("SPAN");
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
	input.onkeydown = update;
	input.onkeyup = update;
	input.oninput = update;
	input.onpropertychange = update;
	var prev_onchange = input.onchange;
	input.onchange = function() {
		if (prev_onchange) prev_onchange();
		update();
	};
	update();
}