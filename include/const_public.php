<?php  @session_start();
ini_set("display_errors","Off");
/**
 * 根据是否含有'8=FIX.4.2'判断是否为NYFIX日志
 */
define('NYFIX_FLAG', '8=FIX.4.2');
//五版_调用和返回log标志
define('NYFIX_IN','Received data');
define('NYFIX_OUT','Sending data');


?>