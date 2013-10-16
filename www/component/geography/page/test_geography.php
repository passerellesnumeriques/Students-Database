<?php
class page_test_geography extends Page{
	public function get_required_rights(){return array();}
	public function execute() {

		
		echo "<div id='test'></div>";
		
		// $this->add_javascript("/static/geography/geographic_area_selection.js");
		// $this->onload("new geographic_area_selection('test','PH');");
		$this->add_javascript("/static/data_model/editable_table.js");
		$this->add_javascript("/static/contact/edit_address.js");
		?>
		<script type='text/javascript'>
		// var div = document.getElementById('test');
		// var add = new get_address_text("1",function(add){add.getAddressText(div);});
		//var structure = null;
		// service.json("contact","get_address",{address_id:"1"},function(res){
			// if(!res) return;
			// new edit_address('test', res);
			// });
			
		// require("typed_field.js", function(){
			// require("field_address.js", function(){
				// var div = document.getElementById("test");
				// var editableAddress = new field_address("1", true, null, null, null);
				// div.appendChild(editableAddress.element);
			// });
		// });
		new editable_table("test","Postal_address",1,"field_address",null,null);
		
		
		//new editable_cell("test", "Postal_address", "geographic_area",1,"field_area", {country_code:"PH"},null);
		//service.json("contact","get_address_text",{address_id:1},function(res){alert("done");});
		</script>
		<?php
	
	}
}
?>