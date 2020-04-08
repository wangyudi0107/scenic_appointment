<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;
header('Access-Control-Allow-Origin: *');

class Setting extends Controller{
	
	public function verify($a,$b)
	{
		$Common = controller('Common');//实例化common控制器
		$Common -> lin_in();//验证登录
		$Common -> indexes($a,$b);//传入索引
	}
	
	public function index()
	{
		//导入索引，验证登录
		$this -> verify(2,0);
		$setting = db('setting') -> find();
		$this -> assign('set',$setting);
		
		return view();
	}
	
	public function set()
	{
		$result = db('setting') -> where('id',1) -> update(input());
		if($result){
			echo json_encode(array('status'=>1,"msg"=> '设置成功'));exit;
		}else{
			echo json_encode(array('status'=>2,"msg"=> '设置失败'));exit;
		}
	}
	
}