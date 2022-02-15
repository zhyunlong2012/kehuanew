<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 出库表格
 */
class StorageOut extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'position_id'  => 'int',
        'position_area_id'  => 'int',
        'product_id'  => 'int',
        'amount'  => 'int',
        'desc'      => 'string',
        'source'      => 'int',
        'customer_id'      => 'string',
        'oprate_time'      => 'int',
        'create_time' => 'int',
        'update_time' =>'int',
        'factory_id' =>'int',
        'weight' =>'int',
        'user_id' => 'int'
    ];

    // 设置字段自动转换类型
    protected $type = [
        'oprate_time' => 'timestamp',
    ];


    /**
     * 添加出库
     *
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @param string $desc
     * @param integer $oprate_time
     * @param integer $source 来源 1单个 2批量 3扫码
     * @return void
     */
    public function add($position_id,$product_id,$amount=1,$desc,$oprate_time,$source=1,$position_area_id = null,$pack_code=null,$customer_id='',$factory_id='',$weight){
        
        $sto_out_model = new StorageOut();
        $uid = getUid();
        $sto_out_model->position_id = $position_id;
        $sto_out_model->product_id = $product_id;
        $sto_out_model->amount = $amount;
        $sto_out_model->desc = $desc;
        $sto_out_model->oprate_time = $oprate_time;
        $sto_out_model->source = $source;
        $sto_out_model->position_area_id = $position_area_id;
        $sto_out_model->customer_id = $customer_id;
        $sto_out_model->factory_id = $factory_id;
        $sto_out_model->weight = $weight;
        $sto_out_model->user_id = $uid;
        // switch($source){
        //     case 1:
        //         $sto_method = '单个出库';
        //         break;
        //     case 2:
        //         $sto_method = '批量出库';
        //         break;
        //     case 3:
        //         $sto_method = '扫码出库';
        //         break;
        //     default:
        //         $sto_method = '其他出库方式';
        //         break;
        // }
        // 启动事务
        Db::startTrans();
        try {
            $res = $sto_out_model->save();
            
            $storage_out_id =  $sto_out_model->id;
            $sto_in_model = new StorageIn();
            $res1 = $sto_in_model->realout($product_id,$amount,$pack_code,$storage_out_id,$position_area_id,$factory_id);
            if($amount>0){
                $amount = '-'.$amount;
            }else{
                $amount = abs($amount);
            }
            $sto_model = new Storage();
            $res2 = $sto_model->add($position_id,$product_id,$amount,$factory_id);
            
            $area_sto_model = new AreaStorage();
            $res3 = $area_sto_model->add($position_area_id,$product_id,$amount,$factory_id);
            
            $sto_customer_model = new StorageCustomer();
            $sto_customer_model -> add($position_id,$product_id,$amount,$factory_id,$customer_id);

            $area_sto_customer_model = new AreaStorageCustomer();
            $area_sto_customer_model -> add($position_area_id,$product_id,$amount,$factory_id,$customer_id);
            if($res&&$res1&&$res2&&$res3){
                // 提交事务
                Db::commit();
                // addLog($sto_method.'产品id:'.$product_id.'供应商:'.$factory_id.',库区'.$position_id.',数量'.$amount.'包裹号:'.$pack_code);
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
     * 返回产品列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $pro_code
     * @param [type] $factory_id
     * @param integer $amount
     * @param string $oprate_time
     * @return void
     */
    public function list($current=1,$pageSize=10,$position_id,$position_area_id,$pro_code,$factory_id,$pro_cates_id,$amount=0,$oprate_time='',$excel=false,$source,$customer_id='',$other_code='',$user_id='',$cal=2){
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
            $between_time = [strtotime($a['begin_time']) ,strtotime($a['end_time'])];
            $map[] = ['oprate_time','between',$between_time];
        // }else{
        //     $data['total'] = 0;
        //     $data['data'] = [];
        //     $data['success'] = true;
        //     $data['totalamount'] = 0;
        //     return json($data);
        }
        if(!empty($position_id)){$map[] = ['position_id','=',$position_id];}
        if(!empty($position_area_id)){$map[] = ['position_area_id','=',$position_area_id];}
        if(!empty($amount)){$map[] = ['amount','>=',$amount];}
        if(!empty($source)){$map[] = ['source','=',$source];}
        if(!empty($customer_id)){$map[] = ['customer_id','=',$customer_id];}
        if(!empty($factory_id)){$map[] = ['factory_id','=',$factory_id];}
        if(!empty($user_id)){$map[] = ['user_id','=',$user_id];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        if($cal){
            $data['totalamount'] = $this->where($map)->sum('amount');
        }
        
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        
        $fac_model = new Factory();
        $pos_model = new Position();
        $pro_model = new Product();
        $cus_model = new Customer();
        $pro_cates_model = new ProCates();
        $position_area_model = new PositionArea();
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
            if($data['data'][$key]['desc'] ==null)$data['data'][$key]['desc'] ='';

            $customer_tmp = $cus_model-> where('id',$value['customer_id'])->find();
            $data['data'][$key]['cus_name'] =$customer_tmp?$customer_tmp['cus_name']:'注销客户';
            $data['data'][$key]['cus_code'] =$customer_tmp?$customer_tmp['cus_code']:'注销客户';
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
                    '客户' => $value['cus_name'],
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

    

}