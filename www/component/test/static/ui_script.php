<?php
header("Content-Type: text/javascript");
readfile("component/".$_GET["component"]."/test/ui/".$_GET["path"].".js"); 
?>