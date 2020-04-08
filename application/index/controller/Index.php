<?php
namespace app\index\controller;
header("Content-Type:text/html; charset=utf-8");//防止输出乱码
use think\Controller;
header("Access-Control-Allow-Origin: *"); //解决跨域

class Index extends Controller
{
	//预购主页
    public function index()
    {
		$intro = db('scenic_introduction') -> select();
		$this -> assign('intro',$intro);
        return view();
    }
	
	//公演信息页包含( 场次时间，分日查询，)
	public function show_item()
	{
		//获取景点信息
		$scenic_intro = db('scenic_introduction') -> where('id',input('scenicID')) -> find();
		$this -> assign('in',$scenic_intro);
		
		//判断当前未上映场次是否易已过上映时间，变更为以上映
		db('show_intro') -> where('show_time','<',time()) -> where('status',1) -> update(['status'=>2]);
		// 查询可买日期
		// $set = db('setting') -> find();
		// 定义演出场次信息数组
		$show_intro = [];
		// 循环查询可够日期内的演出信息
		for($i=0;$i<$scenic_intro['day'];$i++){
			$a=$i+1;//查询时间区间
			//获取日期
			$show_intro[$i]['data'] = date('m-d', strtotime('+'.$i.' day'));
			// 获取在日期内当前景点的未公演的演出
			$show_intro[$i]['info'] = db('show_intro') 
										-> where('scenic_id',input('scenicID')) 
										// -> where('status',1) 
										-> order('show_time')
										-> whereTime('show_time', 'between', [date('Y-m-d', strtotime('+'.$i.' day')), date('Y-m-d', strtotime('+'.$a.' day'))]) 
										-> select();
		}
		$this->assign('show',$show_intro);
		return view();
	}
	
	
	//购票页面赋值
	public function buyit()
	{
		// 获取景点信息
		$scenic_intro = db('scenic_introduction') -> where('id',input('id')) -> find();
		$this -> assign('in',$scenic_intro);
		// 获取id场次的演出信息
		$show_intro = db('show_intro') -> where('id',input('showid')) -> find();
		$this -> assign('show_intro',$show_intro);
		
		return view();
	}
	
	// 支付功能
	// appoint_cardID：预定身份证号 	appoint_phone：预定手机号
	// cardID：购票人身份证  			name：购票人姓名  			tiwen：购票人提问
	// num：购买数量  				price：购买金额
	// scenic_id：景点id  			show_id：演出信息id
	public function pay()
	{
		$data['appoint_cardID'] = input('appoint_cardID');
		$data['appoint_phone'] = input('appoint_phone');
		$data['cardID'] = input('cardID');
		$data['name'] = input('name');
		$data['tiwen'] = input('tiwen');
		$data['num'] = input('num');
		$data['price'] = input('price');
		$data['scenic_id'] = input('scenic_id');
		$data['show_id'] = input('show_id');
		$data['order'] = 'AD'.input('scenic_id').'SW'.input('show_id').'T'.time().'R'.rand(10000, 99999);
		$data['date'] = time();
		//获取开演时间
		$show_time = db('show_intro') -> where('id',$data['show_id']) -> value('show_time');
		$ticket_num = db('show_intro') -> where('id',$data['show_id']) -> value('ticket_num');
		// 如果当前时间大于开演时间即失败，变更演出信息
		if(time()>$show_time){
			$show_time = db('show_intro') -> where('id',$data['show_id']) -> update(['status'=>2]);
			echo json_encode(array('status'=>(int)2,'msg'=>'已过购票时间'));exit;
		}elseif($ticket_num<$data['num']){
			echo json_encode(array('status'=>(int)3,'msg'=>'余票不足'));exit;
		}else{
			//购票信息插入获取自增id
			$result = db('order') -> insertGetId($data);
			//对于门票信息自减
			db('show_intro') -> where('id',$data['show_id']) -> setDec('ticket_num',$data['num']);
			// 拼接信息连接生成二维码
			$info = request()->domain().url('index/index/verification')."?order_id=".$result;
			$info_qrcode = $this -> code($info);
			// 将二维码插入订单
			db('order') -> where('id',$result) -> update(['qrcode'=>$info_qrcode]);
		
			if($result){
				echo json_encode(array('status'=>(int)1,'msg'=>'购票成功','order_id'=>$result));exit;
			}else{
				echo json_encode(array('status'=>(int)4,'msg'=>'辣鸡'));exit;
			}
		}
	}
	
