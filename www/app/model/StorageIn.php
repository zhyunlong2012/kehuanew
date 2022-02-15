<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 入库表格
 */
class StorageIn extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'position_id'  => 'int',
        'position_area_id'  => 'int',
        'product_id'  => 'int',
        'amount'  => 'int',
        'desc'      => 'string',
        'outamount'      => 'int',
        'pack_code'      => 'string',
        'source'      => 'int',
        'oprate_time'      => 'int',
        'create_time' => 'int',
        'update_time' =>'int',
        'factory_id' => 'int',
        'weight' =>'int',
        'customer_id' =>'int',
        'user_id' => 'int',
    ];

    // 设置字段自动转换类型
    protected $type = [
        'oprate_time' => 'timestamp',
    ];


    /**
     * 添加入库
     *
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @param string $desc
     * @param integer $oprate_time
     * @param integer $source 来源 1单个 2批量 3扫码
     * @return void
     */
    public function add($position_id,$product_id,$amount=1,$desc,$oprate_time,$source=1,$position_area_id = null,$pack_code=null,$factory_id='',$weight,$customer_id){
        $sto_in_model = new StorageIn();
        $uid = getUid();
        $sto_in_model->position_id = $position_id;
        $sto_in_model->product_id = $product_id;
        $sto_in_model->amount = $amount;
        $sto_in_model->desc = $desc;
        $sto_in_model->oprate_time = $oprate_time;
        $sto_in_model->source = $source;
        $sto_in_model->position_area_id = $position_area_id;
        $sto_in_model->pack_code = $pack_code;
        $sto_in_model->factory_id = $factory_id;
        $sto_in_model->weight = $weight;
        $sto_in_model->customer_id = $customer_id;
        $sto_in_model->user_id = $uid;
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $sto_in_model->save();
            $sto_model = new Storage();
            $res2 = $sto_model->add($position_id,$product_id,$amount,$factory_id);
            $area_sto_model = new AreaStorage();
            $res3 = $area_sto_model->add($position_area_id,$product_id,$amount,$factory_id);
            $res4 = true;
            if($amount<0){
                $res4 = $this->realout($product_id,abs($amount),'',$sto_in_model->id,$position_area_id,$factory_id,2);
            }
            $sto_customer_model = new StorageCustomer();
            $sto_customer_model -> add($position_id,$product_id,$amount,$factory_id,$customer_id);

            $area_sto_customer_model = new AreaStorageCustomer();
            $area_sto_customer_model -> add($position_area_id,$product_id,$amount,$factory_id,$customer_id);
            
            if($res1&&$res2&&$res3&&$res4){
                // 提交事务
                Db::commit();
                // addLog($sto_method.'成功!产品id:'.$product_id.',库区id'.$position_id.',数量'.$amount.'包裹号:'.$pack_code);
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

     /**
     * 盘点添加入库，不进行冲减
     * 1.对area_storage_customer进行出入库调整
     *
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @param string $desc
     * @param integer $oprate_time
     * @param integer $source 来源 1单个 2批量 3扫码
     * @return void
     */
    public function panadd($position_id,$product_id,$amount=1,$desc,$oprate_time,$source=1,$position_area_id = null,$pack_code=null,$factory_id='',$weight,$customer_id){
        $sto_in_model = new StorageIn();
        
        $sto_in_model->position_id = $position_id;
        $sto_in_model->product_id = $product_id;
        $sto_in_model->amount = $amount;
        $sto_in_model->desc = $desc;
        $sto_in_model->oprate_time = $oprate_time;
        $sto_in_model->source = $source;
        $sto_in_model->position_area_id = $position_area_id;
        $sto_in_model->pack_code = $pack_code;
        $sto_in_model->factory_id = $factory_id;
        $sto_in_model->weight = $weight;
        $sto_in_model->customer_id = $customer_id;
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $sto_in_model->save();
            //对area_sto_customer进行入库
            $area_sto_customer_model = new AreaStorageCustomer();
            $res2 = $area_sto_customer_model -> add($position_area_id,$product_id,$amount,$factory_id,$customer_id);
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                return true;
            }else{
                Db::rollback();
                return false;
            }

            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /**
     * 返回出入列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $pro_code
     * @param [type] $factory_id
     * @param integer $amount
     * @param string $oprate_time
     * @param boolean $excel true 全部数据 false 正常返回
     * @param int $source 
     * @param int $calculate  是否计算产品总数
     * @return void
     */
    public function inlist($current=1,$pageSize=10,$position_id,$position_area_id,$pro_code,$factory_id,$pro_cates_id,$amount=0,$oprate_time='',$excel=false,$source,$other_code='',$user_id='',$cal=2,$customer_id='',$desc=''){
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
                $data['data'] = [];
                $data['success'] = true;
                $data['totalamount'] = 0;
                return json($data);
            }
        }
        
        if(!empty($oprate_time)){
            $a = $oprate_time;
            // $a = json_decode($oprate_time,true);
            // $between_time = [strtotime($a['begin_time']) ,strtotime($a['end_time'])];
            $between_time = [strtotime($a[0]) ,strtotime($a[1])];
            $map[] = ['oprate_time','between',$between_time];
        // }else{
        //     $data['total'] = 0;
        //     $data['data'] = [];
        //     $data['success'] = true;
        //     $data['totalamount'] = 0;
        //     return json($data);
        }
        // $map[] = ['source','<>',4];  //非盘点
        if(!empty($position_id)){$map[] = ['position_id','=',$position_id];}
        if(!empty($position_area_id)){$map[] = ['position_area_id','=',$position_area_id];}
        if(!empty($factory_id)){$map[] = ['factory_id','=',$factory_id];}
        if(!empty($amount)){$map[] = ['amount','>=',$amount];}
        if(!empty($source)){$map[] = ['source','=',$source];}
        if(!empty($user_id)){$map[] = ['user_id','=',$user_id];}
        if(!empty($customer_id)){$map[] = ['customer_id','=',$customer_id];}
        if(!empty($desc)){$map[] = ['desc','like','%'.$desc.'%'];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        
        $data['total'] =  $this->where($map)->count();
        if($cal==1){
            $data['totalamount'] = $this->where($map)->sum('amount');
        }
        
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $fac_model = new Factory();
        $pos_model = new Position();
        $pro_model = new Product();
        $pro_cates_model = new ProCates();
        $position_area_model = new PositionArea();
        $cus_model = new Customer();
        $user_model = new User();
        foreach ($data['data'] as $key => $value) {
            $pro_tmp = $pro_model-> where('id',$value['product_id'])->find();
            if($pro_tmp){
                $data['data'][$key]['pro_name']= $pro_tmp['pro_name'];
                $data['data'][$key]['pro_code']= $pro_tmp['pro_code'];
                $data['data'][$key]['other_code']= $pro_tmp['other_code'];
                $cates_tmp = $pro_cates_model-> where('id',$pro_tmp['pro_cates_id'])->find();
                $data['data'][$key]['cates_name'] =$cates_tmp?$cates_tmp['cates_name']:'注销分类';

            }else{
                $data['data'][$key]['pro_name']= '注销产品';
                $data['data'][$key]['pro_code']= '注销产品';
                $data['data'][$key]['other_code']= '注销产品';
                $data['data'][$key]['cates_name']= '注销分类';
            }

            
            $fac_tmp = $fac_model ->where('id',$value['factory_id'])->find();
            $data['data'][$key]['fac_name'] =$fac_tmp?$fac_tmp['fac_name']:'注销厂家';

            $pos_tmp = $pos_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';

            $area_tmp = $position_area_model-> where('id',$value['position_area_id'])->find();
            $data['data'][$key]['area_code'] =$area_tmp?$area_tmp['code']:'注销库位';
            if($value['customer_id']){
                $cus_tmp = $cus_model ->where('id',$value['customer_id'])->find();
                $data['data'][$key]['cus_name'] =$cus_tmp?$cus_tmp['cus_name']:'注销客户';
            }else{
                $data['data'][$key]['cus_name'] ='注销客户';
            }

            $user_tmp = $user_model-> where('id',$value['user_id'])->find();
            $data['data'][$key]['username'] =$user_tmp?$user_tmp['username']:'注销用户';

            if($data['data'][$key]['desc'] ==null)$data['data'][$key]['desc'] ='';
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
                    '数量' => $value['amount'],
                    '操作时间' => $value['oprate_time'],
                    '来源' => $value['source'],
                    '简要说明' => $value['desc']
                ];
            }
            $data['data']=$excel_data;
        }

        return json($data);
    }

    /**
     * 冲减库存
     *
     * @param [type] $product_id
     * @param string $amount
     * @param [type] $pack_code
     * @param [type] $storage_out_id
     * @param [type] $position_area_id
     * @return void
     */
    public function realout($product_id,$amount,$pack_code,$storage_out_id,$position_area_id,$factory_id='',$source=1){
        
        $left = 0; //总剩余
        $real_out = 0;  //每条记录实际出库
        $out_amount = 0; //每条记录出库后 outamount
        if($pack_code){
            // $sto_scan = $this ->where('pack_code',$pack_code) ->where('position_area_id',$position_area_id)->findOrEmpty();
            $sto_scan = $this ->where('pack_code',$pack_code)->findOrEmpty();
            if(!$sto_scan->isEmpty()){
                $sto_scan ->outamount = $sto_scan['outamount'] + $amount;
                $minus_sto_model = new MinusSto();
                // 启动事务
                Db::startTrans();
                try {
                    $res1 = $sto_scan->save();
                    $res2 = $minus_sto_model->add($sto_scan['id'],$storage_out_id,$amount);
                    if($res1&&$res2){
                        // 提交事务
                        Db::commit();
                        return true;
                    }else{
                        Db::rollback();
                        return false;
                    }
                    
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    return false;
                }
            }
        }
        
        $map = [
            ['product_id','=',$product_id],
            ['position_area_id','=',$position_area_id],
            ['product_id','=',$product_id],
            ['amount','>',0]
        ];
        if($amount>0){
            $sto = $this->where($map)
            ->whereRaw('amount > outamount')
            ->order('oprate_time','asc')->limit(1)->select();
        }else{
            $sto = $this->where($map)
            ->where('outamount','>',0)
            ->order('oprate_time','desc')->limit(1)->select();
        }
        
        if(count($sto)==1){
            $record = StorageIn::where('id',$sto[0]['id'])->find();
            //如果出库为负，冲减到0时，向后冲减
            if($amount<0){
                if($record['outamount'] + $amount >0){
                    $out_amount = $record['outamount'] + $amount;
                    $real_out = $amount;
                }else{
                    $out_amount = 0;
                    $real_out = $record['outamount'];
                    $left = $record['outamount'] + $amount;
                }
            }else{
                if($record['outamount'] + $amount >$record['amount']){
                    $left = $record['outamount'] + $amount - $record['amount'];
                    $out_amount = $record['amount'];
                    $real_out = $record['amount']-$record['outamount'];
                }else{
                    $out_amount = $record['outamount'] + $amount;
                    $real_out = $amount;
                }
            }

            $record->outamount = $out_amount;
            $minus_sto_model = new MinusSto();
            // 启动事务
            Db::startTrans();
            try {
                $res1 = $record->save();
                $res2 = $minus_sto_model->add($record['id'],$storage_out_id,$real_out,'',$source);
                if($res1&&$res2){
                    // 提交事务
                    Db::commit();
                    if($left!=0){
                        return $this->realout($product_id,$left,$pack_code,$storage_out_id,$position_area_id,$factory_id,$source);
                    }else{
                        return true;
                    }
                    
                }else{
                    Db::rollback();
                    return false;
                }
                
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return false;
            }


        }else{
            return false;
        }
        
        
    }

    

}