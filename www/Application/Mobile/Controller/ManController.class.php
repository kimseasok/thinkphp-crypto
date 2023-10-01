<?php
namespace Mobile\Controller;
use Think\Controller;
use GatewayWorker\Lib\Gateway;

class ManController extends Controller {
    public function index(){
        //$uid = I('uid');  //发信人ID，获取登录时ID
        session('uid', '520');//先固定为520
        $this->display();
    }
    /**
     * client_id与uid绑定操作
     */
    public function bind(){
        $client_id = I('client_id');
        $uid      = session('uid');
        //client_id与uid绑定操作
        Gateway::bindUid($client_id, $uid);
        $message = '绑定成功' . $uid . '-' . $client_id;
        //推送成功消息
        Gateway::sendToUid($uid, json_encode($message));
        // 加入某个群组（可调用多次加入多个群组）
        //Gateway::joinGroup($client_id, $group_id);
    }
    /**
     * 发送信息
     */
    public function sendmsg(){
        $uid = session('uid');
        $message = '222';
        //向任意uid的网站页面发送数据
        Gateway::sendToUid($uid, $message);
        //向所有用户发送
        //Gateway::sendToAll($message);
        //向某个client_id对应的连接发消息
        //Gateway::sendToClient($client_id, $data);
        // 向任意群组的网站页面发送数据
        //Gateway::sendToGroup($group, $message);
    }   
}