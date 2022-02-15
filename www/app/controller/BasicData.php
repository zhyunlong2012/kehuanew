<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class BasicData
{
    // protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    function index(){
        $user_model = new \app\model\User();
        $user_num = $user_model->count();
        $car_model = new \app\model\Car();
        $car_num = $car_model->count();
        $positon_model = new \app\model\Position();
        $positon_num = $positon_model->count();
        $factory_model = new \app\model\Factory();
        $factory_num = $factory_model->count();
        $customer_model = new \app\model\Customer();
        $customer_num = $customer_model->count();
        $product_model = new \app\model\Product();
        $product_num = $product_model->count();
        $basic_data = [
            'user_num' => $user_num,
            'position_num' => $positon_num,
            'factory_num' => $factory_num,
            'customer_num' => $customer_num,
            'product_num' => $product_num,
            'car_num' => $car_num,
        ];
        $data = [
            'basicData' => $basic_data
        ];
        return json($data);
    }

    /**
     * 库区监控
     *
     * @return void
     */
    function position(){
        $position_id = input('position_id');
        if(empty($position_id)){
            $res['data']=[];
            $res['total'] =0;
            return json($res);
        }
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $position_area_model = new \app\model\PositionArea();
        $area = $position_area_model->where('position_id',$position_id)->page($current,$pageSize)->field('id,code,size,packamount')->select();
        $area_total =  $position_area_model->where('position_id',$position_id)->count();
        $area_storage_model = new \app\model\AreaStorage();
        $product_model = new \app\model\Product();
        foreach($area as $key=>$value){
            $area_sto = $area_storage_model->where('id',$value['id'])->column('amount','product_id');
            if(count($area_sto)>0){
                $tmp_pro = [];
                foreach($area_sto as $key1 => $value1){
                    $product = $product_model ->where('id',$key1)->find();
                    if($product){
                        $tmp['pro_code'] = $product['pro_code'];
                        $tmp['amount'] = $value1;
                        $tmp_pro[] = $tmp;
                    }
                }
                // dump($tmp_pro);
                $area[$key]['pros'] = $tmp_pro ;
            }else{
                $area[$key]['pros'] = [];
            }
            // dump($area[$key]);
        }

        $res['data']=$area;
        $res['total'] = $area_total;

        return json($res);

        // dump($area[0]);
    }
    /**
     * 打包监控信息
     *
     * @return void
     */
    public function pack(){
        $time = input('time');
        if(!$time){ $time = time();}
        $time  =$time-3600*10;
        $time_arr[]= $time;
        for($i=1;$i<=10;$i++){
            $time  =$time+3600;
            $time_arr[] = $time;
        }
        // dump($time);
        //当天开始时间
        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        $day = [$start_time,$end_time];
        $scan_model = new \app\model\Scan();
        $pack_amount = $scan_model->where('create_time','between',$day)->count();
        $pro_amount =  $scan_model->where('create_time','between',$day)->sum('amount');
        
        $pro_arr = [];//10小时产量
        for($i=0;$i<10;$i++){
            $tmp_pro_amount =  $scan_model->where('create_time','between',[$time_arr[$i],$time_arr[$i+1]])->sum('amount');
            $pro_arr[] = [
                'index' => $i+1,
                'value'=>$tmp_pro_amount
            ];
        }
        $data['pack_amount']=$pack_amount;
        $data['pro_amount'] = $pro_amount;
        $data['pro_arr'] = $pro_arr;
        return $this->JsonSuccess($data);
    }


}