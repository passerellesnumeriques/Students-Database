/**
 * Custom search displays an input to search, and let the given provider do appropriate actions while the user is typing.
 * @param {Element} container where to put the input
 * @param {Number} min_chars minimum number of characters before to do something
 * @param {String} default_message text displayed when the input is empty (placeholder)
 * @param {Function} provider function called when the user is typing. It takes 2 parameters: the text, and a function to call when it has been processed and can be called again.
 */
function custom_search(container, min_chars, default_message, provider) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	/** Get the text in the input
	 * @returns {String} the text
	 */
	this.getInputValue = function() {
		return t.input.default_message ? "" : t.input.value;
	};
	
	/** Reset the input */
	this.reset = function() {
		this.input.default_message = true;
		this.input.value = default_message;
		this.input.className = "informative_text";
	};
	
	/** Creation of the input */
	this._init = function() {
		this.input = document.createElement('input');
		this.input.type = 'text';
		this.input.value = default_message;
		this.input.className = "informative_text";
		setBorderRadius(this.input,8,8,8,8,8,8,8,8);
		setBoxShadow(this.input,-1,2,2,0,'#D8D8F0',true);
		this.input.style.background = "#ffffff url('"+theme.icons_16.search+"') no-repeat 3px 1px";
		this.input.style.padding = "2px 4px 2px 23px";
		this.input.style.width = "130px";
		this.input.default_message = true;
		this.input.onfocus = function(){ 
			if (t.input.default_message) { 
				t.input.value = ""; t.input.className = ""; t.input.default_message = false; 
				layout.changed(container);
			} else 
				t.input.select();
		};
		this.input.onblur = function(){ 
			setTimeout(function(){
				if (!t) return;
				if (t.input.value == "") { 
					t.input.value = default_message; t.input.className = "informative_text"; t.input.default_message = true; 
					layout.changed(container);
				}; 
			},100); 
		};
		container.appendChild(this.input);
		
		this._provider_call = false;
		this._provider_recall = false;
		this._provider_last_string = "";
		this.input.onkeyup = function(e){
			t.input.default_message = false;
			if (t._provider_call) t._provider_recall = true;
			else t._callProvider();
		};
		
		this.input.ondomremoved(function() {
			provider = null;
			this.input = null;
			t = null;
		});
	};
	
	/** Call the provider */
	this._callProvider = function() {
		t._provider_call = true;
		t._provider_recall = false;
		setTimeout(function() {
			if (!t) return;
			if (t.input.value.length < min_chars) {
				t._provider_last_string = "";
				t._provider_call = false;
				t._provider_recall = false;
				return;
			} else {
				if (t.input.value != t._provider_last_string) {
					t._provider_last_string = t.input.value;
					t._provider_recall = false;
					provider(t.input.value, function() {
						t._provider_call = false;
						if (t._provider_recall)
							t._callProvider();
					});
				} else {
					t._provider_recall = false;
					t._provider_call = false;
				}
			}
		},10);
	};
	
	this._init();
}
