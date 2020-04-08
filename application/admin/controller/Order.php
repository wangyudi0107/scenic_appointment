<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;

class Order extends Controller{
	
	public function verify($a,$b){
		$Common = controller('Common');//实例化common控制器
		$Common -> lin_in();//验证登录
		$Common -> indexes($a,$b);//传入索引
	}
	
	public function index()
	{
		//导入索引，验证登录
		$this -> verify(1,0);
		//获取景区信息赋值
		$scenic=db('scenic_introduction') -> select();
		$this -> assign('scenic',$scenic);
		//判断搜索条件
		$where=[];
		if(input('scenic_id')){
			$where['scenic_id'] = input('scenic_id');
		}
		if(input('name')){
			$where['name'] = array('like','%'.input('name').'%');
		}
		if(input('status')){
			$where['status'] = input('status');
		}
		//查询符合条件的订单
		// 按照id降序
		// 分页
		$result = db('order') -> where($where) -> order('id desc') -> paginate(10,false,['query'=>request()->param()]);
		$page = $result -> render();
		
		$items = $result->items();
		// 将姓名、体温、身份证拆为数组
		foreach($items as $k=>$v){
			$items[$k]['name'] = explode(',',$items[$k]['name']);
			array_pop($items[$k]['name']);
			$items[$k]['cardID'] = explode(',',$items[$k]['cardID']);
			array_pop($items[$k]['cardID']);
			$items[$k]['tiwen'] = explode(',',$items[$k]['tiwen']);
			array_pop($items[$k]['tiwen']);
		}
		
		
		$this->assign('order',$items);
		$this->assign('page',$page);
		return view();
	}
	
	public function details(){
		if(input('status')){
			dump(input('status'));
		}
		// 查询订单
		$result = db('order') -> where('id',input('id')) -> find();
		// 将姓名、体温、身份证拆为数组
		$result['name'] = explode(',',$result['name']);
		array_pop($result['name']);
		$result['cardID'] = explode(',',$result['cardID']);
		array_pop($result['cardID']);
		$result['tiwen'] = explode(',',$result['tiwen']);
		array_pop($result['tiwen']);

		$this -> assign('info',$result);
		// 查询景点信息
		$scenic = db('scenic_introduction') -> where('id',$result['scenic_id']) -> find();
		$this -> assign('scenic',$scenic);
		// 查询演出信息
		$show = db('show_intro') -> where('id',$result['show_id']) -> find();
		$this -> assign('show',$show);
		return view();
	}
	
	public function order_status(){
		$result = db('order') -> where('id',input('id')) -> update(['status'=>input('status')]);
		if($result){
			echo json_encode(array('status'=>1,"msg"=> '变更成功'));exit;
		}else{
			echo json_encode(array('status'=>2,"msg"=> '变更失败'));exit;
		}
		
	}
	
}
?>