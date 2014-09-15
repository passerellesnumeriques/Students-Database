<?php 
global $local_domain, $db_config;
$db_config["prefix"] = "students_";
$f = fopen("install_config.inc","w");
fwrite($f, "<?php\n");
fwrite($f, "global \$local_domain, \$db_config;\n");
fwrite($f, "\$local_domain = \"$local_domain\";\n");
fwrite($f, "\$db_config = array(\n");
fwrite($f, "\t\"type\"=>\"MySQL\",\n");
fwrite($f, "\t\"server\"=>\"".$db_config["server"]."\",\n");
fwrite($f, "\t\"user\"=>\"".$db_config["user"]."\",\n");
fwrite($f, "\t\"password\"=>\"".$db_config["password"]."\",\n");
fwrite($f, "\t\"prefix\"=>\"students_\"\n");
fwrite($f, ");\n");
fwrite($f, "?>\n");
?>