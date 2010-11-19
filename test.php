<?php

	require_once 'application/libraries/Sql/Query/Builder/Abstract.php';
	require_once 'application/libraries/Sql/Query/Builder/Sqlsrv.php';
	require_once 'application/libraries/Sql/Query/Builder/Mysql.php';
	require_once 'application/libraries/Sql/Query/Builder/Factory.php';
	
	define('DRIVER', 'SQLSRV');
	
	$q1 = Sql_Query_Builder_Factory::make(DRIVER);
	$q1->select('*', 'tcm_config')
	   ->where('name = ?', array('foo'))
	   ->limit(10, 20);
	
	$q2 = Sql_Query_Builder_Factory::make(DRIVER);
	$q2->select('*', array('`tcm_mod_articles`', $q1))
	   ->where('DATEADD(second, 45152000, [date]) >= UTC_TIMESTAMP()')
	   ->order(array('published' => 'ASC', 'date' => 'DESC'))
	   ->limit(5, 10);
	
	var_dump($q2->build());
	
?>