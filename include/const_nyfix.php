<?php  @session_start();
ini_set("display_errors","Off");
/**
 * 根据是否含有'8=FIX.4.2'判断是否为NYFIX日志
 */
define('NYFIX_CUSTOMER', 'NYFXFSFD');
//fix请求和应答的特殊标记
define('NYFIX_IN','Received data on connection {NYFXFSFD} [');
define('NYFIX_OUT','Sending data on connection {NYFXFSFD} [');

define('NYFIX_TEST', 'BUYSIDE->SELLSIDE');
//fix请求和应答的特殊标记
define('NYFIX_TEST_IN','BUYSIDE->SELLSIDE, incoming> (');
define('NYFIX_TEST_OUT','BUYSIDE->SELLSIDE, outgoing> (');
?>