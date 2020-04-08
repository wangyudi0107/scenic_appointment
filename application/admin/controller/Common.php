<?php
namespace app\admin\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;

//公共函数
class Common extends Controller
{
	//验证是否登录session
	public function lin_in()
	{
		// 验证session
		if(session('name') == null)
		{
			echo "<script>alert('您好!请先登录!');location.href='".url('admin/login/index')."';</script>";exit;
		}
	}
	
	//页面索引赋值
	public function indexes($first,$second)
	{
		$index['first'] = $first;		//一级菜单
		$index['second'] = $second;		//二级菜单
		$this -> assign('index',$index);
	}

}