<?php
/* 交易中心 */

namespace Mobile\Controller;

class TradeController extends MobileController
{

	protected function _initialize()
	{
		parent::_initialize();
		$allow_action = array("index", "tradelist", "ordinary", "trans", "transinfo", "upbbbuy", "getprice", "upbbsell", "clearorder", "tradebill", "billinfo");
		if (!in_array(ACTION_NAME, $allow_action)) {
			$this->error(L("非法操作"));
		}
	}

	//成交详情
	public function billinfo($id = null)
	{
		$uid = userid();
		if ($uid <= 0) {
			$this->redirect('Login/index');
		}
		$info = M("bborder")->where(array('id' => $id))->find();
		$this->assign('info', $info);
		$this->display();
	}


	//全部委托订单及交易记录
	public function tradebill()
	{
		$uid = userid();
		if ($uid <= 0) {
			$this->redirect('Login/index');
		}
		//全总未交易委托
		$list = M("bborder")->where(array('uid' => $uid, 'ordertype' => 1, 'status' => 1))->order('id desc')->select();
		$this->assign('list', $list);
		//全部交易记录
		$alllist = M("bborder")->where("uid = {$uid} and status != 1")->order('id desc')->select();
		$this->assign('alllist', $alllist);
		//全部成交记录
		$finishlist = M("bborder")->where(array('uid' => $uid, 'status' => 2))->order('id desc')->select();
		$this->assign('finishlist', $finishlist);

		$this->display();
	}


