function custom_search(container, min_chars, default_message, provider) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;

	this.getInputValue = function() {
		return t.input.default_message ? "" : t.input.value;
	};
	
	this.reset = function() {
		this.input.default_message = true;
		this.input.value = default_message;
		this.input.className = "informative_text";
	};
	
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
				layout.invalidate(container);
			} else 
				t.input.select();
		};
		this.input.onblur = function(){ 
			setTimeout(function(){
				if (t.input.value == "") { 
					t.input.value = default_message; t.input.className = "informative_text"; t.input.default_message = true; 
					layout.invalidate(container);
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
			else t._call_provider();
		};
		
	};
	
	this._call_provider = function() {
		t._provider_call = true;
		t._provider_recall = false;
		setTimeout(function() {
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
							t._call_provider();
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
