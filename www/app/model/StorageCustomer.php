<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 库存表格
 */
class StorageCustomer extends Model{
    // 设置字段信息
    protected $schema = [
        'id'             => 'int',
        'position_id'    => 'int',
        'product_id'     => 'int',
        'factory_id'     => 'int',
        'amount'         => 'int',
        'customer_id'   => 'int'
    ];


    /**
     * 更改库存(如何不存在则添加，存在则修改)
     * @param integer $position_id
     * @param integer $product_id
     * @param integer $amount
     * @return void
     */
    public function add($position_id,$product_id,$amount,$factory_id,$customer_id){
        $map = [
            ['position_id','=' , $position_id],
            ['product_id','=',$product_id],
            ['factory_id','=',$factory_id],
            ['customer_id','=',$customer_id]
        ];

        $res = StorageCustomer::where($map)->findOrEmpty();
        if (!$res->isEmpty()) {
            //已有库存，修改库存
            if($amount<0){
                $total = $this->where($map)->sum('amount');
                if($total + $amount <0){
                    return false;
                }
            }
            $res->amount = $res['amount']+$amount;
            $res->save();
            // if($res->save() == true){
            //     return true;
            // }else{
            //     return false;
            // }
        }else{
            //未有库存，新增库存
            // if($amount<0){
            //     return false;
            // }
            $storage_model = new StorageCustomer();
            $storage_model->position_id = $position_id;
            $storage_model->product_id = $product_id;
            $storage_model->factory_id = $factory_id;
            $storage_model->amount = $amount;
            $storage_model->customer_id = $customer_id;
            $storage_model->save();
            // if($storage_model->save() == true){
            //     return true;
            // }else{
            //     return false;
            // }
        }
        return true;
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
    public function nowSto($current,$pageSize,$position_id,$pro_code,$factory_id,$pro_cates_id,$excel=false,$other_code='',$status='',$customer_id){
        
        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        $user_id = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$user_id)->find();
        if($user['user_group_id']!=1){
            if(empty($customer_id)){
                return json($data);
            }
        }
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
        if(!empty($customer_id)){$map[] = ['customer_id','=',$customer_id];}
        if(count($map)==0){
            return json($data);
        }
        
        $map[] = ['amount','<>',0];
        if(($status == 'lower')||($status == 'higher')){
            if($status == 'lower'){
                if($excel){
                    $data['data'] = Db::table('Storage')
                    ->where($map)
                    ->alias('s')
                    ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                    ->select()
                    ->toArray();

                    $data['total'] =  Db::table('Storage')
                    ->where($map)
                    ->alias('s')
                    ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                    ->count();
                }else{
                    if($excel){
                        $data['data'] = Db::table('Storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->select()
                        ->toArray();
        
                        $data['total'] =  Db::table('Storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->count();
                    }else{
                        $data['data'] = Db::table('Storage')
                        ->where($map)
                        ->page($current,$pageSize)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->select()
                        ->toArray();
        
                        $data['total'] =  Db::table('Storage')
                        ->where($map)
                        ->alias('s')
                        ->join('product p','s.amount <= p.low_line and s.product_id = p.id')
                        ->count();
                    }
                    
                }

                
                
            }else{
                $data['data'] = Db::table('Storage')
                ->where($map)
                ->page($current,$pageSize)
                ->alias('s')
                ->join('product p','s.amount >= p.high_line and s.product_id = p.id')
                ->select()
                ->toArray();
                $data['total'] =  Db::table('Storage')
                ->where($map)
                ->alias('s')
                ->join('product p','s.amount >= p.high_line and s.product_id = p.id')
                ->count();
            }

        }else{
            if($excel){
                // $data['data'] = $this->where($map)->order('id','desc')->select();
                $data['data'] = Db::table('storage_customer')
                ->where($map)
                ->alias('s')
                ->join('product p','s.product_id = p.id')
                ->select()
                ->toArray();
            }else{
                // $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
                $data['data'] = Db::table('storage_customer')
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
        $cus_model = new Customer();
        $pro_cates_model = new ProCates();
        $tmp_data = [
            'pro_name' => '注销产品',
            'pro_code' => '注销产品',
            'other_code' => '注销产品',
            'cates_name' => '注销产品',
            'cus_name' => '注销客户',
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

            $cus_tmp = $cus_model ->where('id',$value['customer_id'])->find();
            $data['data'][$key]['cus_name'] =$cus_tmp?$cus_tmp['cus_name']:'注销客户';

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
}
    