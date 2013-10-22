<?php
/**
 * Return an array with all the possibles configs for selection component
 * Each config is defined by an array which contains:
 * [0]: config name
 * [1]: explanations: will be displayed on the manage_config page
 * [2]: default value. When a new selection campaign will be created, the config will be set with the default values
 * [3]: array containing all the possible values
 * [4]: {string} type
 */
return array(
	array("separate_boys_girls_IS","Separate the boys and girls figures for the information sessions",false,array(true,false),"boolean"),
);
?>