<?php

if ($com === 'com') {
	$wikidbconf = array(
		'host'		=> 'rdsnu7brenu7bre.mysql.rds.aliyuncs.com',
		'dbname'	=> '',
		'username'	=>'wikiuser',
		'password'	=>'123456'
	);
}else if($com === 'alpha') {
	$wikidbconf = array(
		'host'		=> '172.16.75.32',
		'dbname'	=> '',
		'username'	=>'root',
		'password'	=>'123456'
	);
}else {
	$wikidbconf = array(
		'host'		=> 'alyweb002.prod',
		'dbname'	=> '',
		'username'	=>'wikiuser',
		'password'	=>'123456'
	);
}

?>