/**
 * @constructor
 * @param element HTML element on which the animation occurs
 * @param from starting value of the animation
 * @param to ending value of the animation
 * @param duration duration in milliseconds of the animation
 * @param start_time timestamp when the animation started
 * @param handler function called regularely to update the element according to the current value (between from and to): given parameters are (value,element)
 */
function Animation(element,from,to,duration,start_time,handler) {
	this.element = element;
	this.from = from;
	this.to = to;
	this.duration = duration;
	this.start_time = start_time;
	this.handler = handler;
	this.stopped = false;
}

/** @namespace */
animation = {
	animations: [],
	/**
	 * Create an animation
	 * @param element HTML element on which the animation occurs
	 * @param from starting value of the animation
	 * @param to ending value of the animation
	 * @param duration duration in milliseconds of the animation
	 * @param handler function called regularely to update the element according to the current value (between from and to): given parameters are (value,element)
	 * @returns {Animation}
	 */
	create: function(element, from, to, duration, handler) {
		var anim = new Animation(element,from,to,duration,new Date().getTime(),handler);
		this.animations.push(anim);
		handler(from, element);
		if (this.animations.length == 1) setTimeout("animation.evolve()",1);
		return anim;
	},
	/** Stop the given animation
	 * @param {Animation} anim
	 */
	stop: function(anim) {
		anim.stopped = true;
	},
	evolve: function() {
		var now = new Date().getTime();
		for (var i = 0; i < this.animations.length; ++i) {
			var anim = this.animations[i];
			if (anim.stopped) {
				this.animations.splice(i,1);
				i--;
				continue;
			}
			if (now - anim.start_time >= anim.duration) {
				this.animations.splice(i,1);
				i--;
				try { anim.handler(anim.to, anim.element); }
				catch (e) {
					window.top.log_exception(e, "Animation handler");
				}
				continue;
			}
			var time = now-anim.start_time;
			var new_value;
			if (anim.from && anim.from.length) {
				new_value = new Array();
				for (var vi = 0; vi < anim.from.length; ++vi) {
					var amount = anim.to[vi]-anim.from[vi];
					new_value.push(anim.from[vi]+(time*amount/anim.duration));
				}
			} else {
				var amount = anim.to-anim.from;
				new_value = anim.from+(time*amount/anim.duration);
			}
			try { anim.handler(new_value, anim.element); }
			catch (e) {
				window.top.log_exception(e, "Animation handler");
			}
		}
//		var now2 = new Date().getTime();
//		var next = 50 - (now2-now);
//		if (next < 0) next = 0;
		if (this.animations.length > 0) setTimeout("animation.evolve()",1);
	},
	
	/** Implemetation of an animation to modify the opacity of the element
	 * @param element
	 * @param duration in milliseconds
	 * @param end_handler called at the end of the animation
	 * @param start starting opacity (from 0 to 100)
	 * @param end ending opacity (from 0 to 100)
	 * @returns {Animation}
	 */
	fadeIn: function(element, duration, end_handler, start, end) {
		if (start == null) start = 0;
		if (end == null) end = 100; else end = Math.floor(end);
		return animation.create(element, start, end, duration, function(value, element) {
			value = Math.floor(value);
			try {
				if (value == 0)
					element.style.visibility = 'hidden';
				else {
					setOpacity(element,value/100);
					element.style.visibility = 'visible';
				}
			} catch (e) { window.top.log_exception(e); }
			if (value == end && end_handler != null) { 
				try { end_handler(element); }
				catch (e) { window.top.log_exception(e); }
				end_handler = null; 
			}
		});
	},
	/** Implemetation of an animation to modify the opacity of the element
	 * @param element
	 * @param duration in milliseconds
	 * @param end_handler called at the end of the animation
	 * @param start starting opacity (from 0 to 100)
	 * @param end ending opacity (from 0 to 100)
	 * @returns {Animation}
	 */
	fadeOut: function(element, duration, end_handler, start, end) {
		if (start == null) start = 100;
		if (end == null) end = 0;
		return animation.create(element, start, end, duration, function(value, element) {
			value = Math.floor(value);
			try {
				if (value == 0) {
					element.style.visibility = 'hidden';
				} else {
					setOpacity(element,value/100);
					element.style.visibility = 'visible';
				}
			} catch (e) { window.top.log_exception(e); }
			if (value == 0 && end_handler != null) {
				try { end_handler(element); }
				catch (e) { window.top.log_exception(e); }
				end_handler = null;
			}
		});
	},
	/** Implemetation of an animation to modify the color of the element
	 * @param element
	 * @param {Array} from [r,g,b]
	 * @param {Array} to [r,g,b]
	 * @param duration in milliseconds
	 * @returns {Animation}
	 */
	fadeColor: function(element, from, to, duration) {
		return animation.create(element, from, to, duration, function(value, element){
			element.style.color = "rgb("+Math.round(value[0])+","+Math.round(value[1])+","+Math.round(value[2])+")";
		});
	},
	/** Implemetation of an animation to modify the background color of the element
	 * @param element
	 * @param {Array} from [r,g,b]
	 * @param {Array} to [r,g,b]
	 * @param duration in milliseconds
	 * @returns {Animation}
	 */
	fadeBackgroundColor: function(element, from, to, duration) {
		return animation.create(element, from, to, duration, function(value, element){
			element.style.backgroundColor = "rgb("+Math.round(value[0])+","+Math.round(value[1])+","+Math.round(value[2])+")";
		});
	}
};
