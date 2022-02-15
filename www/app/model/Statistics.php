<?php
namespace app\model;
/**
 * 数据统计
 */
class Statistics{
    /**
     * 打包监控
     *
     * @param [type] $time
     * @return void
     */
    public function yclMonthStatis($current,$pageSize,$time,$pro_code='',$other_code='',$pro_cates_id='',$forward='in',$excel=false){
        $empty_data = [
            'total' =>0 ,
            'current' => 1,
            'pageSize' => 100,
            'success' => true,
            'data' => []
    
        ];
        // $position_model = new Position();
        // $position = $position_model ->where('id',$position_id) ->find();
        // if(!$position){return $empty_data;}

        $days = date('t', strtotime($time)); //该月天数
        $y = date("Y",strtotime($time));
        $m = date("m",strtotime($time));
        $begin_time = $y.'/'.$m.'/1';
        $day1 = strtotime(date($begin_time)); //第一天开始时间戳
        $day_end = $day1 + $days*60*60*24;
        $day_array=[];  //当月时间戳序列
        $day_dif = 60*60*24;
        $flag = $day1;
        for($i=1;$i<=$days;$i++){
            $tmp_day = [$flag,$flag+$day_dif-1];
            $flag = $flag+$day_dif;
            $day_array[] = $tmp_day;

        }
        // dump($day_array);
        //授权产品
        $product_model = new Product();
        $pro_map = [];
        $factory_model = new Factory();
        $auth_factory_ids = $factory_model ->get_auth(getUid());
        // dump($auth_factory_ids);
        $pro_factory_map[] = ['factory_id','in',$auth_factory_ids];
        // if(!empty($factory_id)){$pro_factory_map[]=['factory_id','=',$factory_id];}
        if(!empty($pro_code)){$pro_map[]=['pro_code','like','%'.$pro_code.'%'];}
        if(!empty($other_code)){$pro_map[]=['other_code','like','%'.$other_code.'%'];}
        if(!empty($pro_cates_id)){$pro_map[] = ['pro_cates_id','=',$pro_cates_id];}
        
        //入库
        if($forward=='out'){
            $sto_in_out_model = new StorageOut();
            $f = '出';
        }else{
            $sto_in_out_model = new StorageIn(); 
            $f = '入';
        }

        $exist_pro = $sto_in_out_model ->where('create_time','between',[$day1,$day_end])
                                        ->where($pro_factory_map)
                                        ->column('product_id');
        // dump($exist_pro);
        $uniqe_exist_pro = array_unique($exist_pro);
        $pro_map[] = ['id','in',$uniqe_exist_pro];
        if($excel){
            $products = $product_model->where($pro_map)->select();
        }else{
            $products = $product_model->page($current,$pageSize)->where($pro_map)->select();
        }
        $total = $product_model->where($pro_map)->count();
        // dump($products);
        // dump($pro_map);
        if(count($products)==0){return $empty_data;}

        $tmp_data = [];
        for($j=0;$j<count($products);$j++){
            $tmp_data[$j] = [
                'pro_name' => $products[$j]['pro_name'],
                'pro_code' => $products[$j]['pro_code'],
                'other_code' => $products[$j]['other_code'],
                'forward' => $f
            ];
            // $tmp_factory = $factory_model->where('id',$products[$j]['factory_id'])->find();
            // $tmp_data[$j]['fac_name']= $tmp_factory?$tmp_factory['fac_name']:'注销厂家';
            // $tmp_data[$j]['factory_id']= $tmp_factory?$tmp_factory['id']:'注销厂家';
            $tmp_total_amount = 0;
            for($i=0;$i<$days;$i++){
                $amount =  $sto_in_out_model->where($pro_factory_map)
                                            ->where('create_time','between',$day_array[$i])
                                            ->where('product_id',$products[$j]['id'])->sum('amount');;
                $tmp_data[$j][$i+1] = $amount; 
                $tmp_total_amount = $tmp_total_amount + $amount;
            }
            $tmp_data[$j]['t_amount'] = $tmp_total_amount;
            if( $tmp_total_amount==0){
                unset($tmp_data[$j]);
                $total= $total -1;
            }
        }
        $data['total'] =  $total;
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        $data['data'] = $tmp_data;
        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data_line = [
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '其他编码' => $value['other_code'],
                    // '生产厂家' => $value['fac_name'],
                    '方向'  => $f.'库',
                    '合计' => $value['t_amount']
                ];
                for($i=1;$i<=$days;$i++){
                    $excel_data_line[$i.'日'] = $value[$i];
                }
                $excel_data[] = $excel_data_line;
            }
            $data['data']=$excel_data;

        }
        // dump($data);
        return $data;
    }

   

}