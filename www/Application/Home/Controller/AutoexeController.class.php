<?php
namespace Home\Controller;

class AutoexeController extends \Think\Controller
{
	protected function _initialize()
	{
		$allow_action = array("hycarryout","getnewprice","setwl","setwl_ty","autokjsy","releasedjprofit","autoxjtade","authsharesjsy","releaseissue","hycarryout_ty","rengoubijiesuan","rengoubifafang","rengoubimoneyup","recharge_jiankong","getTransferInList");
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error("非法操作！");
		}
	}
	
	
	//自动释放冻结的认购币,设置计划任务，每天执行一次
	public function releaseissue(){
	    $nowday = date("Y-m-d",time());
	    $map['status'] = 1;
	    $map['endday'] = array('elt',$nowday);
	    $list = M("issue_log")->where($map)->select();
	    if(!empty($list)){
	        foreach($list as $key=>$vo){
	            $id = $vo['id'];
	            $uid = $vo['uid'];
	            $num = $vo['num'];
	            $cname = trim($vo['coinname']);
	            $cnamed = trim($vo['coinname'])."d";
	            //修改记录状态
	            $result = M("issue_log")->where(array('id'=>$id))->save(array('status'=>2));
	            if($result){
	                $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	                //扣除冻结的资产
	                M("user_coin")->where(array('userid'=>$uid))->setDec($cnamed,$num);
	                //增加可用资产的数量
	                M("user_coin")->where(array('userid'=>$uid))->setInc($cname,$num);
	                //写入日志
	                $data['uid'] = $uid;
	                $data['username'] = $vo['account'];
	                $data['num'] = $num;
	                $data['coinname'] = $cname;
	                $data['afternum'] = $minfo[$cname] + $num;
	                $data['type'] = 18;
	                $data['addtime'] = date("Y-m-d H:i:s",time());
	                $data['st'] = 1;
	                $data['remark'] = L("认购资产释放");
	                M("bill")->add($data);
	                echo "==认购记录ID:".$id."释放成功";
	            }else{
	                echo "==认购记录ID:".$id."释放失败";
	            }
	            
	        }
	    }else{
	        echo "==没有可释放认购记录==";
	    }
	}
	
	

	//委托订单自动交易
	//设置成5-10秒执行一次的计划任务
	public function autoxjtade(){
	    $list = M("bborder")->where(array('ordertype'=>1,'status'=>1))->select();
	    if(!empty($list)){
	        foreach($list as $k=>$v){
	            $type = $v['type'];
	            $uid = $v['uid'];
	            $id = $v['id'];
	            $symbol = strtolower($v['coin']).'usdt';
	            $lowercoin = strtolower($v['coin']);
	            
	            //限价单价
	            $xjprice = $v['xjprice'];
	            $sxfbl = $v['sxfbl'];
	            if($lowercoin == "mbn"){
	                $priceinfo = M("market")->where(array('name'=>"mbn_usdt"))->field("new_price")->find();
	                $newprice = $priceinfo['new_price'];
	            }else{
	                //获取当前行情价
	                $coinapi = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=".$symbol;
	                $newprice = $this->getnewprice($coinapi);
	            }

	            //买入，当行情价小于等于限价时则交易
	            $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	            if($type == 1){
	                $usdtnum = $v['usdtnum'];
	                if($newprice <= $xjprice){
	                    //计算能够买到的量
	                    $buy_coinnum = sprintf("%.8f",($usdtnum / $newprice));
	                    //手续费
	                    $fee = $buy_coinnum * $sxfbl / 100;
	                    //实际到账号的金额
	                    $tcoinnum = $buy_coinnum;// - $fee;
	                    //更新订单
	                    $data['coinnum'] = $tcoinnum;
	                    $data['price'] = $newprice;
	                    $data['tradetime'] = date("Y-m-d H:i:s",time());
	                    $data['fee'] =0;// $fee;
	                    $data['status'] = 2;
	                    $savere = M("bborder")->where(array('id'=>$id))->save($data);
	                    //增加购买数量并写入日志
	                    $incre = M("user_coin")->where(array('userid'=>$uid))->setInc($lowercoin,$tcoinnum);
	                    $cincbill['uid'] = $uid;
	                    $cincbill['username'] = $v['account'];
	                    $cincbill['num'] = $tcoinnum;
	                    $cincbill['coinname'] = $lowercoin;
	                    $cincbill['afternum'] = $minfo[$lowercoin] + $tcoinnum;
	                    $cincbill['type'] = 10;
	                    $cincbill['addtime'] = date("Y-m-d H:i:s",time());
	                    $cincbill['st'] = 1;
	                    $cincbill['remark'] = L('币币交易限价购买委托成交');
	                    $cincre = M("bill")->add($cincbill);

	                    //扣除冻结的USDT并写入日志
	                    $decre = M("user_coin")->where(array('userid'=>$uid))->setDec("usdtd",$usdtnum);
                        $uincbill['uid'] = $uid;
	                    $uincbill['username'] = $v['account'];
	                    $uincbill['num'] = $usdtnum;
	                    $uincbill['coinname'] = "usdt";
	                    $uincbill['afternum'] = $minfo['usdt'] - $usdtnum;
	                    $uincbill['type'] = 9;
	                    $uincbill['addtime'] = date("Y-m-d H:i:s",time());
	                    $uincbill['st'] = 2;
	                    $uincbill['remark'] = L('币币交易限价购买委托成交');
	                    $uincre = M("bill")->add($uincbill);
	                    
	                    if($savere && $cincre && $uincre){
	                        
	                        $notice['uid'] = $uid;
		                    $notice['account'] = $v['account'];
		                    $notice['title'] = L('币币交易限价委托交易');
		                    $notice['content'] = L('币币交易限价购买委托订单购买成功');
		                    $notice['addtime'] = date("Y-m-d H:i:s",time());
		                    $notice['status'] = 1;
		                    M("notice")->add($notice);
	                        
	                        echo "==委托订单ID：".$id.",购买成功==";
	                    }
	                }else{
	                    echo "==委托订单ID：".$id.",没有达到限价购买价格==";
	                }
	            
	            //卖出，当行情价大于等于限价时则交易    
	            }elseif($type == 2){
	                $coinnum = $v['coinnum']; 
	                if($newprice >= $xjprice){
	                    //求出卖出所得的USDT量
	                    $allusdt = sprintf("%.8f",($coinnum * $newprice));
	                    //求出手续费
	                    $fee = $allusdt *  $sxfbl / 100;
	                    //求出实际到账USDT量
	                    $tusdtnum = $allusdt - $fee;
	                    //更新订单
	                    $data['usdtnum'] = $tusdtnum;
	                    $data['price'] = $newprice;
	                    $data['tradetime'] = date("Y-m-d H:i:s",time());
	                    $data['fee'] = $fee;
	                    $data['status'] = 2;
	                    $savere = M("bborder")->where(array('id'=>$id))->save($data);
	                    //增加卖出所得的USDT量并写入日志
	                    $incre = M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$tusdtnum);
	                    $uincbill['uid'] = $uid;
	                    $uincbill['username'] = $v['account'];
	                    $uincbill['num'] = $tusdtnum;
	                    $uincbill['coinname'] = 'usdt';
	                    $uincbill['afternum'] = $minfo['usdt'] + $tusdtnum;
	                    $uincbill['type'] = 9;
	                    $uincbill['addtime'] = date("Y-m-d H:i:s",time());
	                    $uincbill['st'] = 1;
	                    $uincbill['remark'] = L('币币交易限价出售委托成交');
	                    $uincre = M("bill")->add($uincbill);
	                    
	                    //扣除冻结的卖出币量并写入日志
	                    $decre = M("user_coin")->where(array('userid'=>$uid))->setDec($lowercoin."d",$coinnum);
                        $cincbill['uid'] = $uid;
	                    $cincbill['username'] = $v['account'];
	                    $cincbill['num'] = $coinnum;
	                    $cincbill['coinname'] = $lowercoin;
	                    $cincbill['afternum'] = $minfo[$lowercoin] - $coinnum;
	                    $cincbill['type'] = 10;
	                    $cincbill['addtime'] = date("Y-m-d H:i:s",time());
	                    $cincbill['st'] = 2;
	                    $cincbill['remark'] = L('币币交易限价出售委托成交');
	                    $cincre = M("bill")->add($cincbill);
	                    
	                    if($savere && $cincre && $uincre){
	                        
	                        
	                        $notice['uid'] = $uid;
		                    $notice['account'] = $v['account'];
		                    $notice['title'] = L('币币交易限价委托交易');
		                    $notice['content'] = L('币币交易限价购买委托订单出售成功');
		                    $notice['addtime'] = date("Y-m-d H:i:s",time());
		                    $notice['status'] = 1;
		                    M("notice")->add($notice);
		                    
            	            $allcount=$fee;
            	            $invit_1=0;
            	            $invit_2=0;
            	            $invit_3=0;
            	            $donfig = M("config")->where(array('id'=>1))->find();
            	            if($donfig)
            	            {
            	              $invit_1=$donfig['invert1'];
            	              $invit_2=$donfig['invert2'];
            	              $invit_3=$donfig['invert3'];
            	            }
            	            $uinfo = M("user")->where(array('id'=>$uid))->field("id,username,rzstatus,buy_on,invit_1,invit_2,invit_3")->find();
            	            $allcount=$fee;
            	            if($uinfo['invit_1']!=0)
            	            {
            	                $allcount=$allcount*$invit_1/100;
            		            $uinfoinvit = M("user")->where(array('id'=>$uinfo['invit_1']))->find();
            	                //增加推广并写入日志
                    	        $decre = M("user_coin")->where(array('userid'=>$uinfo['invit_1']))->setInc("usdt",$allcount);
                    	        $decre = M("user")->where(array('id'=>$uinfo['invit_1']))->setInc("invit_1money",$allcount);
                    	        $cbill['uid'] = $uinfo['invit_1'];
                    		    $cbill['username'] = $uinfoinvit['username'];
                    		    $cbill['num'] = $allcount;
                    		    $cbill['coinname'] = "usdt";
                    		    $cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
                    		    $cbill['type'] = 13;
                    		    $cbill['addtime'] = date("Y-m-d H:i:s",time());
                    		    $cbill['st'] = 1;
                    		    $cbill['remark'] = "[ ".$uinfo['username']." ]一代推广收益 ".$allcount;
                    	        $cbillre = M("bill")->add($cbill);
            	                
            	            }
            	            $allcount=$fee;
            	            if($uinfo['invit_2']!=0)
            	            {
            	                $allcount=$allcount*$invit_2/100;
            		            $uinfoinvit = M("user")->where(array('id'=>$uinfo['invit_2']))->find();
            	                //增加推广并写入日志
                    	        $decre = M("user_coin")->where(array('userid'=>$uinfo['invit_2']))->setInc("usdt",$allcount);
                    	        $decre = M("user")->where(array('id'=>$uinfo['invit_2']))->setInc("invit_2money",$allcount);
                    	        $cbill['uid'] = $uinfo['invit_2'];
                    		    $cbill['username'] = $uinfoinvit['username'];
                    		    $cbill['num'] = $allcount;
                    		    $cbill['coinname'] = "usdt";
                    		    $cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
                    		    $cbill['type'] = 14;
                    		    $cbill['addtime'] = date("Y-m-d H:i:s",time());
                    		    $cbill['st'] = 1;
                    		    $cbill['remark'] = "[ ".$uinfo['username']." ]二代推广收益 ".$allcount;
                    	        $cbillre = M("bill")->add($cbill);
            	                
            	            }
            	            $allcount=$fee;
            	             if($uinfo['invit_3']!=0)
            	            {
            	                $allcount=$allcount*$invit_3/100;
            		            $uinfoinvit = M("user")->where(array('id'=>$uinfo['invit_3']))->find();
            	                //增加推广并写入日志
                    	        $decre = M("user_coin")->where(array('userid'=>$uinfo['invit_3']))->setInc("usdt",$allcount);
                    	        $decre = M("user")->where(array('id'=>$uinfo['invit_3']))->setInc("invit_3money",$allcount);
                    	        $cbill['uid'] = $uinfo['invit_3'];
                    		    $cbill['username'] = $uinfoinvit['username'];
                    		    $cbill['num'] = $allcount;
                    		    $cbill['coinname'] = "usdt";
                    		    $cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
                    		    $cbill['type'] = 15;
                    		    $cbill['addtime'] = date("Y-m-d H:i:s",time());
                    		    $cbill['st'] = 1;
                    		    $cbill['remark'] = "[ ".$uinfo['username']." ]三代推广收益 ".$allcount;
                    	        $cbillre = M("bill")->add($cbill);
            	                
            	            }
	                        echo "==委托订单ID：".$id.",出售成功==";
	                    }
	                    
	                }else{
	                    echo "==委托订单ID：".$id.",没有达到限价出售价格==";
	                }
	            }
	        }
	    }else{
	        echo "没有限价委托可交易！";
	    }
	}
	
	
	//释放冻结的矿机收益币
	//设置一天执行一次的计划任务
	public function releasedjprofit(){
	    $nowday = date("Y-m-d",time());
	    $where['thawday'] = array('elt',$nowday);
	    $where['status'] = array('eq',1);
	    $list = M("djprofit")->where($where)->select();
	    if(!empty($list)){
	        foreach($list as $key=>$vo){
	            $id = $vo['id'];
	            $uid = $vo['uid'];
	            $username = $vo['username'];
	            $num = $vo['num'];
	            $coinname = trim($vo['coin']);
	            $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	            //修改冻结状态
	            M("djprofit")->where(array('id'=>$id))->save(array('status'=>2));
	            //添加财务日志
	            $billdata['uid'] = $uid;
	            $billdata['username'] = $username;
	            $billdata['num'] = $num;
	            $billdata['coinname'] = $coinname;
	            $billdata['afternum'] = $minfo[$coinname] + $num;
	            $billdata['type'] = 8;
	            $billdata['addtime'] = date("Y-m-d H:i:s",time());
	            $billdata['st'] = 1;
	            $billdata['remark'] = L('释放冻结收益');
	            M("bill")->add($billdata);
	            //增加会员资产，减少冻结额度
	            $coinname_d = $coinname."d";
	            M("user_coin")->where(array('userid'=>$uid))->setDec($coinname_d,$num);
	            M("user_coin")->where(array('userid'=>$uid))->setInc($coinname,$num);
	            
	            
	            $notice['uid'] = $uid;
		        $notice['account'] = $username;
		        $notice['title'] = L('释放冻结收益');
		        $notice['content'] = L('您冻结的矿机收益释放成功，可以交易');
		        $notice['addtime'] = date("Y-m-d H:i:s",time());
		        $notice['status'] = 1;
		        M("notice")->add($notice);
	            
	            
	            
	            echo "==ID:".$id."释放".$num.$coinname."成功==";
	            echo "<br />";
	        }
	    }else{
	        echo "====没有可释放的冻结记录====";
	    }
	}
	
	//共享矿机自动结算收益，设置一天执行一次的计划任务
	public function authsharesjsy(){
	    $kjlist = M("kjorder")->where(array('status'=>1,'type'=>2))->select();
	    if(!empty($kjlist)){
	        foreach($kjlist as $key=>$vo){
	           $id = $vo['id'];
	           $uid = $vo['uid'];
	           $username = $vo['username'];
	           $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	           $kid = $vo['kid'];
	           $nowdate = date("Y-m-d",time());
	           $profitinfo = M("kjprofit")->where(array('uid'=>$uid,'kid'=>$id,'day'=>$nowdate))->find();
	           if(empty($profitinfo)){
	               
	               $sharbltxt = $vo['sharbltxt'];
	               
	               if($sharbltxt <= 0){
	                   
	                    echo "===共享矿机ID".$id."共享码有误===";
	                   
	               }else{
	                    $sharekj = M("kjorder")->where(array('sharbltxt'=>$sharbltxt))->count();
	                    if($sharekj >= 2){
	                        //查找矿机收益的类型以及查找收益是否需要冻结及冻结天数
	                         $outtype = $vo['outtype'];
	                         if($outtype == 1){//按产值需要查找产出币种的最新行情
	                             $coinname = strtolower(trim($vo['outcoin']));
	                             $outnum = $vo['outusdt'];
	                             $symbol = $coinname.'usdt';
	                             $coinapi = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=".$symbol;
	                             $newprice = $this->getnewprice($coinapi);
                                 $tcoinnum = sprintf("%.6f",($outnum / $newprice)); //实际产生的币量，保留6位小数
	                         }elseif($outtype == 2){
	                             $coinname = strtolower(trim($vo['outcoin']));
	                             $tcoinnum = $vo['outnum'];
	                         }
	                         $djout = $vo['djout'];//1冻结2不冻结
	                         $djday = $vo['djnum'];//冻结天数
	                         //写入矿机收益日志
	                         $kjprofit_d['uid'] = $uid;
	                         $kjprofit_d['username'] = $username;
	                         $kjprofit_d['kid'] = $id;
	                         $kjprofit_d['ktitle'] = $vo['kjtitle'];
	                         $kjprofit_d['num'] = $tcoinnum;
	                         $kjprofit_d['coin'] = $coinname;
	                         $kjprofit_d['addtime'] = date("Y-m-d H:i:s",time());
	                         $kjprofit_d['day'] =  date("Y-m-d",time());
	                         M("kjprofit")->add($kjprofit_d);
	                         if($djout == 2){
	                             $coin_d = $coinname."d";
	                             M("user_coin")->where(array('userid'=>$uid))->setInc($coin_d,$tcoinnum);
	                             $djprofit_d['uid'] = $uid;
	                             $djprofit_d['username'] = $username;
	                             $djprofit_d['num'] = $tcoinnum;
	                             $djprofit_d['coin'] = $coinname;
	                             $djprofit_d['status'] = 1;
	                             $djprofit_d['addtime'] = date("Y-m-d H:i:s",time());
	                             $djprofit_d['addday'] = date("Y-m-d",time());
	                             $djprofit_d['thawtime'] = date("Y-m-d H:i:s",(time() + 86400 * $djday));
	                             $djprofit_d['thawday'] = date("Y-m-d",(time() + 86400 * $djday));
	                             $djprofit_d['remark'] = L('冻结矿机释放收益');
                
	                             M("djprofit")->add($djprofit_d);
	                             //写资金日志
	                             $billdata['uid'] = $uid;
	                             $billdata['username'] = $username;
	                             $billdata['num'] = $tcoinnum;
	                             $billdata['coinname'] = $coinname;
	                             $billdata['afternum'] = $minfo[$coin_d] + $tcoinnum;
	                             $billdata['type'] = 7;
	                             $billdata['addtime'] = date("Y-m-d H:i:s",time());
	                             $billdata['st'] = 1;
	                             $billdata['remark'] = L('矿机收益释放冻结');
	                             M("bill")->add($billdata);
	                             
	                             $notice['uid'] = $uid;
		                         $notice['account'] = $username;
		                         $notice['title'] = L('矿机收益');
		                         $notice['content'] = L('今日矿机收益已成功到账，请注册查收');
		                         $notice['addtime'] = date("Y-m-d H:i:s",time());
		                         $notice['status'] = 1;
		                         M("notice")->add($notice);
	                             
	                         }elseif($djout == 1){
	                             M("user_coin")->where(array('userid'=>$uid))->setInc($coinname,$tcoinnum);
	                             //写资金日志
	                             $billdata['uid'] = $uid;
	                             $billdata['username'] = $username;
	                             $billdata['num'] = $tcoinnum;
	                             $billdata['coinname'] = $coinname;
	                             $billdata['afternum'] = $minfo[$coinname] + $tcoinnum;
	                             $billdata['type'] = 8;
	                             $billdata['addtime'] = date("Y-m-d H:i:s",time());
	                             $billdata['st'] = 1;
	                             $billdata['remark'] = L('矿机收益释放');
	                             M("bill")->add($billdata);
	                         }
	                         
	                         //修改矿机收益次数
	                         M("kjorder")->where(array('id'=>$id))->setDec("synum",1);
	                         $reinfo = M("kjorder")->where(array('id'=>$id))->find();
	                         if($reinfo['synum'] < 1){
	                             M("kjorder")->where(array('id'=>$id))->save(array('status'=>3));
	                         }
	                         echo "==共享矿机ID:".$kid."收益成功==";
	                         echo "<br />";
        
	                    }else{
	                        echo "===共享矿机ID".$id."另一部分没有购买，不能收益===";
	                    }
	               }
	               
	           }else{
	               echo "==矿机ID:".$kid."不能重复收益==";
	               echo "<br />";
	           }
	           
	           
	       }
	    }
	    
	}
	
	//独资矿机自动收益，每天执行一次
	//设置一天执行一次的计划任务
	public function autokjsy(){
	   $kjlist = M("kjorder")->where(array('status'=>1,'type'=>1))->select();
	   if(!empty($kjlist)){
	       foreach($kjlist as $key=>$vo){
	           $id = $vo['id'];
	           $uid = $vo['uid'];
	           $username = $vo['username'];
	           $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	           $kid = $vo['kid'];
	           $nowdate = date("Y-m-d",time());
	           $profitinfo = M("kjprofit")->where(array('uid'=>$uid,'kid'=>$id,'day'=>$nowdate))->find();
	           if(empty($profitinfo)){
	               //查找矿机收益的类型以及查找收益是否需要冻结及冻结天数
	               $outtype = $vo['outtype'];
	               if($outtype == 1){//按产值需要查找产出币种的最新行情
	                   $coinname = strtolower(trim($vo['outcoin']));
	                   $outnum = $vo['outusdt'];
	                   $symbol = $coinname.'usdt';
	                   $coinapi = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=".$symbol;
	                   $newprice = $this->getnewprice($coinapi);
                       $tcoinnum = sprintf("%.6f",($outnum / $newprice)); //实际产生的币量，保留6位小数
	               }elseif($outtype == 2){
	                   $coinname = strtolower(trim($vo['outcoin']));
	                   $tcoinnum = $vo['outnum'];
	               }
	               $djout = $vo['djout'];//1冻结2不冻结
	               $djday = $vo['djnum'];//冻结天数
	               //写入矿机收益日志
	               $kjprofit_d['uid'] = $uid;
	               $kjprofit_d['username'] = $username;
	               $kjprofit_d['kid'] = $id;
	               $kjprofit_d['ktitle'] = $vo['kjtitle'];
	               $kjprofit_d['num'] = $tcoinnum;
	               $kjprofit_d['coin'] = $coinname;
	               $kjprofit_d['addtime'] = date("Y-m-d H:i:s",time());
	               $kjprofit_d['day'] =  date("Y-m-d",time());
	               M("kjprofit")->add($kjprofit_d);
	               if($djout == 2){
	                   $coin_d = $coinname."d";
	                   M("user_coin")->where(array('userid'=>$uid))->setInc($coin_d,$tcoinnum);
	                   $djprofit_d['uid'] = $uid;
	                   $djprofit_d['username'] = $username;
	                   $djprofit_d['num'] = $tcoinnum;
	                   $djprofit_d['coin'] = $coinname;
	                   $djprofit_d['status'] = 1;
	                   $djprofit_d['addtime'] = date("Y-m-d H:i:s",time());
	                   $djprofit_d['addday'] = date("Y-m-d",time());
	                   $djprofit_d['thawtime'] = date("Y-m-d H:i:s",(time() + 86400 * $djday));
	                   $djprofit_d['thawday'] = date("Y-m-d",(time() + 86400 * $djday));
	                   $djprofit_d['remark'] = L('冻结矿机释放收益');

	                   M("djprofit")->add($djprofit_d);
	                   //写资金日志
	                   $billdata['uid'] = $uid;
	                   $billdata['username'] = $username;
	                   $billdata['num'] = $tcoinnum;
	                   $billdata['coinname'] = $coinname;
	                   $billdata['afternum'] = $minfo[$coin_d] + $tcoinnum;
	                   $billdata['type'] = 7;
	                   $billdata['addtime'] = date("Y-m-d H:i:s",time());
	                   $billdata['st'] = 1;
	                   $billdata['remark'] = L('矿机收益释放冻结');
	                   M("bill")->add($billdata);
	                   
	                   $notice['uid'] = $uid;
		               $notice['account'] = $username;
		               $notice['title'] = L('矿机收益');
		               $notice['content'] = L('今日矿机收益已成功到账，请注册查收');
		               $notice['addtime'] = date("Y-m-d H:i:s",time());
		               $notice['status'] = 1;
		               M("notice")->add($notice);
	                   
	               }elseif($djout == 1){
	                   M("user_coin")->where(array('userid'=>$uid))->setInc($coinname,$tcoinnum);
	                   //写资金日志
	                   $billdata['uid'] = $uid;
	                   $billdata['username'] = $username;
	                   $billdata['num'] = $tcoinnum;
	                   $billdata['coinname'] = $coinname;
	                   $billdata['afternum'] = $minfo[$coinname] + $tcoinnum;
	                   $billdata['type'] = 8;
	                   $billdata['addtime'] = date("Y-m-d H:i:s",time());
	                   $billdata['st'] = 1;
	                   $billdata['remark'] = L('矿机收益释放');
	                   M("bill")->add($billdata);
	               }
	               
	               //修改矿机收益次数
	               M("kjorder")->where(array('id'=>$id))->setDec("synum",1);
	               $reinfo = M("kjorder")->where(array('id'=>$id))->find();
	               if($reinfo['synum'] < 1){
	                   M("kjorder")->where(array('id'=>$id))->save(array('status'=>3));
	               }
	               echo "==矿机ID:".$kid."收益成功==";
	               echo "<br />";
	           }else{
	               echo "==矿机ID:".$kid."不能重复收益==";
	               echo "<br />";
	           }
	           
	           
	       }
  
	   }else{
	       echo "++||没有正常运行的矿机||++";
	   }
	}
	
	//休验订单自动按风控比例设置订单的盈亏比例
	//设置成5-10秒执行一次的计划任务
	public function setwl_ty(){
	    $map['status'] = 1;
	    $map['kongyk'] = 0;
	    $orderobj = M("tyhyorder");
	    $count = $orderobj->where($map)->count();
	    $setting = M("hysetting")->where(array('id'=>1))->field("hy_fkgl")->find();
        if($setting['hy_fkgl'] > 0){
            $ylcount = intval($count * $setting['hy_fkgl'] / 100);
            
            $kscount = $count - $ylcount;
            if($ylcount > 0){
                $yllist = $orderobj->where($map)->order("num asc")->limit($ylcount)->select();
                if(!empty($yllist)){
                    foreach($yllist as $k=>$v){
                        $yid = $v['id'];
                        $orderobj->where(array('id'=>$yid))->save(array('kongyk'=>1));
                        echo "订单ID:".$yid."设为盈利==";
                    }
                }
            }
            
            if($kscount > 0){
                $kslist = $orderobj->where($map)->order("num asc")->limit($kscount)->select();
                if(!empty($kslist)){
                    foreach($kslist as $k=>$v){
                        $kid = $v['id'];
                        $orderobj->where(array('id'=>$kid))->save(array('kongyk'=>2));
                        echo "订单ID:".$kid."设为亏损==";
                    }
                }
            }
        }
        
        echo "操作成功";
	}
	
	
	//自动按风控比例设置订单的盈亏比例
	//设置成5-10秒执行一次的计划任务
	public function setwl(){
	  // exit('已停用');
	    $map['status'] = 1;
	    $map['kongyk'] = 0;
	    $orderobj = M("hyorder");
	    shuffle($orderobj);
	    $count = $orderobj->where($map)->count();
	    $setting = M("hysetting")->where(array('id'=>1))->field("hy_fkgl")->find();
        if($setting['hy_fkgl'] > 0){
            $ylcount = intval($count * $setting['hy_fkgl'] / 100);
            
            $kscount = $count - $ylcount;
            if($ylcount > 0){
                $yllist = $orderobj->where($map)->order("num asc")->limit($ylcount)->select();
                 shuffle($yllist);
                 shuffle($yllist);
                // var_dump($yllist);exit;
                if(!empty($yllist)){
                    foreach($yllist as $k=>$v){
                        $yid = $v['id'];
                        if(time() >= $v['intselltime']){
                        $orderobj->where(array('id'=>$yid))->save(array('kongyk'=>1));
                        echo "订单ID:".$yid."设为盈利==\r\n";
                           // var_dump($kid." Y ",$v['intselltime'] > time(),time(),$v['intselltime']);exit;
                        }else{
                        echo "订单ID:".$yid."未超时\r\n";  
                        }
                    }
                }
            }
            
            if($kscount > 0){
                $kslist = $orderobj->where($map)->order("num asc")->limit($kscount)->select();
                 shuffle($kslist);
                 shuffle($kslist);
                if(!empty($kslist)){
                    foreach($kslist as $k=>$v){
                        $kid = $v['id'];
                        
                        if(time() >= $v['intselltime']){
                        $orderobj->where(array('id'=>$kid))->save(array('kongyk'=>2));
                        echo "订单ID:".$kid."设为亏损==\r\n";
                       // var_dump($kid." K ",($v['intselltime'] > time()), time(),$v['intselltime'] );exit;
                        }else{
                        echo "订单ID:".$yid."未超时\r\n";  
                        }
                    }
                }
            }
        }
        
      //  echo "操作成功";
	}
	
	//自动结算体验合约订单
	public function hycarryout_ty(){
        $nowtime = time();	   
        $map['status'] = 1;
        $map['intselltime'] = array('elt',$nowtime);
        $orderobj = M("tyhyorder");
	    $list = $orderobj->where($map)->select();
	    //获取合约参数
	    $setting = M("hysetting")->where(array('id'=>1))->field("hy_ksid,hy_ylid,hy_fkgl")->find();
	    //指定盈利ID组
	    $winarr = explode(',',$setting['hy_ylid']);
	    //指定亏损ID组
	    $lossarr = explode(',',$setting['hy_ksid']);
        //风控比例组
        $fkarr = explode(',',$setting['hy_fkgl']);
        
	    if(!empty($list)){
	        foreach($list as $key=>$vo){
	            $coinname = $vo['coinname'];
	            $coinarr = explode("/",$coinname);
	            $symbol = strtolower($coinarr[0]).strtolower($coinarr[1]);
	            $coinapi = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=".$symbol;
	            $newprice = $this->getnewprice($coinapi);
	            $randnum = "0.".rand(1000,9999);
	            $buyprice = $vo['buyprice'];
	            $otype = $vo['hyzd']; //合约方向
	            $dkong = $vo['kongyk']; //单控设置
	            $uid = $vo['uid'];//会员ID
	            $id = $vo['id'];//记录ID
	            $num = $vo['num'];
	            $hybl = $vo['hybl'];
	            $ylnum = $num * ($hybl / 100);
	            $money = $num + $ylnum;//盈利金额

	            //买涨
	            if($otype == 1){
	                if(in_array($uid,$winarr)){//如果有指定盈利ID，则按盈利结算
	                    if($newprice > $buyprice){
	                        $sellprice = $newprice;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $newprice + $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $buyprice + $randnum;
	                    }

	                    //增加资产
	                    //M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                    M("user")->where(array('id'=>$uid))->setInc("money",$money);
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 1;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $ylnum;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    //写财务日志
	                    //$this->addlog($uid,$vo['username'],$money);
	                }elseif(in_array($uid,$lossarr)){//如果有指定亏损ID，则按亏损结算

	                    //买涨,指定亏损,结算价格要低于买入价格
	                    if($newprice > $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $newprice;
	                    }
	                    
	                    
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 2;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $num;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    
	                }else{//如果未指定盈利和亏损，则按单控的计算
	                    if($dkong == 1){//盈利
	                        
                            if($newprice > $buyprice){
	                            $sellprice = $newprice;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $newprice + $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $buyprice + $randnum;
	                        }
	                        
	                       // echo '买入价格:'.$buyprice;
	                       // echo "<br />";
	                       // echo  '结算价格:'.$sellprice;die;
	                        
	                        //增加资产
	                        //M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                        M("user")->where(array('id'=>$uid))->setInc("money",$money);
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 1;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $ylnum;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                        //写财务日志
	                        //$this->addlog($uid,$vo['username'],$money);
	                            
	                     }elseif($dkong == 2){//亏损
	                        if($newprice > $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $newprice;
	                        }
	                        
	                       // echo '买入价格:'.$buyprice;
	                       // echo "<br />";
	                       // echo  '结算价格:'.$sellprice;die;
	                        
	                        
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 2;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $num;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                    }
	                }
	            //买跌    
	            }elseif($otype == 2){
	                
    
	                if(in_array($uid,$winarr)){//如果有指定盈利ID，则按盈利结算


	                    if($newprice > $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $newprice;
	                    }
	                    

	                    //增加资产
	                    //M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                    M("user")->where(array('id'=>$uid))->setInc("money",$money);
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 1;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $ylnum;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    //写财务日志
	                    //$this->addlog($uid,$vo['username'],$money);
	                }elseif(in_array($uid,$lossarr)){//如果有指定亏损ID，则按亏损结算
	                   
	                
	                    if($newprice > $buyprice){
	                        $sellprice = $newprice;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice + $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $buyprice  + $randnum;
	                    }
	                    
	                   
	                    
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 2;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $num;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                }else{//如果未指定盈利和亏损，则按单控的计算
	                    if($dkong == 1){//盈利
                            if($newprice > $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $newprice;
	                        }

	                        //增加资产
	                        //M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                        M("user")->where(array('id'=>$uid))->setInc("money",$money);
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 1;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $ylnum;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                        //写财务日志
	                        //$this->addlog($uid,$vo['username'],$money);
	                            
	                     }elseif($dkong == 2){//亏损
	                        if($newprice > $buyprice){
	                            $sellprice = $newprice;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice + $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $buyprice  + $randnum;
	                        }
	                        
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 2;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $num;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                    }
	                }
	                
	            }
	            
	          
	            echo "==订单ID:".$id."出售成功==";
	        }
	    }else{
	        echo "没有订单可以结算！";
	    }
	}
	
	//自动结算合约订单
	public function hycarryout(){
        $nowtime = time();	   
        $map['status'] = 1;
        $map['intselltime'] = array('elt',$nowtime);
        $orderobj = M("hyorder");
	    $list = $orderobj->where($map)->select();
	    
	    //获取合约参数
	    $setting = M("hysetting")->where(array('id'=>1))->field("hy_ksid,hy_ylid,hy_fkgl")->find();
	    //指定盈利ID组
	    $winarr = explode('|',$setting['hy_ylid']);
	    //var_dump("<pre>",$list,$setting);exit;
	    //指定亏损ID组
	    $lossarr = explode('|',$setting['hy_ksid']);
        //风控比例组
        $fkarr = explode('|',$setting['hy_fkgl']);
        
	    if(!empty($list)){
	        foreach($list as $key=>$vo){
	            $coinname = $vo['coinname'];
	            $coinarr = explode("/",$coinname);
	            $symbol = strtolower($coinarr[0]).strtolower($coinarr[1]);
	            $coinapi = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=".$symbol;
	            $newprice = $this->getnewprice($coinapi);
	            $randnum = "0.".rand(1000,9999);
	            $buyprice = $vo['buyprice'];
	            $otype = $vo['hyzd']; //合约方向
	            $dkong = $vo['kongyk']; //单控设置
	            $uid = $vo['uid'];//会员ID
	            $id = $vo['id'];//记录ID
	            $num = $vo['num'];
	            $hybl = $vo['hybl'];
	            $ylnum = $num * ($hybl / 100);
	            $money = $num + $ylnum;//盈利金额

	            //买涨
	            if($otype == 1){

	                if(in_array($uid,$winarr)){//如果有指定盈利ID，则按盈利结算
	                    if($newprice > $buyprice){
	                        $sellprice = $newprice;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $newprice + $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $buyprice + $randnum;
	                    }

	                    //增加资产
	                    M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 1;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $ylnum;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    //写财务日志
	                    $this->addlog($uid,$vo['username'],$money);
	                }elseif(in_array($uid,$lossarr)){//如果有指定亏损ID，则按亏损结算

	                    //买涨,指定亏损,结算价格要低于买入价格
	                    if($newprice > $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $newprice;
	                    }
	                    
	                    
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 2;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $num;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    
	                }else{//如果未指定盈利和亏损，则按单控的计算
	                    if($dkong == 1){//盈利
	                        
                            if($newprice > $buyprice){
	                            $sellprice = $newprice;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $newprice + $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $buyprice + $randnum;
	                        }
	                        
	                       // echo '买入价格:'.$buyprice;
	                       // echo "<br />";
	                       // echo  '结算价格:'.$sellprice;die;
	                        
	                        //增加资产
	                        M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 1;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $ylnum;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                        //写财务日志
	                        $this->addlog($uid,$vo['username'],$money);
	                            
	                     }elseif($dkong == 2){//亏损
	                        if($newprice > $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $newprice;
	                        }
	                       // echo '买入价格:'.$buyprice;
	                       // echo "<br />";
	                       // echo  '结算价格:'.$sellprice;die;
	                        
	                        
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 2;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $num;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                    }
	                }
	            //买跌    
	            }elseif($otype == 2){
	                

	                if(in_array($uid,$winarr)){//如果有指定盈利ID，则按盈利结算


	                    if($newprice > $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice - $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $newprice;
	                    }
	                    

	                    //增加资产
	                    M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 1;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $ylnum;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                    //写财务日志
	                    $this->addlog($uid,$vo['username'],$money);
	                }elseif(in_array($uid,$lossarr)){//如果有指定亏损ID，则按亏损结算
	                   
	                
	                    if($newprice > $buyprice){
	                        $sellprice = $newprice;
	                    }elseif($newprice == $buyprice){
	                        $sellprice = $buyprice + $randnum;
	                    }elseif($newprice < $buyprice){
	                        $sellprice = $buyprice  + $randnum;
	                    }
	                    
	                   
	                    
	                    //修改订单状态
	                    $sd['status'] = 2;
	                    $sd['is_win'] = 2;
	                    $sd['sellprice'] = $sellprice;
	                    $sd['ploss'] = $num;
	                    $orderobj->where(array('id'=>$id))->save($sd);
	                }else{//如果未指定盈利和亏损，则按单控的计算
	                    if($dkong == 1){//盈利
                            if($newprice > $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice - $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $newprice;
	                        }

	                        //增加资产
	                        M("user_coin")->where(array('userid'=>$uid))->setInc("usdt",$money);
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 1;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $ylnum;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                        //写财务日志
	                        $this->addlog($uid,$vo['username'],$money);
	                            
	                     }elseif($dkong == 2){//亏损
	                        if($newprice > $buyprice){
	                            $sellprice = $newprice;
	                        }elseif($newprice == $buyprice){
	                            $sellprice = $buyprice + $randnum;
	                        }elseif($newprice < $buyprice){
	                            $sellprice = $buyprice  + $randnum;
	                        }
	                        
	                        //修改订单状态
	                        $sd['status'] = 2;
	                        $sd['is_win'] = 2;
	                        $sd['sellprice'] = $sellprice;
	                        $sd['ploss'] = $num;
	                        $orderobj->where(array('id'=>$id))->save($sd);
	                    }
	                }
	                
	            }
	            echo "==订单ID:".$id."出售成功==";
	        }
	    }else{
	        echo "没有订单可以结算！";
	    }
	}
	
	//写财务日志
	public function addlog($uid,$username,$money){
	    $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	    $data['uid'] = $uid;
	    $data['username'] = $username;
	    $data['num'] = $money;
	    $data['coinname'] = "usdt";
	    $data['afternum'] = $minfo['usdt'] + $money;
	    $data['type'] = 4;
	    $data['addtime'] = date("Y-m-d H:i:s",time());
	    $data['st'] = 1;
	    $data['remark'] = L('合约出售');
	    M("bill")->add($data);
	    
	    $notice['uid'] = $uid;
		$notice['account'] = $username;
		$notice['title'] = L('秒合约交易');
		$notice['content'] = L('秒合约已平仓，请及时加仓');
		$notice['addtime'] = date("Y-m-d H:i:s",time());
		$notice['status'] = 1;
		M("notice")->add($notice);
	    
	    
	}
		//认购币利息结算
    public function rengoubifafang(){
        $bilist= M("issue_log")->where(['status'=>1])->select();
     //   if(!empty($bilist)){
      //      exit('没有需要发放收益的订单');
    //    }
        foreach ($bilist as $val){
           $starttime= strtotime($val['addtime']);
          $time=time();
          $day=$time-$starttime;
          $yesday=floor($day/86400);//当前天数
          $bizhong= M("issue")->where(["id"=>$val['pid'],'status'=>1])->find();
         
          $lixi=$val['mum'] * ($bizhong['lixi'] / 100) *$yesday;//金额乘以 利息百分比 乘以购买天数
           $yugulixi= $val['mum'] * ($bizhong['lixi'] / 100) *$bizhong['lockday'];//金额乘以 利息百分比 乘以锁仓天数
          if($yesday > 0 && $yesday <= $bizhong['lockday']){//天数必须大于0 且小于等于总天数
              $upshouyi= M("issue_log")->where(array('id'=>$val['id']))->save(["lixi_money"=>$lixi,"yugu"=>$yugulixi]);
              echo "用户ID:". $val['uid']." 认购的".$val['mum']." USDT ".$yesday."天 收益".$lixi."USDT 已发放<br>";
          }
         
        }
      
    }
      public function rengoubimoneyup(){
       /*更新认购币单价*/
        $rengoubi= M("issue")->where(['id'=>3])->find();
           $rgstarttime= strtotime($rengoubi['starttime']);
          $time1=time();
          $day=$time1-$rgstarttime;
          $yesday=floor($day/86400);//当前天数
          $lixi=$rengoubi['yuan_price'] * ($rengoubi['lixi'] / 100) *$yesday;//金额乘以 利息百分比 乘以购买天数
   
          M("issue")->where(['id'=>3])->setInc("price",$lixi); 
          echo "原认购币价格：".$rengoubi['yuan_price']."价格已更新至：".$rengoubi['price']+$lixi;
         
    }
      public function rengoubijiesuan(){
          
          //计算所有代
        /*   $bilist= M("user")->select();
            foreach ($bilist as $val){
                if($val['invit_3'] != 0){
                       $dai = $val['invit_3'].",". $val['invit_2'].",". $val['invit_1'];
                }else if($val['invit_2'] != 0){
                      $dai = $val['invit_2'].",". $val['invit_1'];
                }else if($val['invit_1'] != 0){
                      $dai = $val['invit_1'];
                }
              
               if(!empty($dai)){
                   
             M('user')->where(["id"=>$val['id']])->save(array('path' => $dai));
               }
            }
            exit;*/
            
            
          //新增用户钱包
        /*  $bilist= M("user")->select();
           foreach ($bilist as $val){
               
          $ucion=M("user_coin")->where(["id"=>$val['id']])->find();
          if(empty($ucion)){
		            M('user_coin')->add(array('userid' => $val['id']));
          }
           }
           exit;*/
          
     // 删除无效用户的钱包
      /*    $bilist= M("user_coin")->select();
         // var_dump("<pre>",$bilist);exit;
          foreach ($bilist as $val){
          $ucion=M("user")->where(["id"=>$val['userid']])->find();
     //var_dump($ucion);exit;
              if(empty($ucion)){
                   M("user_coin")->where(array('userid'=>$val['userid']))->delete();
                 
                  echo M()->_sql();
              }else{
                  echo $val['userid'].'存在，无需删除';
              }
          }
          
          exit;*/
          
        $bilist= M("issue_log")->where(['status'=>2,"jiesuan"=>0])->select();
       
           if(empty($bilist)){
           exit('没有认购币订单可以结算');
        }
          foreach ($bilist as $val){
           $starttime= strtotime($val['addtime']);
          $time=time();
          $day=$time-$starttime;
          $yesday=floor($day/86400);//当前天数
          $bizhong= M("issue")->where(["id"=>$val['pid'],'status'=>1])->find();
         
          $lixi=$val['mum'] * ($bizhong['lixi'] / 100) *$yesday;//金额乘以 利息百分比 乘以购买天数
           $yugulixi= $val['mum'] * ($bizhong['lixi'] / 100) *$bizhong['lockday'];//金额乘以 利息百分比 乘以锁仓天数
         
            $uinfo = M("user")->where(array('O.id'=>$val['uid']))->alias('as O') //给表取个别名  ID逆序必须指定是哪个表 是o  还是  W
        ->join('LEFT JOIN `' . C('DB_PREFIX') . 'user_coin` AS W ON W.userid = O.id')->find();
             //增加资产
	       $addmoney=    M("user_coin")->where(array('userid'=>$val['uid']))->setInc("usdt",$lixi); 
	       if($addmoney){
	            $billData=array(
    	            "uid"=>$val['uid'],
    	           "username"=>$uinfo['username'],
    	           "coinname"=>$bizhong['buycoin'],
    	           "num"=>$lixi,
    	           "afternum"=>$uinfo[$bizhong['buycoin']]+$lixi,
    	           "type"=>8,
    	           "addtime"=>date("Y-m-d H:i:s"),
    	           "st"=>1,
    	           "remark"=>"认购币到期解冻收益:".$bizhong['lixi']."% /天",
    	           );
    	           M("bill")->add($billData);
    	           M("issue_log")->where(['id'=>$val['id']])->save(['jiesuan'=>1]);
    	           if($addmoney){
    	           echo "用户ID:". $val['uid']." 认购的".$val['mum']." USDT ".$yesday."天 收益".$lixi."USDT 已转到可提现余额<br>";
    	               
    	           }
	       }
      }
      }
      
       public function recharge_jiankong(){
         $addtime=date("Y-m-d")." 00:00:00";
        $list=M('recharge')->where(["coin"=>'USDT',"atype"=>'0',"czline"=>'TRC20',"status"=>'1',"addtime"=>["gt",$addtime]])->order('id desc')->select();/*"*/
        //echo M()->_sql();
   //var_dump($list);exit;
   if(empty($list)){
       exit("暂时没有需要查询的订单！");
   }
        foreach ($list as $item){
            if(!empty($item['address']) && $item['czline'] =="TRC20" && mb_substr($item['address'],0,1)=="T"){
         
                      $trc20    = $this->getTransferInList($item['address'], 24);//trc20地址
                      $trc20=$trc20[0];
                      
               // var_dump($item['num'] , sprintf("%.6f",$trc20["money"]),$item['num'] == sprintf("%.6f",$trc20["money"]));
                    
                if ($item['num'] == sprintf("%.6f",$trc20["money"]) && $trc20['time'] >= $item['addtime'] && $trc20['mybuyer'] == $item['address'] && $item['trc20id'] != $trc20['trc20id'] && $trc20['tokenAbbr'] =="USDT"  && $trc20['finalResult'] == "SUCCESS") {//必须满足六个条件，第一USDT金额相同，第二时间在20分钟以内，第三收款地址是指定的地址  第四trc20id不能相同 第五是必须币种是USDT，而不能是USTD或其他币种  第六 订单最终状态必须为SUCCESS
              $info = M("user")->where(array('id'=>$item['uid']))->order('id desc')->find();    
                 $uid = $item['uid'];
	    $num = $item['num'];
	    $coinname = strtolower(trim($item['coin']));
	    $minfo = M("user_coin")->where(array('userid'=>$uid))->find();
	    //修改订单状态
	    $save['updatetime'] = date("Y-m-d H:i:s",time());
	    $save['status'] = 2;
       
         $save['trc20id']=$trc20['trc20id'];//交易信息ID
	    $upre = M("recharge")->where(array('id'=>$item['id']))->save($save);
	    //增加会员资产
	    $incre = M("user_coin")->where(array('userid'=>$uid))->setInc($coinname,$num);
	    //增加充值日志
	    $data['uid'] = $item['uid'];
	    $data['username'] = $info['username'];
	    $data['num'] = $num;
	    $data['coinname'] = $coinname;
	    $data['afternum'] = $minfo[$coinname] + $num;
	    $data['type'] = 17;
	    $data['addtime'] = date("Y-m-d H:i:s",time());
	    $data['st'] = 1;
	    $data['remark'] = L('充币到账');
	    $addre = M("bill")->add($data);
	    if($upre && $incre && $addre){
	        
	        $notice['uid'] = $info['id'];
		    $notice['account'] = $info['username'];
		    $notice['title'] = L('充币审核');
		    $notice['content'] = L('您的充值金额已到账，请注意查收');
		    $notice['addtime'] = date("Y-m-d H:i:s",time());
		    $notice['status'] = 1;
		    M("notice")->add($notice);
	        
        echo $info['username'] ." 用户充值".$num."个 USDT已到账！\r\n";
	    }else{
	       
        echo $info['username'] ." 用户充值".$num."个 USDT 处理失败！！！！\r\n";
	    }
                
                }else{
                    
                echo "未查询到用户：".$item['username']."的金额为：".$item['num']."的支付信息 \r\n";exit;
                }

            }else{
                
             echo "没有需要查询的订单 \r\n";exit;
            }
        }
       }
        //对USDT地址执行查询  Telegram：@luckystarts 
    public function getTransferInList($address, $hour = 3)
    {
        $result = [];
        $end    = time() * 1000;
        $start  = (time()-1800) * 1000;//监控时间  20分钟
        $params = [
            'limit'           => 300,
            'start'           => 0,
            'direction'       => 'in',
            'relatedAddress'  => $address,
            'start_timestamp' => $start,
            'end_timestamp'   => $end,
        ];
        
        $api    = "https://apilist.tronscan.org/api/token_trc20/transfers?" . http_build_query($params);
       $resp   = $this->get_curl($api);
 //  var_dump($api.$resp);exit;
        $data   = json_decode($resp, true);
        if (empty($data)) {

            return $result;
        }

        foreach ($data['token_transfers'] as $transfer) {
            if ($transfer['to_address'] == $address && $transfer['finalResult'] == 'SUCCESS') {
                $result[] = [
                    'time'     => $transfer['block_ts'] / 1000,
                    'money'    => $transfer['quant'] / 1000000,
                    'trade_id' => $transfer['transaction_id'],
                    'buyer'    => $transfer['from_address'],
                    'mybuyer'    => $transfer['to_address'],
                    'trc20id'    => $transfer['transaction_id'],
                    'tokenAbbr'  => $transfer['tokenInfo']['tokenAbbr'],//必须币种是USDT
                    'finalResult'  => $transfer['finalResult'],//最终结果必须是成功的
                ];
            }
        }
  
    file_put_contents(dirname( __FILE__ ).'/~USDTjiankong-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log',date('Y-m-d H:i:s').'监控地址：'.$api.$order['pay_orderid'].'完整监控信息：'.$resp."重要数据".json_encode($result)."\n", FILE_APPEND);
        return $result;
    }
         //curl get访问
    public function get_curl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Linux; U; Android 4.4.1; zh-cn) AppleWebKit/533.1 (KHTML, like Gecko)Version/4.0 MQQBrowser/5.5 Mobile Safari/533.1');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
      
	//获取行情数据
    public function getnewprice($api){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt ($ch, CURLOPT_URL, $api );
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT,10);
        $result = json_decode(curl_exec($ch),true);
        $price_arr = $result['data'][0];
        $close = $price_arr['close'];//现价
        return $close;
    }
	

}
?>