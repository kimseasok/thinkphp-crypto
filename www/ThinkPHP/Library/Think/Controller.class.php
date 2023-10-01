<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think;
/**
 * ThinkPHP 控制器基类 抽象类
 */
abstract class Controller {

    /**
     * 视图实例对象
     * @var view
     * @access protected
     */    
    protected $view     =  null;

    /**
     * 控制器参数
     * @var config
     * @access protected
     */      
    protected $config   =   array();

   /**
     * 架构函数 取得模板对象实例
     * @access public
     */
    public function __construct() {
        Hook::listen('action_begin',$this->config);
        //实例化视图类
        $this->view     = Think::instance('Think\View');
        //控制器初始化
        if(method_exists($this,'_initialize'))
            $this->_initialize();
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     * @return void
     */
    protected function display($templateFile='',$charset='',$contentType='',$content='',$prefix='') {
        $this->view->display($templateFile,$charset,$contentType,$content,$prefix);
    }

    /**
     * 输出内容文本可以包括Html 并支持内容解析
     * @access protected
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $prefix 模板缓存前缀
     * @return mixed
     */
    protected function show($content,$charset='',$contentType='',$prefix='') {
        $this->view->display('',$charset,$contentType,$content,$prefix);
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     * @access protected
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀* 
     * @return string
     */
    protected function fetch($templateFile='',$content='',$prefix='') {
        return $this->view->fetch($templateFile,$content,$prefix);
    }

    /**
     *  创建静态页面
     * @access protected
     * @htmlfile 生成的静态文件名称
     * @htmlpath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     * 默认为空 由系统自动定位模板文件
     * @return string
     */
    protected function buildHtml($htmlfile='',$htmlpath='',$templateFile='') {
        $content    =   $this->fetch($templateFile);
        $htmlpath   =   !empty($htmlpath)?$htmlpath:HTML_PATH;
        $htmlfile   =   $htmlpath.$htmlfile.C('HTML_FILE_SUFFIX');
        Storage::put($htmlfile,$content,'html');
        return $content;
    }

    /**
     * 模板主题设置
     * @access protected
     * @param string $theme 模版主题
     * @return Action
     */
    protected function theme($theme){
        $this->view->theme($theme);
        return $this;
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     * @return Action
     */
    protected function assign($name,$value='') {
        $this->view->assign($name,$value);
        return $this;
    }

    public function __set($name,$value) {
        $this->assign($name,$value);
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     * @param string $name 模板显示变量
     * @return mixed
     */
    public function get($name='') {
        return $this->view->get($name);      
    }

    public function __get($name) {
        return $this->get($name);
    }

    /**
     * 检测模板变量的值
     * @access public
     * @param string $name 名称
     * @return boolean
     */
    public function __isset($name) {
        return $this->get($name);
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     * @param string $method 方法名
     * @param array $args 参数
     * @return mixed
     */
    public function __call($method,$args) {
        if( 0 === strcasecmp($method,ACTION_NAME.C('ACTION_SUFFIX'))) {
            if(method_exists($this,'_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method,$args);
            }elseif(file_exists_case($this->view->parseTemplate())){
                // 检查是否存在默认模版 如果有直接输出模版
                $this->display();
            }else{
                E(L('_ERROR_ACTION_').':'.ACTION_NAME);
            }
        }else{
            E(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
            return;
        }
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function error($message='',$jumpUrl='',$ajax=false) {
      $this->dispatchJump($message,1,$jumpUrl,$ajax);
   //$this->msg($message,$jumpUrl);
    } 

//提示框
function msg($msg = '保存成功',$url='',$err='0',$btn='3 秒后跳转'){
	$obj=is_object($msg)?$msg:array();
	if(empty($obj['msg']))$obj['msg'] = $msg;
	if(empty($obj['err']))$obj['err'] = $err;
	if(empty($obj['url']))$obj['url'] = $url;
	if('0'===$obj['err']&&preg_match('/(请|重|失|误)/ui',$obj['msg']))$obj['err']=1;
	if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&stristr($_SERVER['HTTP_X_REQUESTED_WITH'],'xmlhttprequest'))exit(json_encode($obj));
	if(empty($obj['btn']))$obj['btn'] = $btn;
	$html = '<!doctype html><html><head><meta http-equiv="content-type" content="text/html;charset=utf-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">';
	$html.='<meta name="viewport" content="width=device-width,initial-scale=1.0,user-scalable=no"><title>'.$obj['msg'].'</title><style>*{margin:0;padding:0;box-sizing:border-box;}';
	$html.='body{text-align:center;color:#666;background:#f6f6f6;}#btn{color:#FFF;border:none;font-size:16px;padding:12px 49px;line-height:1;font-weight:bold;border-radius:5px;background:#4899E0;text-decoration:none;}';
	$html.='h2{margin:30px 0;font-size:18px;}.block{margin:200px auto 0;width:80%;min-width:250px;max-width:400px;border:1px solid #eee;padding:20px 0 50px;border-radius:10px;background:#fff;box-shadow:0 0 40px #9aa;}';
	$html.='.icon{width:80px;height:80px;border:4px solid gray;border-radius:40px;border-radius:50%;margin:10px auto 0;padding:0;position:relative;box-sizing:content-box;}.icon.sErr{border-color:#F27474;}';
	$html.='.icon.sErr .sMark{position:relative;display:block;}.icon.sErr .sline{position:absolute;height:5px;width:47px;background:#F27474;display:block;top:37px;border-radius:2px;}';
	$html.='.icon.sErr .sline.sleft{transform:rotate(45deg);left:17px;}.icon.sErr .sline.sright{transform:rotate(-45deg);right:16px;}.icon.sWarn{border-color:#F8BB86;}';
	$html.='.icon.sInfo{border-color:#4899E0;}.icon.sOk{border-color:#A5DC86;}.icon.sWarn .sbody{position:absolute;width:5px;height:31px;left:50%;top:16px;border-radius:2px;margin-left:-2px;background:#F8BB86;}';
	$html.='.icon.sWarn .sdot{position:absolute;width:7px;height:7px;border-radius:50%;margin-left:-3px;left:50%;bottom:18px;background:#F8BB86;}';
	$html.='.icon.sInfo::before{content:"";position:absolute;width:5px;height:29px;left:50%;bottom:17px;border-radius:2px;margin-left:-2px;background:#4899E0;}';
	$html.='.icon.sInfo::after{content:"";position:absolute;width:7px;height:7px;border-radius:50%;left:50%;margin-left:-3px;top:19px;background:#4899E0;}';
	$html.='.icon.sOk::before, .icon.sOk::after{content:"";border-radius:40px;border-radius:50%;position:absolute;width:60px;height:120px;background:white;transform:rotate(45deg);}';
	$html.='.icon.sOk::before{border-radius:120px 0 0 120px;top:-7px;left:-33px;transform:rotate(-45deg);transform-origin:60px 60px;}';
	$html.='.icon.sOk::after{border-radius:0 120px 120px 0;top:-11px;left:30px;transform:rotate(-45deg);transform-origin:0px 60px;}';
	$html.='.icon.sOk .sPlaer{width:80px;height:80px;border:4px solid rgba(165, 220, 134, 0.2);border-radius:40px;border-radius:50%;box-sizing:content-box;position:absolute;left:-4px;top:-4px;z-index:2;}';
	$html.='.icon.sOk .sfix{width:5px;height:90px;background:white;position:absolute;left:28px;top:8px;z-index:1;transform:rotate(-45deg);}';
	$html.='.icon.sOk .sline{height:5px;background:#A5DC86;display:block;border-radius:2px;position:absolute;z-index:2;}';
	$html.='.icon.sOk .sline.stip{width:25px;left:14px;top:46px;transform:rotate(45deg);}.icon.sOk .sline.slong{width:47px;right:8px;top:38px;transform:rotate(-45deg);}';
	$html.='.icon.sOk.ani::after{animation:rPlaer 4.25s ease-in;}.aXMark{animation:aXMark 0.5s;}.pWarn{animation:pWarn 0.75s infinite alternate;}.pWarnIns{animation:pWarnIns 0.75s infinite alternate;}';
	$html.='@keyframes rPlaer{0%{transform:rotate(-45deg);}5%{transform:rotate(-45deg);}12%{transform:rotate(-405deg);}100%{transform:rotate(-405deg);}}';
	$html.='@keyframes aOkTip{0%{width:0;left:1px;top:19px;}54%{width:0;left:1px;top:19px;}70%{width:50px;left:-8px;top:37px;}84%{width:17px;left:21px;top:48px;}100%{width:25px;left:14px;top:45px;}}';
	$html.='@keyframes aOkLong{0%{width:0;right:46px;top:54px;}65%{width:0;right:46px;top:54px;}84%{width:55px;right:0px;top:35px;}100%{width:47px;right:8px;top:38px;}}';
	$html.='@keyframes aErrIcon{0%{transform:rotateX(100deg);opacity:0;}100%{transform:rotateX(0deg);opacity:1;}}';
	$html.='@keyframes aXMark{0%{transform:scale(0.4);margin-top:26px;opacity:0;}50%{transform:scale(0.4);margin-top:26px;opacity:0;}80%{transform:scale(1.15);margin-top:-6px;}100%{transform:scale(1);margin-top:0;opacity:1;}}';
	$html.='@keyframes pWarn{0%{border-color:#F8D486;}100%{border-color:#E49B5B;}}@keyframes pWarnIns{0%{background:#F8D486;}100%{background:#F8BB86;}}</style></head><body><div class="block">';
	if(0==$obj['err'])$html.=	'<div class="icon sOk ani"><span class="sline stip aOkTip"></span><span class="sline slong aOkLong"></span><div class="sPlaer"></div><div class="sfix"></div></div>';
	if(1==$obj['err'])$html.=	'<div class="icon sErr aErrIcon"><span class="sMark aXMark"><span class="sline sleft"></span><span class="sline sright"></span></span></div>';
	if(2==$obj['err'])$html.=	'<div class="icon sWarn pWarn"><span class="sbody pWarnIns"></span><span class="sdot pWarnIns"></span></div>';
	if(3==$obj['err'])$html.=	'<div class="icon sInfo"></div>';
	$html.=	'<h2>'.$obj['msg'].'</h2><a id="btn" href="'.($obj['url']?$obj['url']:'javascript:history.go(-1)').'">'.$obj['btn'].'</a>';
	$html.='<script>IntTime=setInterval(function(){btn.innerHTML=btn.innerHTML.replace(/(\d+)/,function(a,b){if(--b<1){location.href=btn.href;clearInterval(IntTime)}return b});},1000);</script></body></html>';
	exit($html);
}
    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function success($message='',$jumpUrl='',$ajax=false) {
        $this->dispatchJump($message,1,$jumpUrl,$ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return void
     */
    protected function ajaxReturn($data,$type='',$json_option=0) {
        if(empty($type)) $type  =   C('DEFAULT_AJAX_RETURN');
        switch (strtoupper($type)){
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                exit(json_encode($data,$json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                $handler  =   isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
                exit($handler.'('.json_encode($data,$json_option).');');  
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/html; charset=utf-8');
                exit($data);            
            default     :
                // 用于扩展其他返回格式数据
                Hook::listen('ajax_return',$data);
        }
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     * @return void
     */
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
        $url    =   U($url,$params);
        redirect($url,$delay,$msg);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function dispatchJump($message,$status=1,$jumpUrl='',$ajax=false) {
        if(true === $ajax || IS_AJAX) {// AJAX提交
            $data           =   is_array($ajax)?$ajax:array();
            $data['info']   =   $message;
            $data['status'] =   $status;
            $data['url']    =   $jumpUrl;
            $this->ajaxReturn($data);
        }
        if(is_int($ajax)) $this->assign('waitSecond',$ajax);
        if(!empty($jumpUrl)) $this->assign('jumpUrl',$jumpUrl);
        // 提示标题
        $this->assign('msgTitle',$status? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if($this->get('closeWin'))    $this->assign('jumpUrl','javascript:window.close();');
        $this->assign('status',$status);   // 状态
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON',false);
        if($status) { //发送成功信息
            $this->assign('message',$message);// 提示信息
            // 成功操作后默认停留1秒
            if(!isset($this->waitSecond))    $this->assign('waitSecond','1');
            // 默认操作成功自动返回操作前页面
            if(!isset($this->jumpUrl)) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
            $this->display(C('TMPL_ACTION_SUCCESS'));
        }else{
            $this->assign('error',$message);// 提示信息
            //发生错误时候默认停留3秒
            if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
            // 默认发生错误的话自动返回上页
            if(!isset($this->jumpUrl)) $this->assign('jumpUrl',"javascript:history.back(-1);");
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            exit ;
        }
    }

   /**
     * 析构方法
     * @access public
     */
    public function __destruct() {
        // 执行后续操作
        Hook::listen('action_end');
    }
}
// 设置控制器别名 便于升级
class_alias('Think\Controller','Think\Action');
