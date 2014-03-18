function applicants_assignment_exam_main_page(container){
	var t = this;
	container = typeof container == "string" ? document.getElementById(container) : container;
	
	t._init = function(){
		//Set the info row
		var div1 = document.createElement("div");
		if(t._not_assigned == 0 && t._total != 0){
			div1.appendChild(document.createTextNode("All the applicants are assigned to an exam center!"));
			div1.style.fontColor = "green";
		} else if (t._total == 0){
			div1.appendChild(document.createTextNode("There is no applicant yet"));
			div1.style.fontStyle = "italic";
		} else {
			div1.appendChild(document.createTextNode("Still "+t._not_assigned+" / "+t._total+" applicants not assigned to any exam center"));
			div1.style.fontColor = "red";
		}
		container.appendChild(div1);
	};
	
	service.json("selection","applicant/get_assignment_figures",{},function(res){
		if(!res)
			return;
		t._total = res.total;
		t._not_assigned = res.not_assigned;
		t._init();
	});
}