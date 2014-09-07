<?php 
/* @var $model DataModel */
$model->addTable("DataImportTemplate")
	->addPrimaryKey("id")
	->addString("type",100,1,false,false)
	->addString("name",100,1,false,false)
	->addString("root_table",100,1,false,false)
	->addInteger("sub_model",32,1,null,true,false)
	->addEnum("template_type", array("multiple","single"), false, false)
	;
$model->addTable("DataImportTemplateMultiple")
	->addPrimaryKey("id")
	->addForeignKey("template", "DataImportTemplate", true, false, true,false,false)
	->addString("data_path",2000,1,false,false)
	->addString("data_name",200,1,false,false)
	->addInteger("sub_index",8,0,null,true,false)
	->addInteger("index",16,0,null,true,false)
	->addString("sheet",200,0,true,false)
	->addInteger("column",16,0,null,true,false)
	->addInteger("row_start",16,0,null,true,false)
	->addString("value",2000,1,true,false)
	;
?>