	// 生成二维码
	// 返回二维码链接
	public function code($info)
	{
		vendor('phpqrcode');				//引入类库
		$value = $info;						//二维码内容
		$errorCorrectionLevel = 'L';  		//容错级别
		$matrixPointSize = 5;      			//生成图片大小
		//生成二维码图片
		// 判断是否有这个文件夹  没有的话就创建一个
		if(!is_dir("qrcode")){
			// 创建文件加
			mkdir("qrcode");
		}
		//设置二维码文件名
		$filename = 'qrcode/'.time().rand(10000,9999999).'.png';
		//生成二维码
		\QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
		//转换成base64数据
		$image_data = chunk_split(base64_encode(fread(fopen($filename, 'r'), filesize($filename))));
		//获取当前域名 
		// $request = Request::instance();
		$domain = request()->domain();
		$url = $domain.'/'.$filename;
		
		return $url;
	}  
	
	// 查询订单验证接口
	public function order_v(){
		$result = db('order') -> where('appoint_cardID',input('cardID')) -> where('appoint_phone',input('phone')) -> select();
		if($result){
			echo json_encode(array('status'=>(int)1,'data'=>'验证成功'));exit;
		}else{
			echo json_encode(array('status'=>(int)2,'data'=>'验证失败'));exit;
		}
	}
	
	public function order_items(){
		// 获取当前用户的所有订单
		$all = db('order') -> where('appoint_cardID',input('cardID')) -> where('appoint_phone',input('phone')) -> order('date desc') -> select();
		if($all){
			foreach($all as $k=>$v){
				$all[$k]['scenic'] = db('scenic_introduction')->where('id',$v['scenic_id'])->value('address');
				$all[$k]['show_time'] = db('show_intro')->where('id',$v['show_id'])->value('show_time');
				$all[$k]['end_time'] = db('show_intro')->where('id',$v['show_id'])->value('end_time');
			}
		}
		$all_num = count($all);
		$unfinished = db('order') -> where('appoint_cardID',input('cardID')) -> where('appoint_phone',input('phone')) -> where('status',1) -> order('date desc') -> count();
	
		$this->assign('all',$all);
		$this->assign('all_num',$all_num);
		$this->assign('unfinished',$unfinished);
		return view();
		
	}
	
	// 查看订单信息
	public function order()
	{
		// 获取当前用户的所有订单
		$result = db('order') -> where('id',input('id')) -> select();
		// 遍历订单数组
		if($result){
			foreach($result as $k=>$v){
				$result[$k]['buyer']='';
				$result[$k]['scenic'] = db('scenic_introduction')->where('id',$v['scenic_id'])->value('address');
				$result[$k]['show_time'] = db('show_intro')->where('id',$v['show_id'])->value('show_time');
				$result[$k]['end_time'] = db('show_intro')->where('id',$v['show_id'])->value('end_time');
				// 将身份证、姓名、体温字符串拼打散再接位数组
				// 由于字符串最有会有一个空数据，用array_pop()删除最后一个
				$v['name'] = explode(',',$v['name']); 		array_pop($v['name']);
				$v['cardID'] = explode(',',$v['cardID']);  	array_pop($v['cardID']);
				$v['tiwen'] = explode(',',$v['tiwen']);    	array_pop($v['tiwen']);
				// 遍历姓名数组将身份信息拼接
				foreach($v['name'] as $key=>$val){
					if($v['tiwen'][$key]==1){
						$tiwen = '<span style="color:'.'skyblue'.'">正常';
					}else{
						$tiwen = '<span style="color:'.'red'.'">异常';
					}
					// 将身份信息拼接为字符串输出
					$result[$k]['buyer'] .= '<tr><td>'.$val.'</td><td>'.$v['cardID'][$key].'</td><td>'.$tiwen."</r>";
				}
				$result[$k]['buyer'].="</tr>";
			}
			$this -> assign('order_info',$result);
			// 判断是否通过支付完成进入订单详情页
			if(input('status')){
				$this->assign('status',input('status'));
			}
			return view();
		}else{
			echo "<script>alert('验证错误，请重试');history.go(-1);</script>";
		}
	}
	
}
