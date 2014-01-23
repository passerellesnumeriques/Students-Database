function IS_status(container){
	if(typeof(container) == "string")
		container = document.getElementById(container);	
	var t = this;
	t.table = document.createElement("table");

	t._init = function(){
		var tr1 = document.createElement("tr");
		var tr2 = document.createElement("tr");
		var tr3 = document.createElement("tr");
		var td12 = document.createElement("td");
		var td11 = document.createElement("td");
		
		td11.innerHTML = "<font color='#808080'><b>Planned / Conducted:</b></font>";
		td11.style.textAlign = "center";
		td11.style.paddingBottom = "10px";
		td12.innerHTML = t.number_IS;
		td12.style.paddingBottom = "10px";
		// td12.style.paddingRight = "15px";
		// td12.style.paddingLeft = "10px";
		td12.style.textAlign = "left";
		tr1.appendChild(td11);
		tr1.appendChild(td12);
		var td13 = document.createElement("td");
		var td14 = document.createElement("td");
		td13.innerHTML = "<font color='#808080'><b>All partners:</b></font>";
		td13.style.paddingBottom = "10px";
		td13.style.textAlign = "center";
		td14.innerHTML = t.partners;
		td14.style.paddingBottom = "10px";
		tr1.appendChild(td13);
		tr1.appendChild(td14);
		t.table.appendChild(tr1);
		
		var td21 = document.createElement("td");
		var td22 = document.createElement("td");
		var td23 = document.createElement("td");
		var td24 = document.createElement("td");
		
		if(t.separate_boys_girls){
			td21.innerHTML = "<font color='#808080'><b>Girls expected:</b></font>";
			td21.style.textAlign = "center";
			td22.innerHTML = t.girls_expected;
			td23.innerHTML = "<font color='#808080'><b>Girls real:</b></font>";
			td23.style.textAlign = "center";
			td24.innerHTML = t.girls_real;
			tr2.appendChild(td21);
			tr2.appendChild(td22);
			tr2.appendChild(td23);
			tr2.appendChild(td24);
			
			var td31 = document.createElement("td");
			var td32 = document.createElement("td");
			var td33 = document.createElement("td");
			var td34 = document.createElement("td");
			td31.innerHTML = "<font color='#808080'><b>Boys expected:</b></font>";
			td31.style.textAlign = "center";
			td32.innerHTML = t.boys_expected;
			td33.innerHTML = "<font color='#808080'><b>Boys real:</b></font>";
			td33.style.textAlign = "center";
			td34.innerHTML = t.boys_real;
			tr3.appendChild(td31);
			tr3.appendChild(td32);
			tr3.appendChild(td33);
			tr3.appendChild(td34);
			
			t.table.appendChild(tr2);
			t.table.appendChild(tr3);
		} else {
			/* in that case, all the data are stored into the boys figures fields */
			td21.innerHTML = "<font color='#808080'><b>Attendees expected:</b></font>";
			td21.style.textAlign = "center";
			td22.innerHTML = t.boys_expected;
			td23.innerHTML = "<font color='#808080'><b>Attendees real:</b></font>";
			td23.style.textAlign = "center";
			td24.innerHTML = t.boys_real;
			tr2.appendChild(td21);
			tr2.appendChild(td22);
			tr2.appendChild(td23);
			tr2.appendChild(td24);
			t.table.appendChild(tr2);
		}
		t.table.style.width = "100%";
		container.appendChild(t.table);
	}
	
	service.json("selection","IS/status",{},function(res){
		if(res){
			t.boys_real = res.boys_real;
			t.girls_real =  res.girls_real;
			t.boys_expected = res.boys_expected;
			t.girls_expected = res.girls_expected;
			t.partners = res.partners;
			t.number_IS = res.number_IS;
			t.separate_boys_girls = res.separate_boys_girls;
			t._init();
		}
	});
}