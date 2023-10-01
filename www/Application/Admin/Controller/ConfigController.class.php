<?php
namespace Admin\Controller;

class ConfigController extends AdminController
{
	protected function _initialize()
	{
		parent::_initialize();
		$allow_action=array("index","edit","image","coin","coinEdit","coinStatus","textStatus","coinImage","text","textEdit","qita","qitaEdit","daohang","daohangEdit","daohangStatus","dhfooter","dhfooterEdit","dhfooterStatus","dhadmin","dhadminEdit","dhadminStatus","ctmarket","ctmarketEdit","marketo","marketoEdit","marketoEdit2","marketoEdit3","marketoStatus","ctmarketoStatus","mining","miningEdit","getblance","getblanceall","get_curl","addusdt","gettrx","getguiji");
		if(!in_array(ACTION_NAME,$allow_action)){
			$this->error("页面不存在！".ACTION_NAME);
		}
		 $this->url = 'https://api.trongrld.io'; /*官方API地址*/
           $this->config = [
            'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',// USDT TRC20 合约地址，固定的不要轻易动
            'decimals' => 6, /*精度*/
        ];
	}

    //系统设置首页
	public function index(){
		$this->data = M('Config')->where(array('id' => 1))->find();
		$this->display();
	}

    //编加网站基本配置
	public function edit(){
        $data = I('post.');

		if (M('Config')->where(array('id' => 1))->save($data)) {
            $config = M('Config')->where(['id' => 1])->find();
            session('sys_config', $config);
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}


	public function image(){
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/public/';
		$upload->autoSub = false;
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}
	


	public function coin($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Coin')->where($where)->count();
		$Page = new \Think\Page($count, 100);
		$show = $Page->show();
		$list = M('Coin')->where($where)->order('sort asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
	
	
    ////增加币名称OK
	public function coinEdit($id = NULL)
	{
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = M('Coin')->where(array('id' => trim($_GET['id'])))->find();
			}

			$this->display();
		} else {
		   if($_POST['agent_id']){
		   $bininfo= M('user')->where(array('id' => trim($_POST['agent_id'])))->find();
		   if($bininfo['path']!=""){
		       	$this->error('该代理不是总代，无法设置！');
		   }
		   $bbbb=M('Coin')->where(array('agent_id' => trim($_POST['agent_id']),'name'=>trim($_POST['name'])))->find();
		   if($bbbb){
		       	$this->error('该总代理已经添加过：'.$_POST['name']." 类型的通道啦！请勿重复添加。");
		   }
		       
		   }
			if ($_POST['id']) {
			    
			    $_POST['addtime'] = date("Y-m-d H:i:s",time());
				$rs = M('Coin')->save($_POST);
				
			} else {
			    
				if (!check($_POST['name'], 'n')) {
					$this->error('币种简称只能是小写字母！');
				}

				$_POST['name'] = strtolower($_POST['name']);

				if (check($_POST['name'], 'username')) {
					$this->error('币种名称格式不正确！');
				}

				if (M('Coin')->where(array('name' => $_POST['name']))->find()) {
				//	$this->error('币种存在！');
				}
				
				$_POST['addtime'] = date("Y-m-d H:i:s",time());

			/*	$rea = M()->execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . '` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000');
				$reb = M()->execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . 'd` DECIMAL(20,8) UNSIGNED NOT NULL DEFAULT 0.00000000');
				$rec = M()->execute('ALTER TABLE  `tw_user_coin` ADD  `' . $_POST['name'] . 'b` VARCHAR(200) NOT NULL DEFAULT 0');
*/
				$rs = M('Coin')->add($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',U('Config/coin'));
			} else {
				$this->error('数据未修改！');
			}
		}
	}

	public function coinStatus()
	{
		if (IS_POST) {
			$id = array();
			$id = implode(',', $_POST['id']);
		} else {
			$id = $_GET['id'];
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = $_GET['type'];
		// $this->error($method);
		switch (strtolower($method)) {
			case 'forbid':
				$data = array('status' => 0);
				break;

			case 'resume':
				$data = array('status' => 1);
				break;

			case 'delt':
				$rs = M('Coin')->where($where)->select();

			/*	foreach ($rs as $k => $v) {
					$rs[] = M()->execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name']);
					$rs[] = M()->execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name'] . 'd');
					$rs[] = M()->execute('ALTER TABLE  `tw_user_coin` DROP COLUMN ' . $v['name'] . 'b');
				}*/

				if (M('Coin')->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}

				break;

			default:

			$this->error('参数非法');
		}

		if (M('Coin')->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}


	public function coinImage()
	{
		$upload = new \Think\Upload();
		$upload->maxSize = 3145728;
		$upload->exts = array('jpg', 'gif', 'png', 'jpeg');
		$upload->rootPath = './Upload/coin/';
		$upload->autoSub = false;
		
		$info = $upload->upload();
		foreach ($info as $k => $v) {
			$path = $v['savepath'] . $v['savename'];
			echo $path;
			exit();
		}
	}

    //系统参数设置
	public function qita(){
		$this->data = M('Config')->where(array('id' => 1))->find();
		$this->display();
	}

    //系统参数编辑
	public function qitaEdit(){
		$data = I('post.');
		if (M('Config')->where(array('id' => 1))->save($data)) {
			$this->success('修改成功！');
		} else {
			$this->error('修改失败');
		}
	}
	

	
	// 前端导航配置
	public function daohang($name = NULL, $field = NULL, $status = NULL, $lang = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
		if ($lang) {
			$where['lang'] = $lang;
		}
		
		$count = M('Daohang')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Daohang')->where($where)->order('sort asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function daohangEdit($id = NULL)
	{
	    //dump($_POST);
		if (empty($_POST)) {
			if ($id) {
				$this->data = M('Daohang')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			if ($_POST['id']) {
				$rs = M('Daohang')->save($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = M('Daohang')->add($_POST);
			}

			if ($rs) {
                $closeUrl = S('closeUrl');
			    if($_POST['get_login']) {
                    $closeUrl[] = $_POST['url'];
                } else {
                    if($key = array_search($_POST['url'], $closeUrl)) {
                        unset($closeUrl[$key]);
                    }
                }
                $closeUrl = array_unique($closeUrl);
                sort($closeUrl);
                S('closeUrl', $closeUrl);

				$this->success('编辑成功！',U('Config/daohang'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function daohangStatus($id = NULL, $type = NULL, $mobile = 'Daohang')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'del':
			$data = array('status' => -1);
			break;

		case 'delete':
			if (M($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($mobile)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	// 页脚导航配置
	public function dhfooter($name = NULL, $field = NULL, $status = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}
		
		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('footer')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('footer')->where($where)->order('sort asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
	
	public function dhfooterEdit($id = NULL)
	{
		if (empty($_POST)) {
			if ($id) {
				$this->data = M('footer')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if ($_POST['id']) {
				$rs = M('footer')->save($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = M('footer')->add($_POST);
			}

			if ($rs) {
                $closeUrl = S('closeUrl');
                if($_POST['get_login']) {
                    $closeUrl[] = $_POST['url'];
                } else {
                    if($key = array_search($_POST['url'], $closeUrl)) {
                        unset($closeUrl[$key]);
                    }
                }
                $closeUrl = array_unique($closeUrl);
                sort($closeUrl);
                S('closeUrl', $closeUrl);
				$this->success('编辑成功！',U('Config/dhfooter'));
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function dhfooterStatus($id = NULL, $type = NULL, $mobile = 'footer')
	{
		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'del':
			$data = array('status' => -1);
			break;

		case 'delete':
			if (M($mobile)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}

			break;

		default:
			$this->error('操作失败！');
		}

		if (M($mobile)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	// 后端导航配置
	public function dhadmin($name = NULL, $field = NULL, $status = NULL, $hide = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
		if ($hide) {
			$where['hide'] = $hide;
		}
		
		$where_1 = $where;
		$where_1['pid'] = 0;
		$where_2 = $where;
		
		$list = M('menu')->where($where_1)->order('sort asc')->select();
		foreach ($list as $k => $v) {
			$where_2['pid'] = $v['id'];
			$list[$k]['voo'] = M('menu')->where($where_2)->order('sort asc')->select();
		}
		
		$this->assign('list', $list);
		$this->display();
	}
	
	public function dhadminEdit($id = NULL)
	{
		if (empty($_POST)) {
			$liste = '';
			
			if ($id) {
				$this->data = M('menu')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
			
			$liste = M('menu')->where('pid = 0')->order('sort asc')->select();
			$this->assign('liste', $liste);
			$this->display();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}
			
			if (empty($_POST['title'])) {
				$this->error('标题错误');
			}

			if ($_POST['id']) {
				$rs = M('menu')->save($_POST);
			} else {
				$_POST['addtime'] = time();
				$rs = M('menu')->add($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！',U('Config/dhadmin'));
			} else {
				$this->error('编辑失败！');
			}
		}	
	}

	public function dhadminStatus($id = NULL, $type = NULL, $mobile = 'menu')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);
		switch (strtolower($type)) {
			case 'forbid':
				$data = array('hide' => 1);
				break;

			case 'resume':
				$data = array('hide' => 0);
				break;

			case 'repeal':
				$data = array('hide' => 2);
				break;
				
			case 'delete':
				$data = array('status' => -1);
				break;

			case 'del':
				if (M($mobile)->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}
				break;

			default:
			$this->error('操作失败！');
		}

		if (M($mobile)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	////市场配置OK
	public function ctmarket(){
	    $count = M('ctmarket')->count();
		$Page = new \Think\Page($count, 100);
		$show = $Page->show();
		$list = M('ctmarket')->order('sort asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
	    $this->display();
	}

    /////OK
	public function marketo($field = NULL, $name = NULL)
	{
		$where = array();

		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else {
				$where[$field] = $name;
			}
		}
		
		$count = M('Market')->where($where)->count();
		$Page = new \Think\Page($count, 100);
		$show = $Page->show();
		$list = M('Market')->where($where)->order('sort asc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
	
	
	///链上币种市场配置修改
	public function ctmarketEdit($id = NULL){
        if(empty($_POST)){
            $id = $_GET['id'];
            $this->data = M('ctmarket')->where(array('id' => $id))->find();
	        $this->display();
        }else{
            $coinname = $_POST['coinname'];
            $status = $_POST['status'];
            $state = $_POST['state'];
            $sort = $_POST['sort'];
            $id = $_POST['id'];
            $data['coinname'] = strtolower($coinname);
            $data['name'] = strtolower($coinname)."_usdt";
            $data['symbol'] = strtolower($coinname)."usdt";
            $data['title'] = strtoupper($coinname)."/USDT";
            $data['status'] = $status;
            $data['state'] = $state;
            $data['sort'] = $sort;
            $data['addtime'] = date("Y-m-d H:i:s",time());
            //编辑
            if($id > 0){
                $re = M("ctmarket")->where(array('id'=>$id))->save($data);
                if($re){
                    $this->success('操作成功！',U('Config/ctmarket'));
                }else{
                    $this->error('操作失败！');
                }
                
            }else{//新增
                $re =M("ctmarket")->add($data);
                if($re){
                    $this->success('操作成功！',U('Config/ctmarket'));
                }else{
                    $this->error('操作失败！');
                }
            }
            
        }
        
	}
	
	// 平台币市场配置修改
	public function marketoEdit($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}

		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = M('Market')->where(array('id' => $id))->find();
			}
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			$this->display();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}
			
			if(!$_POST['hou_price']){
				$_POST['hou_price'] = '0.00000000';
			}

			if ($_POST['id']) {
				$rs = M('Market')->save($_POST);
			} else {
				$buyname = $_POST['buyname'];
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (M('Market')->where(array('name' => $_POST['name']))->find()) {
					$this->error('市场存在！');
				}
				
				$jiaoyiqu = strtolower($getCoreConfig['indexcat'][$_POST['jiaoyiqu']]);
				if ($buyname != $jiaoyiqu) {
					$this->error('所属交易区和买方币种不一致！'.$buyname);
				}
				$rs = M('Market')->add($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',U('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}
	
	// 市场配置2修改
	public function marketoEdit2($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = M('Market')->where(array('id' => $id))->find();
			}
			
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			$this->display();
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}

			if ($_POST['id']) {
				$rs = M('Market')->save($_POST);
			} else {
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (M('Market')->where(array('name' => $_POST['name']))->find()) {
					$this->error('市场存在！');
				}
				$rs = M('Market')->add($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',U('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}
	
	// 市场配置3修改
	public function marketoEdit3($id = NULL)
	{
		$getCoreConfig = getCoreConfig();
		if(!$getCoreConfig){
			$this->error('核心配置有误');
		}
		if (empty($_POST)) {
			if (empty($id)) {
				$this->data = array();
			} else {
				$this->data = M('Market')->where(array('id' => $id))->find();
			}
			
			$time_arr = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');
			$time_minute = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','32','33','34','35','36','37','38','39','40','41','42','43','44','45','46','47','48','49','50','51','52','53','54','55','56','57','58','59');
			$this->assign('time_arr', $time_arr);
			$this->assign('time_minute', $time_minute);
			$this->assign('getCoreConfig',$getCoreConfig['indexcat']);
			
			$round = number_format("0",$this->data['round']-1).'1';
			$this->assign('round', $round);
			
			$this->display();
			
		} else {
			if (APP_DEMO) {
				$this->error('测试站暂时不能修改！');
			}

			$round = array(0, 1, 2, 3, 4, 5, 6);
			if (!in_array($_POST['round'], $round)) {
				$this->error('小数位数格式错误！');
			}

			if ($_POST['id']) {
				$rs = M('Market')->save($_POST);
			} else {
				$_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
				unset($_POST['buyname']);
				unset($_POST['sellname']);

				if (M('Market')->where(array('name' => $_POST['name']))->find()) {
					$this->error('市场存在！');
				}

				$rs = M('Market')->add($_POST);
			}

			if ($rs) {
				$this->success('操作成功！',U('Config/marketo'));
			} else {
				$this->error('操作失败！');
			}
		}
	}
	
	public function marketoStatus($id = NULL, $type = NULL, $mobile = 'Market')
	{
		if (APP_DEMO) {
			$this->error('测试站暂时不能修改！');
		}

		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
			case 'forbid':
				$data = array('status' => 0);
				break;

			case 'resume':
				$data = array('status' => 1);
				break;

			case 'repeal':
				$data = array('status' => 2, 'endtime' => time());
				break;

			case 'delete':
				$data = array('status' => -1);
				break;

			case 'del':
				if (M($mobile)->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}

				break;

			default:
				$this->error('操作失败！');
		}

		if (M($mobile)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
	////修改市场配置状态
	public function ctmarketoStatus($id = NULL, $type = NULL, $mobile = 'ctmarket'){
		if (empty($id)) {
			$this->error('参数错误！');
		}
		if (empty($type)) {
			$this->error('参数错误1！');
		}
		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}
		$where['id'] = array('in', $id);
		switch (strtolower($type)) {
			case 'forbid':
				$data = array('status' => 2);
				break;
			case 'resume':
				$data = array('status' => 1);
				break;
			case 'del':
				if (M($mobile)->where($where)->delete()) {
					$this->success('操作成功！');
				} else {
					$this->error('操作失败！');
				}

				break;
			default:
				$this->error('操作失败！');
		}
		if (M($mobile)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
	
      public function addusdt()
    {
        
        $info=M("coin")->where(["id"=>$_POST['id']])->find();
        $api    = $this->url.'/api/tool/generateAddressWithMnemonic';
        $resp   = $this->get_curl($api);
        $data   = json_decode($resp, true);
   
    $accountinfo=array(
        "channel_id"=>$_POST['id'],
        "czaddress"=>$data['data']['address'],//钱包地址
        "usdtkey"=>$data['data']['privateKey'],//私钥
        );
      //  var_dump($info['czaddress']);exit;
      if($info['blance'] > 10){
          	$this->error("该账号有余额，不可覆盖生成！");
      }
        if($info['czaddress']){
            	$this->error("TRC20收款地址已存在，请删除后再生成！");
        }else{
             $addaccount=M("coin")->where(["id"=>$_POST['id']])->save($accountinfo);//创建收款账号
            if($addaccount){
        	$this->success("生成成功！");
         }else{
             	$this->error("生成失败！");
         }
        }
       
    }
	 public function getblance()
    {
  
        $info=M("coin")->where(["id"=>$_POST['id']])->find();
           $api    = $this->url.'/api/trc20/getAddressBalance?address='.$info['czaddress'];
        $resp   = $this->get_curl($api);
        $data   = json_decode($resp, true);
        
        $api1=$this->url."/api/trx/getBalance?address=".$info['czaddress'];
        $resp1   = $this->get_curl($api1);
        $data1   = json_decode($resp1, true);
        $upbalance=M("coin")->where(["id"=>$_POST['id']])->save(["blance"=>$data['data'],"trx_blance"=>$data1['data']]);//查询通道等于6且状态为开启的子账号
      
        if($upbalance){
            	$this->success('余额更新成功！USDT余额：'.$data['data']." TRX余额：".$data1['data']);
            
        }else{
            	$this->error('余额更新失败！USDT余额：'.$data['data']." TRX余额：".$data1['data']);
        }
      }
       public function getblanceall()
    {
  
        $infolist=M("coin")->where(['name' => "usdt", 'czline' => "TRC20"] )->select();
        
          foreach ($infolist as $item){
        
           $api    = $this->url.'/api/trc20/getAddressBalance?address='.$item['czaddress'];
        $resp   = $this->get_curl($api);
        $data   = json_decode($resp, true);
        
        $api1=$this->url."/api/trx/getBalance?address=".$item['czaddress'];
        $resp1   = $this->get_curl($api1);
        $data1   = json_decode($resp1, true);
        $upbalance=M("coin")->where(["id"=>$item['id']])->save(["blance"=>$data['data'],"trx_blance"=>$data1['data']]);//查询通道等于6且状态为开启的子账号
      
        if($upbalance){
            echo ('余额更新成功！USDT余额：'.$data['data']." TRX余额：".$data1['data']);
            
        }else{
             echo  ('余额更新失败！USDT余额：'.$data['data']." TRX余额：".$data1['data']);
        }
        
          }
      }
      //trx转账
     public function gettrx(){
         $trxlist= M('coin')->where(['name' => "usdt", 'czline' => "TRC20"] )->select();
         $config= M('config')->where(['id' => 1])->find();
         
        $api2=$this->url."/api/trc20/getAddress?privateKey=".$config['shouxufeiid'];
        $res2= $this->get_curl($api2);
        $address   = json_decode($res2, true);
        $address=$address['data']['address'];
            $api1=$this->url."/api/trx/getBalance?address=".$address;
        $resp1   = $this->get_curl($api1);
        $data1   = json_decode($resp1, true);
     
        if($data1['data'] < "10"){
            	$this->error('TRX矿工费账户余额不足，仅剩'.$data1['data']."TRX,请充值后再执行");
        }
        
             foreach ($trxlist as $item){
                 if($item['trx_blance'] < 10){
                     $url=$this->url."/api/trx/send?to=".$item['czaddress']."&key=".$config['shouxufeiid']."&account=10";
                       $res   = $this->get_curl($url);
                       $data   = json_decode($res, true);
                         if($data['data']['result'] =="true"){
                            
                             echo $item['czaddress'] ."TRX转账成功";
                         }else{
                             echo $data['msg'];
                         }
                 }else{
                   echo $item['czaddress'] ."的矿工费余额足够支付下一次归集任务";
                 }
             }
             
        
        
    }
    //usdt归集
     public function getguiji()
    {
          $config= M('config')->where(['id' => 1])->find();
      // var_dump($config,$config['guiji_trc20'],$config['guiji_trx'],$config['guiji_trx_siyao']);exit();
      if(empty($config['guijiid']) || empty($config['shouxufeiid']) ){ 	$this->error("缺少参数");}
   
        
             $trxlist= M('coin')->where(['name' => "usdt", 'czline' => "TRC20"] )->select();
            
             foreach ($trxlist as $item){
                   if($item['trx_blance'] < "10" || $item['blance'] < $config['startmoney'] || empty($item['usdtkey'])){
                         echo($item['czaddress'].'该账户USDT余额小于后台设置的最低归集金额:'.$config['startmoney'].'或TRX矿工费账户余额不足,或私钥为空，请检查后再执行归集操作'." \r\n");
                     }else{
                         
                         
                         $api1111=$this->url."/api/trc20/transfer?key=".$item['usdtkey']."&toaddress=".$config['guijiid']."&amount=".$item['blance'];
                          $resp   = $this->get_curl($api1111);
                          $putdata   = json_decode($resp, true);
                          
                           file_put_contents(dirname( __FILE__ ).'/log/~USDTguiji-log-'. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log',date('Y-m-d H:i:s').'归集地址：'.$item['czaddress'].'归集金额：'.$item['blance']."收款地址：".$config['guijiid']."接口返回信息：".$resp."最终状态：".$putdata['data']['contractRet']."错误信息：".$putdata['msg']."\r\n", FILE_APPEND);
                          
                          if($putdata['data']['contractRet'] =="SUCCESS"){
                               echo($item['czaddress'].'USDT归集成功，金额：'.$item['blance'].'已归集到'.$config['guijiid']);
                               
                          }
                     }
                     
             }
             
          
     
       
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
}
?>