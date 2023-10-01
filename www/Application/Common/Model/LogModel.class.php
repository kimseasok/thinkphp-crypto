<?php 
/*
 * 日志记录
 * 每天生成一个日志文件，当文件超过指定大小则备份日志文件并重新生成新的日志文件
 *
*/
class Log {
private $maxsize = 1024000000; //最大文件大小1M
//写入日志
public function writeLog($filename,$msg){
$res = array();
$res['Time'] = date("Y-m-d H:i:s",time());
$res["Msg"] = $msg;

//如果日志文件超过了指定大小则备份日志文件
if(file_exists($filename) && (abs(filesize($filename)) > $this->maxsize)){
$newfilename = dirname($filename).'/'.time().'-'.basename($filename);
rename($filename, $newfilename);

}
//如果是新建的日志文件，去掉内容中的第一个字符逗号
if(file_exists($filename) && abs(filesize($filename))>0){
$content = PHP_EOL.json_encode($res);
}
else{
$content = json_encode($res);
}
//往日志文件内容后面追加日志内容
    file_put_contents($filename, $content, FILE_APPEND);
}
//读取日志
public function readLog($filename){
if(file_exists($filename)){
$content = file_get_contents($filename);
$json = json_decode('['.$content.']',true);
}else{
$json = '{"msg":"The file does not exist."}';
}
return $json;
}
}
 ?>