	//取消委托订单
	public function clearorder($oid = null)
	{
		if (checkstr($oid)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('缺少重要参数')]);
		}

		$oinfo = M("bborder")->where(array('id' => $oid))->find();
		if (empty($oinfo)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('委托订单不存在')]);
		}
		$uid = $oinfo['uid'];
		$type = $oinfo['type'];

		//买入委托
		if ($type == 1) {
			$coin = "usdt";
			$num = $oinfo['usdtnum'];

			//出售委托   
		} elseif ($type == 2) {
			$coin = strtolower($oinfo['coin']);
			$num = $oinfo['coinnum'];
		}
		$upre = M("bborder")->where(array('id' => $oid))->save(array('status' => 3));

		$coind = $coin . "d";
		//把冻结的资产转移到可用资产里
		$decre = M("user_coin")->where(array('userid' => $uid))->setDec($coind, $num);
		$incre = M("user_coin")->where(array('userid' => $uid))->setInc($coin, $num);

		if ($upre && $decre && $incre) {
			$this->ajaxReturn(['code' => 1, 'info' => "订单已撤消"]);
		} else {
			$this->ajaxReturn(['code' => 0, 'info' => "订单撤消失败"]);
		}
	}

	//币币交易出售处理
	public function upbbsell($symbol = null, $mprice = null, $mnum = null, $musdt = null, $selltype = null, $type = null)
	{
		if (checkstr($symbol) || checkstr($mprice) || checkstr($mnum) || checkstr($musdt) || checkstr($buytype) || checkstr($type)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('您输入的信息有误')]);
		}

		//判断是否开市
		$nowtime = date('H:i', time());
		$bbset = M("bbsetting")->where(array('id' => 1))->find();
		$kstime = explode("~", $bbset['bb_kstime']);
		$start = $kstime[0];
		$end = $kstime[1];
		if ($nowtime < $start || $nowtime > $end) {
			$this->ajaxReturn(['code' => 0, 'info' => L('未开市')]);
		}

		//判断登陆
		$uid = userid();
		$uinfo = M("user")->where(array('id' => $uid))->field("id,username,rzstatus,buy_on,invit_1,invit_2,invit_3")->find();
		if (empty($uinfo)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('请先登录')]);
		}
		if ($uinfo['buy_on'] == "2") {
			$this->ajaxReturn(['code' => 0, 'info' => L('您的账户已被禁止交易，请联系客服')]);
		}
		if ($uinfo['rzstatus'] != 2) {
			$this->ajaxReturn(['code' => 0, 'info' => L('请先完成实名认证')]);
		}

		if ($symbol == '') {
			$this->ajaxReturn(['code' => 0, 'info' => L('缺少重要参数')]);
		}
		$arr = explode("/", $symbol);
		$coin = $arr[0];

		if ($coin == "MBN") {
			$marketinfo = M("ctmarket")->where(array('coinname' => "mbn"))->field("state")->find();
			if ($marketinfo['state'] != 1) {
				$this->ajaxReturn(['code' => 0, 'info' => L('禁止交易')]);
			}
		}


		$lowercname = strtolower($arr[0]);
		$lowercoin = strtolower($arr[0]) . strtolower($arr[1]);
		$coinname = strtoupper($coin);

		//查手续费比例
		$coininfo = M("coin")->where(array('name' => $coinname))->field("bbsxf")->find();
		$sxf = $coininfo['bbsxf'];
		if ($sxf < 0) {
			$sxf = 0;
		}

		//查账户余额
		$minfo = M("user_coin")->where(array('userid' => $uid))->find();
		$coin_blance = $minfo[$lowercname];
		if ($coin_blance < $mnum) {
			$mnum = $coin_blance;
			//$this->ajaxReturn(['code'=>0,'info'=>L($lowercname.'余额不足')]);
		}

		//限价必须有出售数量 限价单价
		if ($selltype == 1) {
			if ($mnum <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入出售额度')]);
			}
			if ($mprice <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入限价价格')]);
			}

			//写入交易记录
			$xjdata['uid'] = $uid;
			$xjdata['account'] = $uinfo['username'];
			$xjdata['type'] = 2;
			$xjdata['ordertype'] = 1;
			$xjdata['symbol'] = $symbol;
			$xjdata['coin'] = $coin;
			$xjdata['coinnum'] = $mnum;
			$xjdata['xjprice'] = $mprice;
			$xjdata['addtime'] = date("Y-m-d H:i:s", time());
			$xjdata['tradetime'] = date("Y-m-d H:i:s", time());
			$xjdata['coin'] = $coin;
			$xjdata['status'] = 1;
			$xjdata['sxfbl'] = $sxf;
			$xjdata['usdtnum'] = $musdt;
			$xjdata['price'] = 0;
			$xjdata['fee'] = 0;
			$addre = M("bborder")->add($xjdata);
			//把资产转入冻结字段
			$decre = M("user_coin")->where(array('userid' => $uid))->setDec($lowercname, $mnum);
			$incre = M("user_coin")->where(array('userid' => $uid))->setInc($lowercname . "d", $mnum);
			if ($addre && $decre && $incre) {
				$this->ajaxReturn(['code' => 1, 'info' => L('订单委托成功')]);
			} else {
				$this->ajaxReturn(['code' => 0, 'info' => L('订单委托失败')]);
			}

			//市价  出售数量,获取当前单价
		} elseif ($selltype == 2) {
			if ($mnum <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入出售额度')]);
			}

			if ($coin == "MBN") {
				$priceinfo = M("market")->where(array('name' => "mbn_usdt"))->field("new_price")->find();
				$close = $priceinfo['new_price']; //现价
			} else {
				//获取当前交易对价格
				$url = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=" . $lowercoin;
				$result = $this->getprice($url);
				$pdata = $result['data'][0];
				$close = $pdata['close']; //现价
			}

			//求出卖出所得USDT
			$allusdt = $mnum * $close;
			//手续费
			$sxfnum = $allusdt * $sxf / 100;
			//实际到账USDT
			$tusdt = $allusdt - $sxfnum;

			//写入交易记录
			$sjdata['uid'] = $uid;
			$sjdata['account'] = $uinfo['username'];
			$sjdata['type'] = 2;
			$sjdata['ordertype'] = 2;
			$sjdata['symbol'] = $symbol;
			$sjdata['coin'] = $coin;
			$sjdata['coinnum'] = $mnum;
			$sjdata['usdtnum'] = $tusdt;
			$sjdata['price'] = $close;
			$sjdata['xjprice'] = $close;
			$sjdata['addtime'] = date("Y-m-d H:i:s", time());
			$sjdata['tradetime'] = date("Y-m-d H:i:s", time());
			$sjdata['fee'] = $sxfnum;
			$sjdata['sxfbl'] = $sxf;
			$sjdata['status'] = 2;
			$addre = M("bborder")->add($sjdata);

			//扣除卖出额度并写入日志
			$decre = M("user_coin")->where(array('userid' => $uid))->setDec($lowercname, $mnum);
			$cbill['uid'] = $uid;
			$cbill['username'] = $uinfo['username'];
			$cbill['num'] = $mnum;
			$cbill['coinname'] = $lowercname;
			$cbill['afternum'] = $minfo[$lowercname] - $mnum;
			$cbill['type'] = 10;
			$cbill['addtime'] = date("Y-m-d H:i:s", time());
			$cbill['st'] = 2;
			$cbill['remark'] = L('币币交易出售') . $coin;
			$cbillre = M("bill")->add($cbill);

			//增加USDT数量并写入日志
			$incre = M("user_coin")->where(array('userid' => $uid))->setInc("usdt", $tusdt);
			$ubill['uid'] = $uid;
			$ubill['username'] = $uinfo['username'];
			$ubill['num'] = $tusdt;
			$ubill['coinname'] = "usdt";
			$ubill['afternum'] = $minfo['usdt'] + $tusdt;
			$ubill['type'] = 9;
			$ubill['addtime'] = date("Y-m-d H:i:s", time());
			$ubill['st'] = 1;
			$ubill['remark'] = L('币币交易出售') . $coin;
			$ubillre = M("bill")->add($ubill);

			if ($addre && $decre && $cbillre && $incre && $ubillre) {
				//各个推广分成
				//一代
				$allcount = $sxfnum;
				$invit_1 = 0;
				$invit_2 = 0;
				$invit_3 = 0;
				$donfig = M("config")->where(array('id' => 1))->find();
				if ($donfig) {
					$invit_1 = $donfig['invert1'];
					$invit_2 = $donfig['invert2'];
					$invit_3 = $donfig['invert3'];
				}
				$allcount = $sxfnum;
				if ($uinfo['invit_1'] != 0) {
					$allcount = $allcount * $invit_1 / 100;
					$uinfoinvit = M("user")->where(array('id' => $uinfo['invit_1']))->find();
					//增加推广并写入日志
					$decre = M("user_coin")->where(array('userid' => $uinfo['invit_1']))->setInc("usdt", $allcount);
					$decre = M("user")->where(array('id' => $uinfo['invit_1']))->setInc("invit_1money", $allcount);
					$cbill['uid'] = $uinfo['invit_1'];
					$cbill['username'] = $uinfoinvit['username'];
					$cbill['num'] = $allcount;
					$cbill['coinname'] = "usdt";
					$cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
					$cbill['type'] = 13;
					$cbill['addtime'] = date("Y-m-d H:i:s", time());
					$cbill['st'] = 1;
					$cbill['remark'] = "[ " . $uinfo['username'] . " ]一代推广收益 " . $allcount;
					$cbillre = M("bill")->add($cbill);
				}
				$allcount = $sxfnum;
				if ($uinfo['invit_2'] != 0) {
					$allcount = $allcount * $invit_2 / 100;
					$uinfoinvit = M("user")->where(array('id' => $uinfo['invit_2']))->find();
					//增加推广并写入日志
					$decre = M("user_coin")->where(array('userid' => $uinfo['invit_2']))->setInc("usdt", $allcount);
					$decre = M("user")->where(array('id' => $uinfo['invit_2']))->setInc("invit_2money", $allcount);
					$cbill['uid'] = $uinfo['invit_2'];
					$cbill['username'] = $uinfoinvit['username'];
					$cbill['num'] = $allcount;
					$cbill['coinname'] = "usdt";
					$cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
					$cbill['type'] = 14;
					$cbill['addtime'] = date("Y-m-d H:i:s", time());
					$cbill['st'] = 1;
					$cbill['remark'] = "[ " . $uinfo['username'] . " ]二代推广收益 " . $allcount;
					$cbillre = M("bill")->add($cbill);
				}
				$allcount = $sxfnum;
				if ($uinfo['invit_3'] != 0) {
					$allcount = $allcount * $invit_3 / 100;
					$uinfoinvit = M("user")->where(array('id' => $uinfo['invit_3']))->find();
					//增加推广并写入日志
					$decre = M("user_coin")->where(array('userid' => $uinfo['invit_3']))->setInc("usdt", $allcount);
					$decre = M("user")->where(array('id' => $uinfo['invit_3']))->setInc("invit_3money", $allcount);
					$cbill['uid'] = $uinfo['invit_3'];
					$cbill['username'] = $uinfoinvit['username'];
					$cbill['num'] = $allcount;
					$cbill['coinname'] = "usdt";
					$cbill['afternum'] = $uinfoinvit["usdt"] + $allcount;
					$cbill['type'] = 15;
					$cbill['addtime'] = date("Y-m-d H:i:s", time());
					$cbill['st'] = 1;
					$cbill['remark'] = "[ " . $uinfo['username'] . " ]三代推广收益 " . $allcount;
					$cbillre = M("bill")->add($cbill);
				}
				$this->ajaxReturn(['code' => 1, "info" => L('出售成功')]);
			} else {
				$this->ajaxReturn(['code' => 0, "info" => L('出售失败')]);
			}
		}
	}


	//币币交易购买处理
	public function upbbbuy($symbol = null, $mprice = null, $mnum = null, $musdt = null, $buytype = null, $type = null)
	{
		if (checkstr($symbol) || checkstr($mprice) || checkstr($mnum) || checkstr($musdt) || checkstr($buytype) || checkstr($type)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('您输入的信息有误')]);
		}

		//判断是否开市
		$nowtime = date('H:i', time());
		$bbset = M("bbsetting")->where(array('id' => 1))->find();
		$kstime = explode("~", $bbset['bb_kstime']);
		$start = $kstime[0];
		$end = $kstime[1];
		if ($nowtime < $start || $nowtime > $end) {
			$this->ajaxReturn(['code' => 0, 'info' => L('未开市')]);
		}

		$uid = userid();
		$uinfo = M("user")->where(array('id' => $uid))->field("id,username,rzstatus,buy_on")->find();
		if (empty($uinfo)) {
			$this->ajaxReturn(['code' => 0, 'info' => L('请先登录')]);
		}
		if ($uinfo['buy_on'] == "2") {
			$this->ajaxReturn(['code' => 0, 'info' => L('您的账户已被禁止交易，请联系客服')]);
		}
		if ($uinfo['rzstatus'] != 2) {
			$this->ajaxReturn(['code' => 0, 'info' => L('请先完成实名认证')]);
		}

		if ($symbol == '') {
			$this->ajaxReturn(['code' => 0, 'info' => L('缺少重要参数')]);
		}
		$arr = explode("/", $symbol);
		$coin = $arr[0];
		if ($coin == "MBN") {
			$marketinfo = M("ctmarket")->where(array('coinname' => "mbn"))->field("state")->find();
			if ($marketinfo['state'] != 1) {
				$this->ajaxReturn(['code' => 0, 'info' => L('禁止交易')]);
			}
		}
		$lowercoin = strtolower($arr[0]) . strtolower($arr[1]);
		$coinname = strtoupper($coin);
		$coininfo = M("coin")->where(array('name' => $coinname))->field("bbsxf")->find();
		$sxf = $coininfo['bbsxf'];
		if ($sxf < 0) {
			$sxf = 0;
		}

		//需要查会员的账号USDT余额
		$minfo = M("user_coin")->where(array('userid' => $uid))->find();

		if ($musdt > $minfo['usdt']) {
			$musdt = $minfo['usdt'];
			//$this->ajaxReturn(['code'=>0,'info'=>L('USDT余额不足')]);
		}

		//限价必须有单价,USDT量
		//市价必须有USDT量,再获取当前最新单价
		if ($buytype == 1) { //限价
			if ($mprice <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入限价价格')]);
			}
			if ($musdt <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入买入金额')]);
			}
			$xjdata['uid'] = $uid;
			$xjdata['account'] = $uinfo['username'];
			$xjdata['type'] = 1;
			$xjdata['ordertype'] = 1;
			$xjdata['symbol'] = $symbol;
			$xjdata['coin'] = $coin;
			$xjdata['usdtnum'] = $musdt;
			$xjdata['xjprice'] = $mprice;
			$xjdata['addtime'] = date("Y-m-d H:i:s", time());
			$xjdata['tradetime'] = date("Y-m-d H:i:s", time());
			$xjdata['coin'] = $coin;
			$xjdata['status'] = 1;
			$xjdata['sxfbl'] = 0; // $sxf;
			$xjdata['coinnum'] = 0;
			$xjdata['price'] = 0;
			$xjdata['fee'] = 0;
			//添加限价委托记录
			$addre = M("bborder")->add($xjdata);
			//把USDT转入冻结字段 
			$decre = M("user_coin")->where(array('userid' => $uid))->setDec('usdt', $musdt);
			$incre = M("user_coin")->where(array('userid' => $uid))->setInc('usdtd', $musdt);
			if ($addre && $decre && $incre) {
				$this->ajaxReturn(['code' => 1, 'info' => L('订单委托成功')]);
			}
		} elseif ($buytype == 2) { //市价
			if ($musdt <= 0) {
				$this->ajaxReturn(['code' => 0, 'info' => L('请输入买入金额')]);
			}
			if ($coin == "MBN") {
				$priceinfo = M("market")->where(array('name' => "mbn_usdt"))->field("new_price")->find();
				$close = $priceinfo['new_price']; //现价
			} else {
				//获取当前交易对价格
				$url = "https://api.huobi.pro/market/history/kline?period=1day&size=1&symbol=" . $lowercoin;
				$result = $this->getprice($url);
				$pdata = $result['data'][0];
				$close = $pdata['close']; //现价
			}

			//计算买入币的量
			$coinnum = sprintf("%.8f", ($musdt / $close));
			//计算手续费
			$sxfnum = $coinnum * $sxf / 100;
			//实际到账号的币量
			$tnum = $coinnum; // - $sxfnum;
			$sjdata['uid'] = $uid;
			$sjdata['account'] = $uinfo['username'];
			$sjdata['type'] = 1;
			$sjdata['ordertype'] = 2;
			$sjdata['symbol'] = $symbol;
			$sjdata['coin'] = $coin;
			$sjdata['coinnum'] = $coinnum;
			$sjdata['usdtnum'] = $musdt;
			$sjdata['price'] = $close;
			$sjdata['xjprice'] = $close;
			$sjdata['addtime'] = date("Y-m-d H:i:s", time());
			$sjdata['tradetime'] = date("Y-m-d H:i:s", time());
			$sjdata['fee'] = 0; //$sxfnum;
			$sjdata['sxfbl'] = 0; // $sxf;
			$sjdata['status'] = 2;

			$lowercoin = strtolower($coin);

			//生成交易记录
			$addre = M("bborder")->add($sjdata);
			//扣除USDT额度并写日志
			$decre = M("user_coin")->where(array('userid' => $uid))->setDec('usdt', $musdt);
			$ubill['uid'] = $uid;
			$ubill['username'] = $uinfo['username'];
			$ubill['num'] = $musdt;
			$ubill['coinname'] = "usdt";
			$ubill['afternum'] = $minfo['usdt'] - $musdt;
			$ubill['type'] = 9;
			$ubill['addtime'] = date("Y-m-d H:i:s", time());
			$ubill['st'] = 2;
			$ubill['remark'] = L('币币交易购买') . $coin;
			$ubillre = M("bill")->add($ubill);
			//增加币种额度并写日志
			$incre = M("user_coin")->where(array('userid' => $uid))->setInc($lowercoin, $tnum);
			$cbill['uid'] = $uid;
			$cbill['username'] = $uinfo['username'];
			$cbill['num'] = $coinnum;
			$cbill['coinname'] = $lowercoin;
			$cbill['afternum'] = $minfo[$lowercoin] + $coinnum;
			$cbill['type'] = 10;
			$cbill['addtime'] = date("Y-m-d H:i:s", time());
			$cbill['st'] = 1;
			$cbill['remark'] = L('币币交易购买') . $coin;
			$cbillre = M("bill")->add($cbill);

			if ($addre && $decre && $ubillre && $incre && $cbillre) {
				$this->ajaxReturn(['code' => 1, 'info' => L('交易成功')]);
			} else {
				$this->ajaxReturn(['code' => 0, 'info' => L('交易失败')]);
			}
		}
	}

	//获取行情数据
	public function getprice($api)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_URL, $api);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		$result = json_decode(curl_exec($ch), true);
		return $result;
	}

	//币币交易详情页面
	public function transinfo()
	{

		$uid = userid();

		$symbol = trim(I('get.symbol'));
		$coin = strtolower($symbol);
		$minfo = M("user_coin")->where(array('userid' => $uid))->find();
		$usdt_blance = $minfo['usdt'];
		$coin_blance = $minfo[$coin];
		$this->assign('usdt_blance', $usdt_blance);

		$this->assign('coin_blance', $coin_blance);
		$this->assign('uid', $uid);
		$coinname = $symbol . "/" . "USDT";
		$this->assign('coinname', $coinname);
		$this->assign('symbol', $symbol);

		$where['ordertype'] = 1;
		$where['status'] = 1;
		$where['uid'] = $uid;
		$where['coin'] = array('eq', $symbol);
		$list = M("bborder")->where($where)->select();
		$this->assign('list', $list);
		$this->assign('select', 'trans');


		$this->display();
	}


	//币币交易页面
	public function trans()
	{
		$uid = userid();
		if ($uid <= 0) {
			$this->redirect('Login/index');
		}
		$this->assign('uid', $uid);
		$sytx  = trim(I('get.sytx'));
		$txarr = explode('/', $sytx);
		$symbol = $txarr[0];
		$market = strtolower($txarr[0] . $txarr[1]);
		if ($symbol == '') {
			$symbol = 'btc';
		}
		if ($market == '') {
			$market = 'btcusdt';
		}
		$upmarket = strtoupper($symbol) . "/USDT";
		$this->assign('upmarket', $upmarket);
		$this->assign('market', $market);
		$this->assign('smybol', $symbol);

		$lowercoin = strtolower($symbol);
		$cmarket = M("ctmarket")->where(array('coinname' => $lowercoin))->field("state")->find();
		$state = $cmarket['state'];
		$this->assign('state', $state);
		$this->assign('select', 'trans');
		$this->display();
	}

	//网站首页
	public function tradelist()
	{

		$uid = userid();
		$this->assign('uid', $uid);
		$clist = M("config")->where(array('id' => 1))->field("websildea,websildeb,websildec")->find();
		$this->assign('clist', $clist);
		$websildec = $clist['websildec'];
		$this->assign('websildec', $websildec);

		$nlist = M("content")->where(array('status' => 1))->order("id desc")->field("title,id")->select();
		$this->assign('nlist', $nlist);

		if ($uid > 0) {
			$sum = M("notice")->where(array('uid' => $uid, 'status' => 1))->count();
		} else {
			$sum = 0;
		}
		$this->assign('sum', $sum);

		$list = M("ctmarket")->where(array('status' => 1))->field("coinname,id,logo")->select();
		unset($list['6']);
		$this->assign("market", $list);


		$info = M("content")->where(array('status' => 1))->order("id desc")->find();
		$this->assign('info', $info);


		$this->assign('select', 'index');


		$this->display();
	}


	//交易市场
	public function index()
	{
		$uid = userid();
		if ($uid <= 0) {
			$this->redirect('Login/index');
		}
		$where['status'] = 1;
		//$where['coinname'] = array('neq','usdz');
		$list = M("ctmarket")->where($where)->field("coinname,id")->select();
		unset($list[6]);
		$this->assign("market", $list);

		$this->assign('select', 'trade');
		$this->display();
	}



	/**
	 * 普通K线图
	 */
	public function ordinary($market = NULL)
	{

		$market = trim(I('get.market'));
		if ($market == '') {
			$market = "btcusdt";
		}
		$this->assign('market', $market);
		$this->display();
	}

	/**
	 * 专业K线图
	 * @param  [type] $market [description]
	 * @return [type]         [description]
	 */
	public function specialty($market = NULL)
	{
		// 过滤非法字符----------------S
		if (checkstr($market)) {
			$this->error(L('您输入的信息有误'));
		}
		// 过滤非法字符----------------E
		if (!$market) {
			$market = C('market_mr');
		}
		$this->assign('market', $market);
		$this->display();
	}
}
