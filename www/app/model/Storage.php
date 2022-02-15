<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 库存表格
 */
class Storage extends Model{
    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'position_id'    => 'int',
        'product_id'     => 'int',
        'factory_id'     => 'int',
        'amount'         => 'int',
    ];


    /**
     * 更改库存(如何不存在则添加，存在则修改)
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @return void
     */
    public function add($position_id,$product_id,$amount,$factory_id=''){
        $map = [
            ['position_id','=' , $position_id],
            ['product_id','=',$product_id],
            ['factory_id','=',$factory_id]
        ];

        $res = Storage::where($map)->findOrEmpty();
        if (!$res->isEmpty()) {
            //已有库存，修改库存
            // if($amount<0){
            //     $total = $this->where($map)->sum('amount');
            //     if($total + $amount <0){
            //         return false;
            //     }
            // }
            $res->amount = $res['amount']+$amount;
            if($res->save() == true){
                // addLog('成功!更改库存,产品id:'.$product_id.',库区id'.$position_id.',数量'.$amount);
                return true;
            }else{
                // addLog('失败!更改库存,产品id:'.$product_id.',库区id'.$position_id.',数量'.$amount);
                return false;
            }
        }else{
            //未有库存，新增库存
            if($amount<0){
                return false;
            }
            $storage_model = new Storage();
            $storage_model->position_id = $position_id;
            $storage_model->product_id = $product_id;
            $storage_model->factory_id = $factory_id;
            $storage_model->amount = $amount;
            
            if($storage_model->save() == true){
                // addLog('成功!更改库存,产品id:'.$product_id.',库区id'.$position_id.',数量'.$amount);
                return true;
            }else{
                // addLog('失败!更改库存,产品id:'.$product_id.',库区id'.$position_id.',数量'.$amount);
                return false;
            }
        }
    }

    /**
     * 实时库存
     *
     * @param [type] $current
     * @param [type] $pageSize
     * @param [type] $position_id
     * @param [type] $pro_code
     * @param [type] $factory_id
     * @param boolean $excel
     * @return void
     */
    public function nowSto($current,$pageSize,$position_id,$pro_code,$factory_id,$pro_cates_id,$excel=false,$other_code='',$status=''){
        $map1 = [];
        $map = [];
        if(!empty($pro_code)){$map1[] = ['pro_code','=',$pro_code];}
        if(!empty($other_code)){$map1[] = ['other_code','=',$other_code];}
        if(!empty($pro_cates_id)){$map1[] = ['pro_cates_id','=',$pro_cates_id];}
        if(count($map1)>0){
            $pro_model = new Product();
            $pro_id_list = $pro_model-> where($map1)->column('id');
            if(count($pro_id_list)>0){
                $map[]=['product_id','in',$pro_id_list];
            }else{
                $data['total'] = 0;
                $data['success'] = true;
                return json($data);
            }
        }

        if(!empty($position_id)){$map[] = ['position_id','=',$position_id];}
        if(!empty($factory_id)){$map[] = ['factory_id','=',$factory_id];}
        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        if(count($map)==0){
            return json($data);
        }
        
        $map[] = ['amount','<>',0];
        if(($status == 'lower')||($status == 'higher')){
            if($status == 'lower'){
                if($excel){
                    $data['data'] = Db::table('storage')
                    ->where($map)
                    ->alias('s')
                    ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                    ->select()
                    ->toArray();

                    $data['total'] =  Db::table('storage')
                    ->where($map)
                    ->alias('s')
                    ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                    ->count();
                }else{
                    if($excel){
                        $data['data'] = Db::table('storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->select()
                        ->toArray();
        
                        $data['total'] =  Db::table('storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->count();
                    }else{
                        $data['data'] = Db::table('storage')
                        ->where($map)
                        ->page($current,$pageSize)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->select()
                        ->toArray();
        
                        $data['total'] =  Db::table('storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->count();
                    }
                    
                }

                
                
            }else{
                $data['data'] = Db::table('storage')
                ->where($map)
                ->page($current,$pageSize)
                ->alias('s')
                ->join('product p','s.amount >= p.high_line and s.product_id = p.id')
                ->select()
                ->toArray();
                $data['total'] =  Db::table('storage')
                ->where($map)
                ->alias('s')
                ->join('product p','s.amount >= p.high_line and s.product_id = p.id')
                ->count();
            }

        }else{
            if($excel){
                // $data['data'] = $this->where($map)->order('id','desc')->select();
                $data['data'] = Db::table('storage')
                ->where($map)
                ->alias('s')
                ->join('product p','s.product_id = p.id')
                ->select()
                ->toArray();
            }else{
                // $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
                $data['data'] = Db::table('storage')
                ->where($map)
                ->page($current,$pageSize)
                ->alias('s')
                ->join('product p','s.product_id = p.id')
                ->select()
                ->toArray();
            }
            $data['total'] =  $this->where($map)->count();
        }
        
        
        
        
        // $data['total'] =  $this->where($map)->count();
        
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $fac_model = new Factory();
        $pro_model = new Product();
        $pos_model = new Position();
        $pro_cates_model = new ProCates();
        $tmp_data = [
            'pro_name' => '注销产品',
            'pro_code' => '注销产品',
            'other_code' => '注销产品',
            'cates_name' => '注销产品',
            'high_line' => '注销产品',
            'low_line' => '注销产品',
        ];
        foreach ($data['data'] as $key => $value) {
            $pro_tmp = $pro_model-> where('id',$value['product_id'])->find();
            if($pro_tmp){
                // $data['data'][$key]['pro_name']= $pro_tmp['pro_name'];
                // $data['data'][$key]['pro_code']= $pro_tmp['pro_code'];
                // $data['data'][$key]['other_code']= $pro_tmp['other_code'];
                $cates_tmp = $pro_cates_model ->where('id',$pro_tmp['pro_cates_id'])->find();
                $data['data'][$key]['cates_name'] =$cates_tmp?$cates_tmp['cates_name']:'注销分类';
                // $data['data'][$key]['high_line'] =$pro_tmp['high_line'];
                // $data['data'][$key]['low_line'] =$pro_tmp['low_line'];
            }else{
                $data['data'][$key]=$tmp_data;
            }

            
            $fac_tmp = $fac_model ->where('id',$value['factory_id'])->find();
            $data['data'][$key]['fac_name'] =$fac_tmp?$fac_tmp['fac_name']:'注销厂家';

            $pos_tmp = $pos_model->where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
        } 
        
        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '库区名称' => $value['pos_name'],
                    '库区编号' => $value['pos_code'],
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '其他编码' => $value['other_code'],
                    '生产厂家' => $value['fac_name'],
                    '产品分类' => $value['cates_name'],
                    '数量' => $value['amount']
                ];
            }
            $data['data']=$excel_data;

        }
        return json($data);

    }

    /**
     * 返回期间库存列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @return void
     */
    public function list($current=1,$pageSize=10,$position_id,$pro_code,$factory_id,$oprate_time='',$excel=false,$source,$other_code=''){
        $map1 = [];
        $map = [];
        if(!empty($pro_code)){$map1[] = ['pro_code','=',$pro_code];}
        if(!empty($other_code)){$map1[] = ['other_code','=',$other_code];}
        if($pro_code||$other_code){
            $pro_model = new Product();
            $pro_id_list = $pro_model-> where($map1)->column('id');
            if(count($pro_id_list)>0){
                $map[]=['product_id','in',$pro_id_list];
            }else{
                $data['total'] = 0;
                $data['data'] = [];
                $data['success'] = true;
                return json($data);
            }
        }
        if(!empty($oprate_time)){
            $a = $oprate_time;
            // $a = json_decode($oprate_time,true);
            // $between_time = [strtotime($a['begin_time']) ,strtotime($a['end_time'])];
            $between_time = [strtotime($a[0]) ,strtotime($a[1])];
            $sto_check_model = new StorageCheck();
            $last_check =  $sto_check_model ->where('create_time','<',$between_time[0])
                                            ->where('status',2)
                                            ->order('id','desc')
                                            ->limit(1)->find();
                                            
            if($last_check){
                // $last_check_to_begin_time = [strtotime($last_check['update_time']),strtotime($a['end_time'])];
                $last_check_to_begin_time = [strtotime($last_check['update_time']),strtotime($a[1])];
            }
            $map_time[] = ['oprate_time','between',$between_time];
        }else{
            $data['total'] = 0;
            $data['data'] = [];
            $data['success'] = true;
            return json($data);
        }
        
        if(!empty($position_id)){$map[] = ['position_id','=',$position_id];}
        if(!empty($source)){$map[] = ['source','=',$source];}
        if(!empty($factory_id)){$map[] = ['factory_id','=',$factory_id];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        
       
        $sto_check_ids = $sto_check_model->where('position_id',$position_id)->where('update_time','between',$between_time)->where('status',2)->column('id');
        // echo '期间盘点ID';
        $fac_model = new Factory();
        $pos_model = new Position();
        $pro_model = new Product();
        $sto_in_mode = new StorageIn();
        $sto_out_model = new StorageOut();
        $sto_check_detail_model = new StorageCheckDetail();
        foreach ($data['data'] as $key => $value) {
            $pro_tmp = $pro_model-> where('id',$value['product_id'])->find();
            if($pro_tmp){
                $data['data'][$key]['pro_name']= $pro_tmp['pro_name'];
                $data['data'][$key]['pro_code']= $pro_tmp['pro_code'];
                $data['data'][$key]['other_code']= $pro_tmp['other_code'];
            }else{
                $data['data'][$key]['pro_name']= '注销产品';
                $data['data'][$key]['pro_code']= '注销产品';
                $data['data'][$key]['ohter_code']= '注销产品';
            }
            
            $fac_tmp = $fac_model ->where('id',$value['factory_id'])->find();
            $data['data'][$key]['fac_name'] =$fac_tmp?$fac_tmp['fac_name']:'注销厂家';

            $pos_tmp = $pos_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $map2 = [
                ['product_id','=',$value['product_id']],
                // ['position_id','=',$value['position_id']],
                ['factory_id','=',$value['factory_id']],
            ];
            //期间入库
            $data['data'][$key]['between_in'] = $sto_in_mode->where($map)->where($map_time)-> where($map2)->where('source','<>',4)->sum('amount');
            //期间出库
            $data['data'][$key]['between_out'] = $sto_out_model->where($map)->where($map_time)-> where($map2)->sum('amount');
            //期间盘点
            $data['data'][$key]['between_pan'] =  $sto_check_detail_model->where('position_id',$position_id)
                                                                         ->where('update_time','between',$between_time)
                                                                         ->where('storage_check_id','in',$sto_check_ids)
                                                                         ->where($map2)
                                                                         ->sum('different');
            //如果有盘点记录                                                           
            if($last_check){
                $sto_check_to_begin_ids = $sto_check_model->where('position_id',$position_id)->where('update_time','between',$last_check_to_begin_time)->where('status',2)->column('id');
                $last_check_to_begin_time = [strtotime($last_check['update_time']),strtotime($a[0])];
                // $last_check_to_begin_time = [strtotime($last_check['update_time']),strtotime($a['begin_time'])];
                // echo '期间有盘点';
                $last_check_detail = $sto_check_detail_model->where('storage_check_id',$last_check['id'])->where($map)->where($map2)->find();
                $check_amount =$last_check_detail?$last_check_detail['amount']:0;
                // echo '期间有盘点，盘点数';
                //盘点到期初入库出库盘点
                $check_to_begin_in = $sto_in_mode->where($map)->where('oprate_time','between',$last_check_to_begin_time)-> where($map2)->sum('amount');
                $check_to_begin_out = $sto_out_model->where($map)->where('oprate_time','between',$last_check_to_begin_time)-> where($map2)->sum('amount');
                $tmp_detail = $sto_check_detail_model->where('update_time','between',$last_check_to_begin_time) ->where('storage_check_id','in',$sto_check_to_begin_ids)->where($map)->where($map2)->sum('different');
    
                $check_to_begin_check =$tmp_detail?$tmp_detail:0;
                $data['data'][$key]['begin_amount'] = $check_to_begin_in - $check_to_begin_out + $check_to_begin_check + $check_amount ;
            }else{
                //期初
                $begin_in = $sto_in_mode->where($map)->where('oprate_time','<',$between_time[0])-> where($map2)->sum('amount');
                $begin_out = $sto_out_model->where($map)->where('oprate_time','<',$between_time[0])-> where($map2)->sum('amount');
                
                $data['data'][$key]['begin_amount'] = $begin_in - $begin_out ;

            }
            //期末
            $data['data'][$key]['end_amount'] =  $data['data'][$key]['begin_amount'] 
                                                + $data['data'][$key]['between_pan']
                                                +$data['data'][$key]['between_in']
                                                -$data['data'][$key]['between_out'];
            // $end_in = $sto_in_mode->where($map)->where('oprate_time','<=',$between_time[1])-> where($map2)->sum('amount');
            // $end_out = $sto_out_model->where($map)->where('oprate_time','<=',$between_time[1])-> where($map2)->sum('amount');
            // $data['data'][$key]['end_amount'] = $end_in - $end_out + $data['data'][$key]['between_pan'];
        }
        
        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '库区名称' => $value['pos_name'],
                    '库区编号' => $value['pos_code'],
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '其他编码' => $value['other_code'],
                    '生产厂家' => $value['fac_name'],
                    '期初数量' => $value['begin_amount'],
                    '期间入库' => $value['between_in'],
                    '期间出库' => $value['between_out'],
                    '期间盘点' => $value['between_pan'],
                    '期末数量' => $value['end_amount']
                ];
            }
            $data['data']=$excel_data;

        }
        return json($data);
    }

     /**
     * 库龄
     *
     * @param [type] $position_id
     * @return void
     */
    public function ageSto($current,$pageSize,$position_id,$position_area_id,$excel=false,$factory_id=''){
        $one_month_ago =  strtotime("-1 month");
        $three_month_ago =  strtotime("-3 month");
        $six_month_ago =  strtotime("-6 month");
        $one_year_ago =  strtotime("-1 year");
        $two_year_ago =  strtotime("-2 year");
        $three_year_ago =  strtotime("-3 year");
        if($factory_id){
            $map[] = ['factory_id','=',$factory_id];
        }
        if($position_id){
            $map = [
                ['position_id','=',$position_id],
                ['amount','exp', Db::raw('<> outamount')]
            ];
        }

        if($position_area_id){
            $map = [
                ['position_area_id','=',$position_area_id],
                ['amount','exp', Db::raw('<> outamount')]
            ];
        }
        
        $mapAll = [
            ['map'=>[$map[0],$map[1],['oprate_time','>',$one_month_ago]],'desc'=>'一个月内'],
            ['map'=>[$map[0],$map[1],['oprate_time','>',$three_month_ago],['oprate_time','<=',$one_month_ago]],'desc'=>'一至三个月'],
            ['map'=>[$map[0],$map[1],['oprate_time','>',$six_month_ago],['oprate_time','<=',$three_month_ago]],'desc'=>'三至六个月'],
            ['map'=>[$map[0],$map[1],['oprate_time','>',$one_year_ago],['oprate_time','<=',$six_month_ago]],'desc'=>'六个月至一年'],
            ['map'=>[$map[0],$map[1],['oprate_time','>',$two_year_ago],['oprate_time','<=',$one_year_ago]],'desc'=>'一年至二年'],
            ['map'=>[$map[0],$map[1],['oprate_time','>',$three_year_ago],['oprate_time','<=',$two_year_ago]],'desc'=>'二年至三年'],
            ['map'=>[$map[0],$map[1],['oprate_time','<=',$three_year_ago]],'desc'=>'三年以上']
        ];

        $res = [];
        foreach($mapAll as $value){
            $res1 = $this->calsto($value['map'],$value['desc']);
            $res = array_merge($res,$res1);
            
        }

        $fac_model = new Factory();
        $pro_model = new Product();
        foreach ($res as $key => $value) {
            $pro_tmp = $pro_model-> where('id',$value['product_id'])->find();
            if($pro_tmp){
                $res[$key]['pro_name']= $pro_tmp['pro_name'];
                $res[$key]['pro_code']= $pro_tmp['pro_code'];
                $res[$key]['other_code']= $pro_tmp['other_code'];
            }else{
                $res[$key]['pro_name']= '注销产品';
                $res[$key]['pro_code']= '注销产品';
                $res[$key]['other_code']= '注销产品';
            }
        }   
        $data['data'] = $res;
        $data['total'] = count($res);
        $data['current'] = 1;
        $data['pageSize'] = $data['total'];
        $data['success'] = true;

        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '其他编码' => $value['other_code'],
                    '数量' => $value['amount'],
                    '库龄' =>$value['desc']
                ];
            }
            $data['data']=$excel_data;

        }
        return json($data);
        
    }

    /**
     * 统计$map 时间段数组
     *
     * @param array $map
     * @param [type] $desc
     * @return array
     */
    public function calsto($map=[],$desc){
        $storage_in_model = new StorageIn();
        $storage = $storage_in_model ->where($map)->select();
        if(count($storage)==0){
            return [];
        }

        //物料hash数组
        $hash_pro = [];
        foreach($storage as $value){
            $hash_pro[$value['product_id']] = 0;
        }

        //计算每种产品剩余数量
        foreach($storage as $value){
            $hash_pro[$value['product_id']] =$hash_pro[$value['product_id']] + $value['amount'] -$value['outamount'];
        }  

        //整理成数组
        $res = [];
        foreach($hash_pro as $key => $value){
            $res[]  = [
                'product_id' => $key,
                'amount' => $value,
                'desc' =>$desc
            ];
        }
        return $res;
    }

    /**
     * 盘点(待完成)
     *
     * @return void
     */
    

    /**
     * 入出库操作记录
     *
     * @param [type] $method
     * @param [type] $source
     * @param [type] $pro_code
     * @param [type] $fac_code
     * @param [type] $pos_name
     * @param [type] $amount
     * @param [type] $pack_code
     * @param [type] $cus_code
     * @return void
     */
     public function addStoLog($method,$source,$pro_code,$fac_code,$pos_name,$amount,$weight,$pack_code,$cus_code){
        if($method === 'in'){
            switch($source){
                case 1:
                    $sto_method = '单个入库';
                    break;
                case 2:
                    $sto_method = '批量入库';
                    break;
                case 3:
                    $sto_method = '扫码入库';
                    break;
                case 4:
                    $sto_method = '扫码盘点入库';
                    break;
                default:
                    $sto_method = '其他入库方式';
                    break;
            }
        }else{
            switch($source){
                case 1:
                    $sto_method = '单个出库';
                    break;
                case 2:
                    $sto_method = '批量出库';
                    break;
                case 3:
                    $sto_method = '扫码出库';
                    break;
                case 4:
                    $sto_method = '扫码盘点出库';
                    break;
                default:
                    $sto_method = '其他出库方式';
                    break;
            }
        }
        $desc = '['.$sto_method.']:产品'.$pro_code.'厂家'.$fac_code.',库区'.$pos_name.',数量'.$amount.'重量'.$weight;
        if(!empty($pack_code)){ $desc = $desc.'包裹号'.$pack_code;}
        if(!empty($cus_code)){$desc = $desc.'客户'.$cus_code;}
        addLog($desc);
     }

    
}