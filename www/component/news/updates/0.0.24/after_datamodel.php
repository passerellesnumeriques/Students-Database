<?php 
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `News` SET `category`='students' WHERE `category`='batch'");
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `News` SET `type`='activity' WHERE 1");
SQLQuery::getDataBaseAccessWithoutSecurity()->execute("UPDATE `News` SET `type`='update' WHERE `html`='Welcome in PN Students Management Software'");
?>