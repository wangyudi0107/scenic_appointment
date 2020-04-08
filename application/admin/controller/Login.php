<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;
header('Access-Control-Allow-Origin: *');

class Login extends Controller
{
	
	public function index()
	{
		return	view('index');
	}
	
	//账号密码登录
	public function login(){
		$name = null;//给登录判断条件附空值，如果没有值，即返回错误
		//判断是否通过账号密码登录
		if(!empty(input('account'))){	
			//获取账号
			$data['account'] = input('account');
			//获取密码
			$data['password'] = input('pwd');
			//验证是否存在
			$name = db('account') -> where($data) -> find();
		}
		
		if(!empty($name)){
			// 启动session
			session('[start]'); 
			//设置session
			session('name',$name['name']); 
			echo "<script>alert('您好!登录成功!');location.href='".url('admin/index/index')."';</script>";exit;
		}else{
			echo "<script>alert('登录失败！账号或密码错误！');history.go(-1);</script>";exit;
		}
	}
	
	//登出
	public function lin_out()
	{
		// 清除session
		session(null);
		echo json_encode(array('status'=>(int)1,'msg'=>'退出成功'));exit;
		
	}
	
	
}