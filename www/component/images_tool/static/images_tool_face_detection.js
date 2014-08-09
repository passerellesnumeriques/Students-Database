if (typeof require != 'undefined')
	require(["ccv.js","face.js"]);

function images_tool_face_detection_init(onready) {
	require(["ccv.js","face.js"], function() {
		onready();
	});
}

function images_tool_face_detection(image, ondone) {
	var max_width = 300, max_height = 300;
	var canvas = document.createElement("CANVAS");
	var w = image.naturalWidth;
	var h = image.naturalHeight;
	if (w > max_width) {
		h = Math.floor(h*(max_width/w));
		w = max_width;
	}
	if (h > max_height) {
		w = Math.floor(w*(max_height/h));
		h = max_height;
	}
	canvas.width = w;
	canvas.height = h;
	var ctx = canvas.getContext("2d");
	ctx.drawImage(image, 0, 0, w, h);
	var detected;
	try {
		detected = ccv.detect_objects({ 
			"canvas" : ccv.grayscale(canvas),
			"cascade" : cascade,
			"interval" : 20,
			"min_neighbors" : 1,
		});
	} catch (e) {
		detected = [];
	}
	if (detected.length == 0) {
		if (ondone) ondone(0,null);
		return;
	}
	var face;
	if (detected.length == 1)
		face = detected[0];
	else {
		face = detected[0];
		for (var i = 1; i < detected.length; ++i) {
			if (detected[i].width+detected[i].height > face.width+face.height)
				face = detected[i];
		}
	}

	// upper part is middle of forehead => go up by 80%
	var y1 = face.y-face.height*0.8;
	// bottom part is mounth => go down by 75%
	var y2 = face.y+face.height*1.75;
	// left and right are on the eyes => enlarge by 50%
	var x1 = face.x-face.width*0.5;
	var x2 = face.x+face.width*1.5;
	
	face.x = x1;
	face.width = x2-x1;
	face.y = y1;
	face.height = y2-y1;
	
	// put back with aspect ratio;
	face.x /= w/image.naturalWidth;
	face.width /= w/image.naturalWidth;
	face.y /= h/image.naturalHeight;
	face.height /= h/image.naturalHeight;
	
	face.x = Math.floor(face.x);
	face.y = Math.floor(face.y);
	face.width = Math.floor(face.width);
	face.height = Math.floor(face.height);
	
//	var ctx = t.original_canvas.getContext("2d");
//	ctx.lineWidth = 2;
//	ctx.strokeStyle = 'rgba(230,87,0,0.8)';
//	ctx.beginPath();
//	ctx.rect(face.x, face.y, face.width, face.height);
//	ctx.stroke();
	
	if (ondone) ondone(detected.length, face);
}
