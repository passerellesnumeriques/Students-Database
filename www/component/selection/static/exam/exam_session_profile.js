function exam_session_profile(container,date_container, supervisor_container, list_container, session, can_manage){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	if(typeof date_container == "string")
		date_container = document.getElementById(date_container);
	if(typeof supervisor_container == "string")
		supervisor_container = document.getElementById(supervisor_container);
	if(typeof list_container == "string")
		list_container = document.getElementById(list_container);
	
	t._init = function(){
		t._refreshDateContent();
		date_container.style.margin = "15px";
	};
	
	t._refreshDateContent = function(){
		if(!t._date_content){
			t._date_content = document.createElement("div");
			t._date_content.style.padding = "10px";
		}			
		if(!t._date_section){
			t._date_section = new section("","Date",t._date_content);
			date_container.appendChild(t._date_section.element);
			if(can_manage){
				//Set the set date button
				var b = document.createElement("div");
				b.className = "button";
				b.onclick = function(){
					require("pop_select_date_and_time.js",function(){
						service.json("selection","config/get_all_values_and_default",{name:"default_duration_exam_session"},function(res){
							if(!res){
								error_dialog('An error occured, functionality not available');
								return;
							}
							var all_values = [];
							for(var i = 0; i < res.all_values.length; i++){
								var duration_in_seconds = res.all_values[i].split(" ");
								duration_in_seconds = parseInt(duration_in_seconds[0]) * 60 * 60;
								all_values.push({name:res.all_values[i], value:duration_in_seconds});
							}
							var default_duration_seconds = res.default_value.split(" ");
							default_duration_seconds = parseInt(default_duration_seconds[0]) * 60 * 60;
							new pop_select_date_and_time(
								"Create an exam session",
								null,
								all_values,
								default_duration_seconds,
								function(event){
									session.event = event;
									//TODO save!
									t._refreshDateContent();
								},
								null,
								session.event
							);
						});
					});
				};
				b.innerHTML = "<img src = '/static/selection/IS/date_clock_picker.png'/> Set the date";
				t._date_section.addToolBottom(b);
			}			
		}
		while(t._date_content.firstChild)
			t._date_content.removeChild(t._date_content.firstChild);
		var row1 = document.createElement("div");
		row1.style.marginBottom = "5px";
		var row2 = document.createElement("div");
		//Set the first row, with the date field		
		var date = new Date(parseInt(session.event.start) * 1000);
		row1.appendChild(document.createTextNode("Date: "+dateToSQL(date)));
		//Set the second row with the start and end time
		var d_start = new Date(parseInt(session.event.start) * 1000);
		var _start = d_start.getHours()+":"+d_start.getMinutes();
		var start_field = new field_time(_start, false, {can_be_null:false});
		var start_elem = start_field.getHTMLElement();
		var d_end = new Date(parseInt(session.event.end) * 1000);
		var _end = d_end.getHours()+":"+d_end.getMinutes();
		var end_field = new field_time(_end, false, {can_be_null:false});
		var end_elem = end_field.getHTMLElement();
		row2.appendChild(document.createTextNode("Start time:"));
		start_elem.style.marginLeft = "3px";
		start_elem.style.marginRight = "30px";
		row2.appendChild(start_elem);
		row2.appendChild(document.createTextNode("End time:"));
		end_elem.style.marginLeft = "3px";
		row2.appendChild(end_elem);
		t._date_content.appendChild(row1);
		t._date_content.appendChild(row2);
	};
	
	require([["typed_field.js"],["pop_select_date_and_time.js","section.js","field_time.js"]],function(){
		t._init();
	});
}