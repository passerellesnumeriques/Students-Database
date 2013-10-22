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
		if (min_size)
			while (s.length < min_size) s += "w";
		input.mirror.appendChild(document.createTextNode(s));
		var w = getWidth(input.mirror);
		if (w < 15) w = 15;
		input.style.width = w+"px";
	};
	input.onkeydown = update;
	input.onkeyup = update;
	input.oninput = update;
	input.onpropertychange = update;
	input.onchange = update;
	update();
}