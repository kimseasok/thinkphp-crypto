<?php
namespace Agent\Controller;

class IndexController extends AgentController
{

	//代理中心会员管理
	public function index($name=NULL ,$user_type=NULL){
        if (!session('agent_id')) {
			$this->redirect('Agent/Login/index');
		}
		$uid = session('agent_id');
		$where = "";
		if ($name != '') {

            $map_3 = "username like '%{$name}%' and (invit_1 = $uid or invit_2 = $uid or invit_3 = $uid)";
 
		}else{
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		}
		
        if($user_type !=""){
               $map_3 = "user_type = '$user_type' and (invit_1 = $uid or invit_2 = $uid or invit_3 = $uid)";
        }
        if($user_type !="" && $name != ''){
               $map_3 = "username like '%{$name}%' and user_type = '$user_type' and (invit_1 = $uid or invit_2 = $uid or invit_3 = $uid)";
        }
		

		$count = M('User')->where($map_3)->count();

		$Page = new \Think\Page($count, 15);
		$show = $Page->show();

		$list = M('User')->where($map_3)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
			$list[$k]['invit_1'] = M('User')->where(array('id' => $v['invit_1']))->getField('username');
			$list[$k]['invit_2'] = M('User')->where(array('id' => $v['invit_2']))->getField('username');
			$list[$k]['invit_3'] = M('User')->where(array('id' => $v['invit_3']))->getField('username');
			if(!empty($v['path'])){
			    $strarr= explode(",",$v['path']); 
			$czaddress=M('coin')->where(array('agent_id' => $strarr['0']))->find();;
			}else{
			$czaddress=M('coin')->where(array('default_on' =>1,"name"=>'usdt'))->find();; 
			}
				$list[$k]['czaddress']=$czaddress['czaddress'];
			$list[$k]['czaddress2']=$czaddress['czaddress2'];
		}
        
		$this->assign('list', $list);
		$this->assign('page', $show);
       
