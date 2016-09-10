<?php  @session_start();
ini_set("display_errors","Off");
/**
 * 根据是否含有'8=FIX.4.2'判断是否为NYFIX日志
 */
define('NYFIX_CUSTOMER', 'NYFXFSFD');
//五版_调用和返回log标志
define('NYFIX_IN','Received data on connection {NYFXFSFD} [');
define('NYFIX_OUT','Sending data on connection {NYFXFSFD} [');

define('NYFIX_TEST', 'NYFIXUAT');
//五版_调用和返回log标志
define('NYFIX_TEST_IN','Received data on connection {NYFIXUAT} [');
define('NYFIX_TEST_OUT','Sending data on connection {NYFIXUAT} [');
?>