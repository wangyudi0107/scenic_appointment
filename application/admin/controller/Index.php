<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;

class Index extends Controller{
	
	public function verify($a,$b){
		$Common = controller('Common');//实例化common控制器
		$Common -> lin_in();//验证登录
		$Common -> indexes($a,$b);//传入索引
	}
	
	public function index()
	{
		//导入索引，验证登录
		$this -> verify(0,0);
		$result = db('scenic_introduction') -> select();
		$this -> assign('intro',$result);
		return view('index');
	}
	
	public function scenic_add(){
		//导入索引，验证登录
		$this -> verify(0,0);
		if(input('scenic_id')){//有id传入则跳转信息修改页面
			//获取景点信息
			$result = db('scenic_introduction') -> where('id',input('scenic_id')) -> find();
			//赋值修改页面跳转
			$this -> assign('intro',$result);
		}
		return view('scenic_modify');
		
	}
	
	//图片上传接口
	public function intro_upload(){
		$file = request() -> file('file');  //接收上传文件，默认名为file
		$info = $file -> move('./static/img/intro_pic/');  //将文件移动到指定目录下
		$pic_url ='/static/img/intro_pic'.DS.$info->getSaveName();  //赋值路径名称
		//判断上传成功与否，返回数据给layui的js
		if($pic_url){
			echo json_encode(array('code'=>1,"msg"=> '上传成功',"data"=> ["src"=>$pic_url] ));exit;
		}else{
			echo json_encode(array('code'=>2,"msg"=> '上传失败'));exit;
		}
	}
	
	//景点信息更改功能
	// address：景点名称
	// src：景点图片
	// intro：景点介绍
	// duration：演出时长
	public function scenic_insert(){
		$data['address']=input('address');
		$data['src']=input('src');
		$data['intro']=input('intro');
		$data['duration']=input('duration');
		$data['ticket_num']=input('ticket_num');
		$data['day']=input('day');
		// id传入判定为修改，否则为新增
		if(input('id')){
			$result = db('scenic_introduction') -> where('id',input('id')) -> update($data);
			if($result){
				echo "<script>alert('更改成功');location.href='".url('admin/index/index')."';</script>";
			}else{
				echo "<script>alert('更改失败');history.go(-1)</script>";
			}
		}else{
			$result = db('scenic_introduction') -> insert($data);
			if($result){
				echo "<script>alert('添加成功');location.href='".url('admin/index/index')."';</script>";
			}else{
				echo "<script>alert('添加失败');history.go(-1)</script>";
			}
		}
	}
	
	//景点信息删除
	public function scenic_del(){
		db('scenic_introduction') -> where('id',input('id')) -> delete();
		echo json_encode(array('status'=>1,"msg"=> '删除成功'));exit;
	}
	
	public function show_info(){
		//导入索引，验证登录
		$this -> verify(0,1);
		
		$scenic=db('scenic_introduction') -> select();
		$this -> assign('scenic',$scenic);
		
		//模糊查询
		$where=[];
		if(input('scenic_id')){
			$where['scenic_id'] = input('scenic_id');
		}
		if(input('show_time')){
			$show_time = strtotime(input('show_time').' 00:00:00');
			$end_time = strtotime(input('show_time').' 23:59:59');
			$where['show_time'] = array('>',$show_time);
			$where['end_time'] = array('<',$end_time);
		}
		if(input('status')){
			$where['status'] = input('status');
		}
		 
		db('show_intro') -> where('show_time','<',time()) -> where('status',1) -> update(['status'=>2]);
		
		$result = db('show_intro') -> where($where) -> order('show_time desc') -> paginate(10,false,['query'=>request()->param()]);
		$items = $result->items();
		foreach($items as $k=>$v){
			$items[$k]['scenic'] = db('scenic_introduction')->where('id',$v['scenic_id'])->value('address');
		}
		
		$page = $result -> render();  //获取分页
		
		$this -> assign('page',$page);
		$this->assign('show',$items);
		
		return view();
	}
	
	//演出信息删除
	public function show_del(){
		db('show_intro') -> where('id',input('id')) -> delete();
		echo json_encode(array('status'=>1,"msg"=> '删除成功'));exit;
	}
	
	
	public function show_info_change()
	{
		//导入索引，验证登录
		$this -> verify(0,1);
		
		$scenic=db('scenic_introduction') -> select();
		$this -> assign('scenic',$scenic);
		
		if(input('id')){//有id传入则跳转信息修改页面			
			//获取景点信息			
			$result = db('show_intro') -> where('id',input('id')) -> find();
			$result['scenic'] = db('scenic_introduction')->where('id',$result['scenic_id'])->value('address');
			
			$result['show_time'] = date('Y-m-d H:i' , $result['show_time']);
			$result['show_time'] = explode(' ',$result['show_time']);
			$result['date'] = $result['show_time'][0];
			$result['show_time'] = $result['show_time'][1];
			
			$result['end_time'] = date('Y-m-d H:i' , $result['end_time']);
			$result['end_time'] = explode(' ',$result['end_time']);
			$result['end_time'] = $result['end_time'][1];
			
			//赋值修改页面跳转			
			$this -> assign('intro',$result);
		}
		return view('show_change');
	}
	
	public function show_insert(){
		
		$data['show_time'] = strtotime(input('date').' '.input('show_time'));
		$data['end_time'] = strtotime(input('date').' '.input('end_time'));
		
		if($data['end_time']<=$data['show_time'])
		{
			$this -> error('开始时间不应小于结束时间');
		}else{
			$data['ticket_num'] = input('ticket_num');
			$data['scenic_id'] = input('scenic');
			$data['price'] = input('price');
			$data['status'] = input('status');
			if(input('id')){
				$result = db('show_intro') -> where('id',input('id')) -> update($data);
				if($result){
					echo "<script>alert('更改成功');location.href='".url('admin/index/show_info')."';</script>";
				}else{
					echo "<script>alert('更改失败');history.go(-1)</script>";
				}
			}else{
				$result =  db('show_intro') -> insert($data);
				if($result){
					echo "<script>alert('添加成功');location.href='".url('admin/index/show_info')."';</script>";
				}else{
					echo "<script>alert('添加失败');history.go(-1)</script>";
				}
			}
		}
	}
	
}