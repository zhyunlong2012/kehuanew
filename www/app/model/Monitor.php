<?php
namespace app\model;
/**
 * 监控
 */
class Monitor{
    /**
     * 打包监控
     *
     * @param [type] $time
     * @return void
     */
    public function packmonitor($time){
        if(!$time){ $time = time();}
        $time  =$time-3600*10;
        $time_arr[]= $time;
        for($i=1;$i<=10;$i++){
            $time  =$time+3600;
            $time_arr[] = $time;
        }
        //当天开始时间
        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        $day = [$start_time,$end_time];
        $scan_model = new Scan();
        $pack_amount = $scan_model->where('create_time','between',$day)->count();
        $pro_amount =  $scan_model->where('create_time','between',$day)->sum('amount');
        $pro_weight =  $scan_model->where('create_time','between',$day)->sum('weight');
        $not_in_amount = $scan_model->where('create_time','between',$day)->where('status',1)->count();;
        $out_amount = $scan_model->where('status',2)->count();
        $not_car_amount = $scan_model->where('status',3)->count();
        // $pro_arr = [];//10小时产量
        // for($i=0;$i<10;$i++){
        //     $tmp_pro_amount =  $scan_model->where('create_time','between',[$time_arr[$i],$time_arr[$i+1]])->sum('amount');
        //     $pro_arr[] = [
        //         'index' => $i+1,
        //         'value'=>$tmp_pro_amount
        //     ];
        // }
            
        $user_model = new User();
        $ax_users = $user_model->where('username','like','ax%')->where('status',1)->column('id');
        $bx_users = $user_model->where('username','like','bx%')->where('status',1)->column('id');
        $dm_users = $user_model->where('username','like','dm%')->where('status',1)->column('id');
        $ax_in_weight = $scan_model->where('create_time','between',$day)->where('user_id','in',$ax_users)->sum('weight');
        $bx_in_weight = $scan_model->where('create_time','between',$day)->where('user_id','in',$bx_users)->sum('weight');
        $dm_in_weight = $scan_model->where('create_time','between',$day)->where('user_id','in',$dm_users)->sum('weight');

        $data['pack_amount']=$pack_amount;
        $data['pro_amount'] = $pro_amount;
        $data['pro_weight'] = round($pro_weight,2);
        // $data['pro_arr'] = $pro_arr;
        $data['pack_status'] = [
            ['title'=>'未入库','max'=>$pack_amount,'value'=>$not_in_amount],
            ['title'=>'未校验','max'=>$out_amount,'value'=>$not_car_amount],
        ];
        $data['in_cates'] = [
            ['type'=>'A线','value'=>round($ax_in_weight,2)],
            ['type'=>'B线','value'=>round($bx_in_weight,2)],
            ['type'=>'地面','value'=>round($dm_in_weight,2)],
        ];
        return $data;
    }

    /**
     * 随车单监控
     *
     * @return void
     */
    public function billmonitor(){
        //当天开始时间
        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        $day = [$start_time,$end_time];
        $bill_model = new Billlist();
        $wait_amount = $bill_model->where('create_time','between',$day)->where('status',1)->count();
        $suiche_amount =  $bill_model->where('create_time','between',$day)->where('status',2)->count();
        $chuchang_amount =  $bill_model->where('create_time','between',$day)->where('status',3)->count();
        $data['wait_amount']=$wait_amount;
        $data['suiche_amount'] = $suiche_amount;
        $data['chuchang_amount'] = $chuchang_amount;
        return $data;
    }

    /**
     * 当日出入库监控
     *
     * @return void
     */
    public function stomonitor(){
        //当天开始时间
        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        $day = [$start_time,$end_time];
        $sto_in_model = new StorageIn();
        $sto_out_model = new StorageOut();
        $pro_in_amount = $sto_in_model->where('create_time','between',$day)->sum('amount');
        $pro_in_weight = $sto_in_model->where('create_time','between',$day)->sum('weight');
        $pro_out_amount = $sto_out_model->where('create_time','between',$day)->sum('amount');
        $pro_out_weight = $sto_out_model->where('create_time','between',$day)->sum('weight');


        $data=[
            ['type' => '入库数量','amount'=>$pro_in_amount],
            ['type' => '入库重量','amount'=>round($pro_in_weight,2)],
            ['type' => '出库数量','amount'=>$pro_out_amount],
            ['type' => '出库重量','amount'=>round($pro_out_weight,2)],
        ];
        return $data;
    }

    public function carmonitor(){
        //当天开始时间
        $start_time=strtotime(date("Y-m-d",time()));
        //当天结束之间
        $end_time=$start_time+60*60*24;
        $day = [$start_time,$end_time];

        $car_model = new Car();
        $car_amount = $car_model ->where('status',1)->count();
        $bill_model = new Billlist();
        $wait_amount = $bill_model->where('status',1)->where('create_time','between',$day)->count();
        $go_amount = $bill_model->where('status',3)->where('create_time','between',$day)->count();
        $data = [
            'car_amount'=>$car_amount,
            'wait_amount' => $wait_amount,
            'go_amount' => $go_amount,
        ];
        return $data;
    }

}