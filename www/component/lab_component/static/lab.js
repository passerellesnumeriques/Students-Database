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
      
}