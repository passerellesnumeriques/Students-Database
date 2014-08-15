function initLab(){
     
      $("#lab_button").click(function(){
    
    /* Step 3 : getting a data from the server using a service */
    
    
    var name="Anna";
    
     /* cf www\component\application\static\service.js */
   service.json("lab_component","get_country",name,function(country){
               /* display result into home page */
          $("#lab_result").html(name + " comes from "+country);
   
        });
    
    
      });
      
      
      /* Lab 2 (DataDisplay) : just a simple datalist */
        var dl=new data_list(
                   "lab_list",
                   "LabPeople", null,
                   [
                           "People Information.First Name",
                           "People Information.Last Name",
                           "People Information.Gender",
                           "People Information.Age",
                           "Football.Field Position",
                           "Football.Team Name",
                           "Football.Team Color"
                   ],
                   [],
                   -1,
                   function(list) {
                    // once the data_list has been created we can do things here
                   }
           );
        
        dl.setTitle('<b>Lab 2 (DataDisplay) . Example with data_list</b>');
}