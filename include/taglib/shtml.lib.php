<?php
if(!defined('DEDEINC'))
{
    exit("Request Error!");
}
/**
 * shtml
 */


function lib_shtml(&$ctag,&$refObj)
{
	$attlist = "file|";
    FillAttsDefault($ctag->CAttribute->Items,$attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);

	return '<!--#include virtual="'.$file.'"-->';

}
