function exam_status(container){
	if(typeof(container) == "string")
		container = document.getElementById(container);
		
	var t = this;
	t.table = document.createElement("table");
	
	t._init = function(){
		var td11 = document.createElement("td");
		var td12 = document.createElement("td");
		td11.innerHTML = "<font color='#808080'><b>Subjects created:</b></font>";
		td12.innerHTML = t.number_exams;
		var tr = document.createElement("tr");
		tr.appendChild(td11);
		tr.appendChild(td12);
		t.table.appendChild(tr);
		container.appendChild(t.table);
	}
	
	
	service.json("selection","exam_subject/status",{},function(res){
		if(res){
			t.number_exams = res.number_exams;
			t._init();
		}
	});
}