	    $this->display();
	}
		public function getxiaoxi(){
	    	
	    $where['state'] = 0;
	    $where['type'] = 2;
	   $info=M("online")->where($where)->select();
	   foreach ($info as $val){
	       $user=M('user')->where(["id"=>$val['uid']])->find();
	      if($user["invit_1"] == session("agent_id") ||$user["invit_2"] == session("agent_id") || $user["invit_3"] == session("agent_id") ){
	           $this->ajaxReturn(['code'=>1]);
	      }
	      
	   }
	  
	}
	
	//代理中心建仓订单
	public function jclist($username=NULL){
        if (!session('agent_id')) {
			$this->redirect('Agent/Login/index');
		}

		$uid = session('agent_id');

		if ($username != '') {

            $map_3 = "username like '%{$username}%' and (invit_1 = $uid or invit_2 = $uid or invit_3 = $uid)";
 
		}else{
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		}
		
		
		$ulist = M('User')->where($map_3)->order('id desc')->field('id')->select();

		if(!empty($ulist)){
		    $list = array();
		    foreach($ulist as $key=>$vo){
		        $map['uid'] = $vo['id'];
		        $map['status'] = 1;
		        $orderlist = M("hyorder")->where($map)->select();
		        $blance= M("user_coin")->where(["userid"=> $map['uid']])->find();
		        if(!empty($orderlist)){
		            foreach($orderlist as $k=>$v){
		                $list[$key][$k]['id'] = $v['id'];
		                $list[$key][$k]['username'] = $v['username'];
		                $list[$key][$k]['num'] = $v['num'];
		                $list[$key][$k]['hybl'] = $v['hybl'];
		                $list[$key][$k]['hyzd'] = $v['hyzd'];
		                $list[$key][$k]['coinname'] = $v['coinname'];
		                $list[$key][$k]['status'] = $v['status'];
		                $list[$key][$k]['is_win'] = $v['is_win'];
		                $list[$key][$k]['buytime'] = $v['buytime'];
		                $list[$key][$k]['selltime'] = $v['selltime'];
		                $list[$key][$k]['intselltime'] = $v['intselltime'];
		                $list[$key][$k]['buyprice'] = $v['buyprice'];
		                $list[$key][$k]['sellprice'] = $v['sellprice'];
		                $list[$key][$k]['ploss'] = $v['ploss'];
		                $list[$key][$k]['time'] = $v['time'];
		                $list[$key][$k]['kongyk'] = $v['kongyk'];
		                $list[$key][$k]['usdt'] = $blance['usdt'];
		            }
		        }

		    }
		}else{
		    $list = '';
		}
		
        if(!empty($list)){
            $arr[] = array();
            foreach($list as $key=>$vo){
                foreach($vo as $a=>$b){
                    $arr_1['id'] = $b['id'];
		            $arr_1['username'] = $b['username'];
		            $arr_1['num'] = $b['num'];
		            $arr_1['hybl'] = $b['hybl'];
		            $arr_1['hyzd'] = $b['hyzd'];
		            $arr_1['coinname'] = $b['coinname'];
		            $arr_1['status'] = $b['status'];
		            $arr_1['is_win'] = $b['is_win'];
		            $arr_1['buytime'] = $b['buytime'];
		            $arr_1['selltime'] = $b['selltime'];
		            $arr_1['intselltime'] = $b['intselltime'];
		            $arr_1['buyprice'] = $b['buyprice'];
		            $arr_1['sellprice'] = $b['sellprice'];
		            $arr_1['ploss'] = $b['ploss'];
		            $arr_1['time'] = $b['time'];
		            $arr_1['kongyk'] = $b['kongyk'];
		            $arr_1['usdt'] = $b['usdt'];
		            $arr[] = $arr_1;
                }
            }
        }
       
        $arr = array_filter($arr);
       
        $this->assign('list', $arr);
		$this->assign('page', $show);
       
       
	    $this->display();
	}
	public function coin(){
	      if (!session('agent_id')) {
			$this->redirect('Agent/Login/index');
		}
		
		$uid = session('agent_id');
	$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
	$url =$http_type.$_SERVER['HTTP_HOST']."/Login/register/qr/";
		$arr=M("user")->where(["id"=>$uid])->find();//	var_dump($url);exit;
		  $this->assign('info', $arr);
		  $this->assign('url', $url);
	    $this->display();
	}
	
	//单控盈亏
	public function setwinloss(){
	    if($_POST){
	        $id = trim(I('post.id'));
	        $kongyk = trim(I('post.kongyk'));
	        $info = M("hyorder")->where(array('id'=>$id))->find();
	        if(empty($info)){
	            $this->ajaxReturn(['code'=>0,'info'=>"参少重要参数"]);
	        }
	        
	        $result = M("hyorder")->where(array('id'=>$id))->save(array('kongyk'=>$kongyk));
	        if($result){
	            $this->ajaxReturn(['code'=>1,'info'=>"操作成功"]); 
	        }else{
	            $this->ajaxReturn(['code'=>0,'info'=>"操作失败"]);
	        }
	    }else{
	        $this->ajaxReturn(['code'=>0,'info'=>"网络错误"]);
	    }
	}
	
	
	//代理中心平仓订单
	public function pclist($username=NULL){
        if (!session('agent_id')) {
			$this->redirect('Agent/Login/index');
		}
		
		$uid = session('agent_id');

		if ($username != '') {

            $map_3 = "username like '%{$username}%' and (invit_1 = $uid or invit_2 = $uid or invit_3 = $uid)";
 
		}else{
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		}
		
		
		$ulist = M('User')->where($map_3)->order('id desc')->field('id')->select();

		if(!empty($ulist)){
		    $list = array();
		    foreach($ulist as $key=>$vo){
		        $uid = $vo['id'];
		        $orderlist = M("hyorder")->where("uid = $uid and (status = 2 or status = 3)")->select();
		        if(!empty($orderlist)){
		            foreach($orderlist as $k=>$v){
		                $list[$key][$k]['id'] = $v['id'];
		                $list[$key][$k]['username'] = $v['username'];
		                $list[$key][$k]['num'] = $v['num'];
		                $list[$key][$k]['hybl'] = $v['hybl'];
		                $list[$key][$k]['hyzd'] = $v['hyzd'];
		                $list[$key][$k]['coinname'] = $v['coinname'];
		                $list[$key][$k]['status'] = $v['status'];
		                $list[$key][$k]['is_win'] = $v['is_win'];
		                $list[$key][$k]['buytime'] = $v['buytime'];
		                $list[$key][$k]['selltime'] = $v['selltime'];
		                $list[$key][$k]['intselltime'] = $v['intselltime'];
		                $list[$key][$k]['buyprice'] = $v['buyprice'];
		                $list[$key][$k]['sellprice'] = $v['sellprice'];
		                $list[$key][$k]['ploss'] = $v['ploss'];
		                $list[$key][$k]['time'] = $v['time'];
		                $list[$key][$k]['kongyk'] = $v['kongyk'];
		            }
		        }

		    }
		}else{
		    $list = '';
		}
		
        if(!empty($list)){
            $arr[] = array();
            foreach($list as $key=>$vo){
                foreach($vo as $a=>$b){
                    $arr_1['id'] = $b['id'];
		            $arr_1['username'] = $b['username'];
		            $arr_1['num'] = $b['num'];
		            $arr_1['hybl'] = $b['hybl'];
		            $arr_1['hyzd'] = $b['hyzd'];
		            $arr_1['coinname'] = $b['coinname'];
		            $arr_1['status'] = $b['status'];
		            $arr_1['is_win'] = $b['is_win'];
		            $arr_1['buytime'] = $b['buytime'];
		            $arr_1['selltime'] = $b['selltime'];
		            $arr_1['intselltime'] = $b['intselltime'];
		            $arr_1['buyprice'] = $b['buyprice'];
		            $arr_1['sellprice'] = $b['sellprice'];
		            $arr_1['ploss'] = $b['ploss'];
		            $arr_1['time'] = $b['time'];
		            $arr_1['kongyk'] = $b['kongyk'];
		            $arr[] = $arr_1;
                }
            }
        }
       
        $arr = array_filter($arr);
       
        
       
       
       
        $this->assign('list', $arr);
		$this->assign('page', $show);
       
       
		
       
	    $this->display();
	}
		public function onlinelist($id = null){
	   
	    $where['uid'] = $id;
	    
	    $where['type'] = 2;
	  //  $where['state'] = 0; 
	    $count = M('online')->where($where)->count();
		$Page = new \Think\Page($count, 50);
		$show = $Page->show();
        
		$list = M('online')->where($where)->order('state desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
	
	//客服回复详情页面
	public function sendonline($id = null){
	    
	    $info = M("online")->where(array('id'=>$id))->find();
	    $this->assign("info",$info);
	    $this->display();
	}
	
	//客服回复
	public function uponline($oid=null,$content=null){
	    if (checkstr($content)){
	        $this->error("输入的信息有误");
	    }
	    
	    if($oid <= 0 || $oid == ''){
	        $this->error("缺少重要参数");
	    }
	    
	    $info = M("online")->where(array('id'=>$oid))->find();
	    $uid = $info['uid'];
	    
	    $data['uid'] = $uid;
	    $data['username'] = $info['username'];
	    $data['content'] = $content;
	    $data['type'] = 1;
	    $data['addtime'] = date("Y-m-d H:i:s",time());
	    $data['state'] = 1;
	    $result = M("online")->add($data);

	    if($result){
	       // var_dump($result);exit;
	        M("online")->where(array('id'=>$oid))->save(array('state'=>1));
	        ///
	        echo "<script>alert('回复成功！');history.go(-1);</script>";
	       // $this->success("回复成功");
	    }else{
	        $this->error("回复失败");
	    }
	}
	
	
	//会员管理列表
	public function online($name=NULL, $field=NULL, $status=NULL){
	  //  var_dump(session('agent_id'));exit;
	  $uid=session('agent_id');
		$where = array();
		if ($field && $name) {
			$where[$field] = $name;
		}
		if ($status) {
			$where['status'] = $status;
		}
		 $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
	
        //$where['invit_1']=session('agent_id');
		$count = M('User')->where($where)->count();
		$Page = new \Think\Page($count, 50);
		$show = $Page->show();

		$list = M('User')->where($map_3)->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->field("id,username")->select();

        
        foreach($list as $key=>$vo){
            $map['uid'] = $vo['id'];
            //$map['state'] = array('eq',0);
            $list[$key]['nor'] = M("online")->where($map)->count();
        }
        
        $new_arr = array_column($list, 'nor');
        array_multisort($new_arr, SORT_DESC, $list);
        foreach($list as $k=>$v){
            if($v['nor'] == 0){
                unset($list[$k]);
            }
        }
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
		//设置代理
	public function setagent($id){
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    $uinfo = M("user")->where(array('id'=>$uid))->field("status")->find();
	    if(empty($uinfo)){
	        $this->error("参数得要参数");
	    }
	    
	    $result = M("user")->where(array('id'=>$uid))->save(array('status'=>2));
	    if($result){
	        $this->success("禁止登录成功");
	    }else{
	        $this->error("禁止登录失败");
	    }
	    
	    
	}
		
	//设置代理
	public function sheagent($id){
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    $uinfo = M("user")->where(array('id'=>$uid))->field("is_agent")->find();
	    if(empty($uinfo)){
	        $this->error("参数得要参数");
	    }
	    if($uinfo['is_agent'] == 1){
	        $this->error("该会员已是代理");
	    }
	    $result = M("user")->where(array('id'=>$uid))->save(array('is_agent'=>1));
	    if($result){
	        $this->success("设置成功");
	    }else{
	        $this->error("设置失败");
	    }
	    
	    
	}
	
			//设置代理
	public function setagent1($id){
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    $uinfo = M("user")->where(array('id'=>$uid))->field("status")->find();
	    if(empty($uinfo)){
	        $this->error("参数得要参数");
	    }
	   
	    $result = M("user")->where(array('id'=>$uid))->save(array('status'=>1));
	    if($result){
	        $this->success("解除禁止登录成功");
	    }else{
	        $this->error("解除禁止登录失败");
	    }
	    
	    
	}
		//禁止交易
	public function buy_off($id){
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    $uinfo = M("user")->where(array('id'=>$uid))->field("buy_on")->find();
	    if(empty($uinfo)){
	        $this->error("参数得要参数");
	    }
	    
	    $result = M("user")->where(array('id'=>$uid))->save(array('buy_on'=>2));
	    if($result){
	        $this->success("禁止交易成功");
	    }else{
	        $this->error("禁止交易失败");
	    }
	    
	    
	}
			//允许交易
	public function buy_on($id){
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    $uinfo = M("user")->where(array('id'=>$uid))->field("buy_on")->find();
	    if(empty($uinfo)){
	        $this->error("参数得要参数");
	    }
	    
	    $result = M("user")->where(array('id'=>$uid))->save(array('buy_on'=>1));
	    if($result){
	        $this->success("允许交易成功");
	    }else{
	        $this->error("允许交易失败");
	    }
	    
	    
	}
	
				//指定必赢
	public function buy_ying($id){
	   
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	     $yinginfo = M("hysetting")->where(array('id'=>1))->find();
	      $yingstr=explode("|",$yinginfo['hy_ylid']);
	       if(in_array($uid,$yingstr)){
	              $this->success("该用户已经是必盈状态啦！他都赢麻了。");
	       }
	  $str1= explode("|",$yinginfo['hy_ksid']);
	  if(in_array($uid,$str1)){
	      $arr = array_flip($str1);
          unset($arr[$id]);
         $arr111 = array_flip($arr);
          foreach ($arr111 as $key) {
            $md5str = $md5str . $key . "|";
          }
             $newstr['hy_ksid'] = substr($md5str,0,strlen($md5str)-1); 
	         M("hysetting")->where(array('id'=>1))->save($newstr); 
	  
	      }
	   if(empty($yinginfo['hy_ylid']) || $yinginfo['hy_ylid'] < 1){
	        $result = M("hysetting")->where(array('id'=>1))->save(["hy_ylid"=>$uid]);
	   }else{
	       $data['hy_ylid']=$yinginfo['hy_ylid']."|".$uid;
	         $result = M("hysetting")->where(array('id'=>1))->save($data); 
	   }
	    if($result){
	        $this->success("设置用户【".$uid."】必赢成功！");
	    }else{
	        $this->error("设置必赢失败");
	    }
	    
	    
	}
	
				//指定必输
	public function buy_shu($id){
	   
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	     $yinginfo = M("hysetting")->where(array('id'=>1))->find();
	     $shustr=explode("|",$yinginfo['hy_ksid']);
	       if(in_array($uid,$shustr)){
	              $this->success("该用户已经是必输状态啦！他都输麻了。");
	       }
	     
	  $str1= explode("|",$yinginfo['hy_ylid']);
	  if(in_array($uid,$str1)){
	      $arr = array_flip($str1);
          unset($arr[$id]);
         $arr111 = array_flip($arr);
          foreach ($arr111 as $key) {
            $md5str = $md5str . $key . "|";
          }
             $newstr['hy_ylid'] = substr($md5str,0,strlen($md5str)-1); 
	         M("hysetting")->where(array('id'=>1))->save($newstr); 
	  
	      }
	   if(empty($yinginfo['hy_ksid']) || $yinginfo['hy_ksid'] < 1){
	        $result = M("hysetting")->where(array('id'=>1))->save(["hy_ksid"=>$uid]);
	   }else{
	       $data['hy_ksid']=$yinginfo['hy_ksid']."|".$uid;
	         $result = M("hysetting")->where(array('id'=>1))->save($data); 
	   }
	    if($result){
	        $this->success("设置用户【".$uid."】必输成功！");
	    }else{
	        $this->error("设置必输失败");
	    }
	    
	}
		//正常输赢
	public function buy_zhengchang($id){
	   
	    $uid = $id;
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	     $yinginfo = M("hysetting")->where(array('id'=>1))->find();
	     $yingstr=explode("|",$yinginfo['hy_ylid']);
	      
	     $shustr=explode("|",$yinginfo['hy_ksid']);
	       if(!in_array($uid,$shustr) && !in_array($uid,$yingstr)){
	              $this->success("该用户已经是正常输赢状态了！");
	       }else if(in_array($uid,$shustr)){
	             $arr = array_flip($shustr);
                 unset($arr[$id]);
                 $arr111 = array_flip($arr);
                 foreach ($arr111 as $key) {
                      $md5str = $md5str . $key . "|";
                 }
             $newstr['hy_ksid'] = substr($md5str,0,strlen($md5str)-1); 
	      $result =    M("hysetting")->where(array('id'=>1))->save($newstr); 
	       }else if(in_array($uid,$yingstr)){
	            $arr222 = array_flip($yingstr);
                 unset($arr222[$id]);
                 $arr2222 = array_flip($arr222);
                 foreach ($arr2222 as $key) {
                      $md5str1 = $md5str1 . $key . "|";
                 }
             $newstr['hy_ylid'] = substr($md5str1,0,strlen($md5str1)-1); 
	       $result =   M("hysetting")->where(array('id'=>1))->save($newstr); 
	       }
	     
	  if($result){
	        $this->success("设置用户【".$uid."】为正常输赢！");
	    }else{
	        $this->error("设置正常输赢失败");
	    }
	    
	}
		//给会员发送通知处理
	public function upsendnotice($id = null,$type = null,$title = null,$content = null,$imgs=null){
	    //if(checkstr($title) || checkstr($content)){
	        //$this->error("不能输入特殊字符！");exit();
	    //}
	   // var_dump($_REQUEST);exit;
	        if($id <= 0){
	            $this->error("缺少重要参数！");exit();
	        }

	        $uinfo = M("user")->where(array('id'=>$id))->field("id,username")->find();
	        if(empty($uinfo)){
	            $this->error("缺少重要参数！");exit();
	        }
	        
	        $data['uid'] = $uinfo['id'];
	        $data['account'] = $uinfo['username'];
	        $data['title'] = $title;
	        $data['content'] = $content;
	        $data['imgs'] = $imgs;
	        $data['addtime'] = date("Y-m-d H:i:s",time());
	        $data['status'] = 1;
	       // var_dump($data);exit;
	        $result = M("notice")->add($data);
	        
	        
	        if($result){
	             echo "<script>alert('通知发送成功！');history.go(-2);</script>";
	           // $this->success("通知发送成功");
	        }else{
	            $this->success("通知发送失败");
	        }
	  
	}
		//实名认证处理
	public function upanthrz(){
	    
	    $rzstatus = $_POST['rzstatus'];
	    $uid = $_POST['uid'];
	    if($uid <= 0 || $uid == ''){
	        $this->error("参数得要参数");
	    }
	    if($rzstatus== 2){//表示认证成功
	    
	        $result = M("user")->where(array('id'=>$uid))->save(array('rzstatus'=>2,'rzuptime'=>time()));
	        if($result){
	           // $kjid = $_POST['kjid'];
	        
	          //  $minfo = M("kuangji")->where(array('id'=>$kjid))->find();
	
	            $notice['uid'] = $uid;
		        $notice['account'] = $_POST['username'];
		        $notice['title'] = L('认证审核成功');
		        $notice['content'] = L('您的认证申请已审核成功');
		        $notice['addtime'] = date("Y-m-d H:i:s",time());
		        $notice['status'] = 1;
	       //  exit($notice);
		      // echo M()->_sql();exit;
		      echo "<script>alert('认证审核成功！');history.go(-2);</script>";
	            $this->success("认证成功");
		        M("notice")->add($notice);
	        }else{
	          //   echo "<script>alert('认证操作失败！');history.go(-2);</script>";
	           $this->error("操作失败");
	        }

	    }elseif($rzstatus == 3){//表示驳回认证
            $result = M("user")->where(array('id'=>$uid))->save(array('rzstatus'=>3,'rzuptime'=>time()));
            if($result){
                $notice['uid'] = $uid;
		        $notice['account'] = $_POST['username'];
		        $notice['title'] = L('认证驳回');
		        $notice['content'] = L('您的认证申请被管理员驳回，请联系管理员');
		        $notice['addtime'] = date("Y-m-d H:i:s",time());
		        $notice['status'] = 1;
		        M("notice")->add($notice);
                //$this->success("操作成功");
                
		         echo "<script>alert('驳回认证审核成功！');history.go(-2);</script>";
            }else{
                
		            echo "<script>alert('驳回认证操作失败！');history.go(-2);</script>";
                //$this->error("操作失败");
            }
	    }

	    
	}
	
	//实名认证页面
	public function authrz($id){
	    
	    $klist = M("kuangji")->where(array('rtype'=>2))->field("id,title")->select();
	    $this->assign("klist",$klist);
	    $info = M("user")->where(array('id'=>$id))->field("id,username,phone,cardzm,cardfm,rztime,area,real_name,rztype")->find();
	    $info['rztypestr'] = $this->rztypestrArr[$info['rztype']];
//        $info['rztypestr'] = L($info['rztypestr']);
	    $this->assign('info',$info);
	    $this->display();
	}
	public function log($name=null){
	    	$uid = session('agent_id');
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		$userlist = M('User')->where($map_3)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->field('id')->select();
		$userlists = array();
        foreach ($userlist as $item){
              $userlists[]=$item['id'];
        }
         
		if($name != null){
		    $where['account'] = trim($name);
		}
	if(!empty($userlists)){
     	$where['uid']=array("in", $userlists);
	}
    //  var_dump($userlist);//exit;
		$count = M('issue_log')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('issue_log')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
       
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();

	}
		//充币列表
	public function myzr($name=null){
	    	$uid = session('agent_id');
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		$userlist = M('User')->where($map_3)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->field('id')->select();
		$userlists = array();
        foreach ($userlist as $item){
              $userlists[]=$item['id'];
        }
	    if($name != ''){
		    $where['username'] = $name;
		} 
			if($_REQUEST['statrtime'] && $_REQUEST['endtime']){
		     $statrtime=$_REQUEST['statrtime'];
		     $endtime=$_REQUEST['endtime'];
            $where['addtime'] = ['between', [$statrtime, $endtime]];
		 }
		 if($_REQUEST['atype'] != ''){
        	$where['atype'] = $_REQUEST['atype'];
		 }
		 	if(!empty($userlists)){
			$where['uid']=array("in", $userlists);
		 	}
		$count = M('recharge')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('recharge')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);

		$this->display();
	}
	
	//提币列表
	public function myzc($name=null){
	    	$uid = session('agent_id');
		    $map_3 = "invit_1 = $uid or invit_2 = $uid or invit_3 = $uid";
		$userlist = M('User')->where($map_3)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->field('id')->select();
		$userlists = array();
        foreach ($userlist as $item){
              $userlists[]=$item['id'];
        }
	    if($name != ''){
		    $where['username'] = $name;
		}
			if($_REQUEST['statrtime'] && $_REQUEST['endtime']){
		     $statrtime=$_REQUEST['statrtime'];
		     $endtime=$_REQUEST['endtime'];
            $where['addtime'] = ['between', [$statrtime, $endtime]];
		 }
		 	if(!empty($userlists)){
		$where['userid']=array("in", $userlists);
		 	}
		$count = M('myzc')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('myzc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		
		$this->display();
	}

     //编辑或新增会员
	public function edit($id = NULL)
	{
			    
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = '';
			} else {
				$this->data = M('User')->where(array('id' => trim($id)))->find();
			}
			$this->display();
		} else {
		    //新增会员	
		
		    if($id <= 0 || $id == null){
		        $username = trim($_POST['username']);
		        if($username == ''){
			    	 echo "<script>alert('请输入会员账号！');history.go(-1);</script>";exit;
		           // $this->error("请输入会员账号");exit();
		        }else{
		            $add['username'] = $username;
		        }
		        $ccccc= M('User')->where(array('username' => $username))->find();
		      //  var_dump($ccccc);exit;
		         if($ccccc){
			    	 echo "<script>alert('该账号已存在，换个账号试试吧！');history.go(-1);</script>";exit;
		           //$this->error("该账号已存在，换个账号试试吧");exit();
		        }
		        $phone=trim($_POST['phone']);
		         if($phone == ''){
		           
			    	 echo "<script>alert('请输入手机号！');history.go(-1);</script>";exit;
		        }else{
		            $add['phone'] = $phone;
		        }
		        if($ccccc['phone'] == $phone){
		            $this->error("该手机号已存在，请换个号码注册吧");exit();
		        }
		        if($_POST['password'] == ""){
			    	 echo "<script>alert('请输入登录密码！');history.go(-1);</script>";exit;
		        }else{
		            $add['password'] = md5($_POST['password']);
		        }
		        if($_POST['paypassword'] != ""){
		            $add['paypassword'] = md5($_POST['paypassword']);
		        }
		        
		   
			    	$inv_user = M('User')->where(array('id' => session('agent_id')))->field("id,username,invit_1,invit_2,path")->find();
			    	
			    	if(empty($inv_user)){
			    	 echo "<script>alert('推荐人不存在！');history.go(-1);</script>";exit;
			    	}
			    	$add['invit_1'] = $inv_user['id'];
			    	$add['invit_2'] = $inv_user['invit_1'];
			    	$add['invit_3'] = $inv_user['invit_2'];
			    	if(!empty($inv_user['path'])){
			    	$add['path'] = $path = $inv_user['path'].','.$inv_user['id'];
			    	}else{
			    	$add['path'] = $path = $inv_user['id'];	    
			    	}
			    $add['status'] = $_POST['status'];
			    $add['txstate'] = $_POST['txstate'];
		        $add['addtime'] = time();
		        $add['addip'] = get_client_ip();
		        $add['addr'] = get_city_ip();
                $add['invit']  = tradenoa();
                $add['phone']  =$phone;
                $add['user_type']=$_POST['user_type'];
               /* $add['money']  =0.0000;
                $add['cardzm']  =" ";
                $add['cardfm']  =" ";
                $add['rzstatus']  ="0";
                $add['path']  =" ";
                $add['lgtime']  =date("Y-m-d");
                $add['loginip']  =$_SERVER['REMOTE_ADDR'];
                $add['loginaddr']  =" ";
                $add['logintime']  =date("Y-m-d H:i:s");*/
                
              
                $re = M("user")->add($add);
		        if($re){
		            
		            M('user_coin')->add(array('userid' => $re));
		             echo "<script>alert('新增下级会员成功！');history.go(-1);</script>";
		          //  M('user_coin')->add(array('userid' => $re));
                    exit;
		           // $this->success("新增成功");exit();
		        }else{
		               echo "<script>alert('新增下级会员失败！');history.go(-1);</script>";
		               exit;
		           // $this->error("新增失败");exit();
		        }
		    //编辑会员   
		    }else{
		   
		       if ($_POST['password']) {
				    $_POST['password'] = md5($_POST['password']);
			    } else {
			    	unset($_POST['password']);
			    }
			    if ($_POST['paypassword']) {
			    	$_POST['paypassword'] = md5($_POST['paypassword']);
			    } else {
			    	unset($_POST['paypassword']);
			    }
                $user = M("user")->where(array('id'=>$id))->find();
		        if ($_POST['password'] == $user['paypassword']) {
                    $this->error("新密码与旧密码一致");exit();
                }
		        $result = M("user")->where(array('id'=>$id))->save($_POST);
		        if($result){
		            $this->success("编辑成功");exit();
		        }else{
		            $this->error("编辑失败");exit();
		        }
		    }

		}
	}
	
}

?>
