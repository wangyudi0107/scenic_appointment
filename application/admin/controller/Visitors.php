<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;
header('Access-Control-Allow-Origin: *');

class Visitors extends Controller{

	public function verify($a,$b)
	{
		$Common = controller('Common');//实例化common控制器
		$Common -> lin_in();//验证登录
		$Common -> indexes($a,$b);//传入索引
	}
	
	public function index(){
		//导入索引，验证登录
		$this -> verify(3,0);
		return view();
	}
	
	//传入参数date(today、week、month、year)
	public function visitors()
	{
		//获取景点信息
		$scenic = db('scenic_introduction') -> field('address') -> select();
		//按照日期条件查询
		$visitors = db('order') -> where('status',2) -> whereTime('date',input('date')) ->field('num,scenic_id') -> select();
	
		//将景点信息以及客流量赋值给data
		$data = [];
		foreach($scenic as $k => $v){
			$data[$k]['name'] = $v['address'];
			$data[$k]['value'] = 0;
			foreach($visitors as $key=>$val){
				if($val['scenic_id']==$k+1){
					$data[$k]['value'] = $data[$k]['value']+$val['num'];
				}
			}
		}
		//返回景点信息和客流量信息
		echo json_encode(array($scenic,$data));exit;
	}
	
}