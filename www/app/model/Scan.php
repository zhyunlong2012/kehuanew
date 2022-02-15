<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 扫码打包表格
 */
class Scan extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'        => 'string',
        'pro_code'    => 'string',
        'fac_code'    => 'string',
        'cus_code'    => 'string',
        'amount'      => 'int',
        'status'      => 'int',  //1打包未入库 2入库 3出库 4上车(扫码随车) 5出厂  9作废
        'position_area_id' =>'int',
        'create_time' => 'int',
        'update_time' =>'int',
        'number' => 'string',  //随车单
        'user_id' => 'int',
        'weight'=>'string',
        'scandesc' => 'string' //来源
    ];


    /**
     * 添加打包
     *
     * @param [type] $product_id
     * @param [type] $amount
     * @param [type] $code  包裹二维码
     * @return void
     */
    public function add($pro_code,$fac_code,$cus_code,$amount,$code,$weight=0,$desc='',$status=1){
        $scan_model = new Scan();
        $user_id = getUid();
        $scan_model->user_id = $user_id;
        $scan_model->pro_code = $pro_code;
        $scan_model->fac_code = $fac_code;
        $scan_model->cus_code = $cus_code;
        $scan_model->amount = $amount;
        $scan_model->code = $code;
        $scan_model->status = $status;
        $scan_model->weight = $weight;
        $scan_model->scandesc= $desc;
        addLog('[扫码打包]二维码:'.$code.'产品:'.$pro_code.'厂家:'.$fac_code.'客户:'.$cus_code.'数量:'.$amount.'重量:'.$weight);
        if($scan_model->save() == true){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 修改信息
     *
     * @return void
     */
    public function updata($pro_code,$fac_code,$cus_code,$amount,$code,$weight=0){
        
         // 启动事务
         Db::startTrans();
         try {
            $scan_model = new Scan();
            $scan = $scan_model ->where('code',$code)->findOrEmpty();
            
            if(($scan['amount']==$amount)&&($scan['pro_code']==$pro_code)&&($scan['fac_code']==$fac_code)&&($scan['cus_code']==$cus_code)){
                return true;
            }
            $desc = '[扫码拆包]';
            if($scan['amount']!=$amount){
                $desc= $desc.'数量:'.$scan['amount'].'变更为:'.$amount.'重量:'.$scan['weight'].'变更为:'.$weight;
            }
            if (empty($scan)) { return false;}
            if((!empty($fac_code))&&($scan['fac_code']!=$fac_code)){$scan->fac_code = $fac_code;$desc= $desc.'厂家:'.$scan['fac_code'].'变更为:'.$fac_code;}
            if((!empty($pro_code))&&($scan['pro_code']!=$pro_code)){$scan->pro_code = $pro_code;$desc= $desc.'产品:'.$scan['pro_code'].'变更为:'.$pro_code;}
            if((!empty($cus_code))&&($scan['cus_code']!=$cus_code)){$scan->cus_code = $cus_code;$desc= $desc.'客户:'.$scan['cus_code'].'变更为:'.$cus_code;}
            $scan->amount = $amount;
            $scan->weight = $weight;
            
            $res1=$scan->save();
            $scan_log_model = new ScanLog();
            // $desc = '扫码拆包,原'.''.'新内容,产品'.$pro_code.'数量:'.$amount;
            $res2 = $scan_log_model->add($code,$desc);
             if($res1&&$res2){
                 addLog('单号:'.$scan['code'].$desc);
                // addLog('拆包,单号:'.$scan['code'].'原:'.$scan['pro_code'].'数量:'.$scan['amount']);
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

        // $scan_model = new Scan();
        // $res = $scan_model ->where('code',$code)->findOrEmpty();
        // if (empty($res)) {
        //     return false;
        // }else{
        //     if($res['status']==2){
        //         return false;
        //     }
        // }
        // addLog('拆包,单号:'.$res['code'].'原:'.$res['pro_code'].'数量:'.$res['amount']);

        // if(!empty($fac_code)){$res->fac_code = $fac_code;}
        // if(!empty($pro_code)){$res->pro_code = $pro_code;}
        // if(!empty($cus_code)){$res->cus_code = $cus_code;}
        // $res->amount = $amount;
        // if($res->save() == true){
        //     return true;
        // }else{
        //     return false;
        // }
    }

    /**
     * 打包列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $code
     * @param [type] $pro_code
     * @param [type] $fac_code
     * @param [type] $cus_code
     * @param [type] $position_area_id
     * @param [type] $status
     * @param boolean $excel
     * @param boolean $scan  是否是扫码定位
     * @param boolean $exact 是否精确按照包裹号查找
     * @return void
     */
    public function list(
        $current=1,
        $pageSize=10,
        $code,
        $pro_code,
        $fac_code,
        $cus_code,
        $position_id,
        $position_area_id,
        $status,
        $number='',
        $create_time='',
        $excel=false,
        $other_code='',
        $desc='',
        $exact=false){

        $data = [
            'data' => [],
            'total' =>0,
            'success' => true
        ];
        $map = [];
        if(!empty($position_id)){
            $position_area_model = new PositionArea();
            $area_id_list = $position_area_model-> where('position_id',$position_id)->column('id');
            if(count($area_id_list)>0){
                $map[]=['position_area_id','in',$area_id_list];
            }else{
                return json($data);
            }
        }

        $uid = getUid();
        $user_model = new User();
        $user = $user_model ->where('id',$uid) ->find();
        
        
        if(!$user){return json($data);}
        
        // if($user['user_group_id']!==1){
            $auth_ids  = $this->get_auth($uid);
            if(count($auth_ids)==0){
                return json($data);
            }else{
                $map[] = ['fac_code','in',$auth_ids];
            }
            
        // }

        if(!empty($other_code)){
            $product_model = new Product();
            $product = $product_model-> where('other_code',$other_code)->find();
            if($product){
                $map[]=['pro_code','like',$product['pro_code']];
            }else{
                return json($data);
            }
        }else{

            if(!empty($pro_code)){$map[]=['pro_code','like','%'.$pro_code.'%'];}
        }
        if($exact){
            if(!empty($code)){$map[]=['code','like',$code];}
        }else{
            if(!empty($code)){$map[]=['code','like','%'.$code.'%'];}
        }
        
        if(!empty($position_area_id)){$map[]=['position_area_id','=',$position_area_id];}
        if(!empty($fac_code)){$map[]=['fac_code','like','%'.$fac_code.'%'];}
        if(!empty($cus_code)){$map[]=['cus_code','like','%'.$cus_code.'%'];}
        if(!empty($desc)){$map[]=['scandesc','like','%'.$desc.'%'];}
        if(!empty($status)){$map[]=['status','=',$status];}
        if(!empty($number)){$map[]=['number','like','%'.$number.'%'];}
        if(!empty($create_time)){
            $between_time = [strtotime($create_time[0]) ,strtotime($create_time[1])];
            $map[] = ['create_time','between',$between_time];
        }
        
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $fac_model = new Factory();
        $pro_model = new Product();
        $cus_model = new Customer();
        $pos_model = new Position();
        $user_model = new User();
        $profile_model = new Profile();
        $position_area_model = new PositionArea();
        foreach ($data['data'] as $key => $value) {
            $pro_tmp = $pro_model-> where('pro_code',$value['pro_code'])->find();
            $data['data'][$key]['pro_name'] = $pro_tmp? $pro_tmp['pro_name']:'注销产品';
            $data['data'][$key]['other_code'] = $pro_tmp? $pro_tmp['other_code']:'注销产品';
            
            $fac_tmp = $fac_model ->where('fac_code',$value['fac_code'])->find();
            $data['data'][$key]['fac_name'] = $fac_tmp?$fac_tmp['fac_name']:'注销厂家';

            $cus_tmp = $cus_model ->where('cus_code',$value['cus_code'])->find();
            $data['data'][$key]['cus_name'] = $cus_tmp?$cus_tmp['cus_name']:'注销客户';
            $data['data'][$key]['cus_code'] = $cus_tmp?$cus_tmp['cus_code']:'注销客户';

            $pos = $position_area_model->where('id',$value['position_area_id'])->find();
            if($pos){
                $data['data'][$key]['pos_area_code'] =$pos?$pos['code']:'注销库位';
                $pos_tmp = $pos_model->where('id',$pos['position_id'])->find();
            }else{
                $data['data'][$key]['pos_area_code'] ='注销库位';
                $pos_tmp = null;
            }
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $profile = $profile_model->where('user_id',$value['user_id'])->find();
            if($profile){
                $data['data'][$key]['user_name']= $profile['nickname'];
            }else{
                $user_tmp = $user_model-> where('id',$value['user_id'])->find();
                $data['data'][$key]['user_name'] = $user_tmp? $user_tmp['username']:'注销用户';
            }
        }

        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '库区名称' => $value['pos_name'],
                    '库区编号' => $value['pos_code'],
                    '库位编号' => $value['pos_area_code'],
                    '包裹单号' => $value['code'],
                    '产品名称' => $value['pro_name'],
                    '产品编码' => $value['pro_code'],
                    '生产厂家' => $value['fac_name'],
                    '数量' => $value['amount'],
                    '客户' => $value['cus_code'],
                    '打包时间'=>$value['create_time']
                ];
            }
            $data['data']=$excel_data;

        }

        return json($data);
    }


    /**
     * 作废扫码
     *
     * @param string $code
     * @return void
     */
    public function invalid($code){
        if(empty($code)){return false;}
        $scan = $this->where('code',$code)->findOrEmpty();
        if (!$scan->isEmpty()) {
            if($scan['status']==2){
                return false;
            }
            $scan->status = 9;
            if($scan->save() == true){
                addLog('作废,单号:'.$scan['code']);
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 扫包裹入出库定位
     *
     * @param [type] $code
     * @param [type] $position_id
     * @param [type] $pos_area_code
     * @param [type] $desc
     * @param [type] $oprate_time
     * @param [type] $source 1单个 2批量 3 扫码
     * @param string $method  in 入库，out 出库
     * 20200617增加出库客户代码，如果有客户信息传入，则不提取包裹中客户代码，
     * @return void
     */
    public function packin($code,$pos_area_id,$desc,$oprate_time,$source=1,$method='in',$cus_code='',$position_id=''){
        // 启动事务
        Db::startTrans();
        try {
            $scan = $this->where('code',$code)->lock(true) ->find();
            if (!$scan) {  return 4; }
            //检测是否定位
            if($method === 'in'){
                // if(($scan['status']==2)||($scan['status']>=4)){return 8;}
                if($scan['status']==2){return 8;}
            }else{
                if($scan['status']!==2){return 9;}
            }

            $product_model = new Product();
            $product = $product_model ->where('pro_code',$scan['pro_code']) ->findOrEmpty();
            if ($product->isEmpty()) {
                  return 5; 
            }else{
                $weight = $scan['amount']*$product['weight'];
            }

            $position_area_model = new PositionArea();
            $map = [];
            // $map[] = ['code','=',$pos_area_code];
            if($position_id){$map[] = ['position_id','=',$position_id];}
            
            if($method == 'in'){
                // $area = $position_area_model ->where($map)->findOrEmpty();
                $area = $position_area_model ->where('id',$pos_area_id)->findOrEmpty();
            }else{
                $area = $position_area_model ->where('id',$scan['position_area_id'])->findOrEmpty();
            }
            
            if ($area->isEmpty()) { 
                return 2; 
            }else{
                $position_model = new Position();
                $position = $position_model ->where('id',$area['position_id'])->findOrEmpty();
                if ($position->isEmpty()) {  return 3; }
            }

            $fac_model = new Factory();
            $factory = $fac_model->where('fac_code',$scan['fac_code'])->find();
            if(!$factory){
                return 7;
            }
            
            $customer='';
            $cus_model = new Customer();
            if($cus_code||$scan['cus_code']){
                if($cus_code){
                    $customer = $cus_model->where('cus_code',$cus_code)->find();
                }else{
                    $customer = $cus_model->where('cus_code',$scan['cus_code'])->find();
                }
            }

            if(($method=='out')&&(!$customer)){
                return 10;
            }
            //定位
            
            // $user_id = getUid();
            // $scan->user_id = $user_id;
            if($method === 'in'){
                $scan->position_area_id = $area['id'];
                $scan->status = 2;
                $desc = '扫码入库，包裹号'.$code.'，库位'.$area['code'].'库区:'.$position['pos_name'];
            }else{
                $scan->position_area_id = '';
                $scan->status = 3;
                $desc = '扫码出库，包裹号'.$code.'，库位'.$area['code'].'库区:'.$position['pos_name'].'客户:'.$customer['cus_name'];
            }
        
            //修改库位现有包裹数
            if($method === 'in'){
                $area_packamount = $area['packamount']+1;
                $area->last_pro_code = $scan['pro_code'];
                $area->fac_code = $scan['fac_code'];
                $area->cus_code = $scan['cus_code'];
            }else{
                $area_packamount = $area['packamount']-1;
                if($area_packamount==0){
                    $area->last_pro_code = null;
                    $area->fac_code = null;
                    $area->cus_code = null;
                }
            }
            $area->packamount = $area_packamount;
            $scan_log_model = new ScanLog();
            
            $res1 = $scan_log_model->add($code,$desc);
             //入出库
            $customer_id = $customer?$customer['id']:'';
            $customer_code = $customer?$customer['cus_code']:'';
            if($method === 'in'){
                $sto_in_out_model = new \app\model\StorageIn();
                $res2 =  $sto_in_out_model->add($area['position_id'],$product['id'],$scan['amount'],$desc,$oprate_time,$source,$area['id'],$code,$factory['id'],$weight,$customer_id);
            }else{
                $sto_in_out_model = new \app\model\StorageOut();
                $res2 =  $sto_in_out_model->add($area['position_id'],$product['id'],$scan['amount'],$desc,$oprate_time,$source,$area['id'],$code,$customer_id,$factory['id'],$weight);
            }
            
            $res3 = $area->save();
            $res4 = $scan->save();
            if($res1&&$res2&&$res3&&$res4){
                // 提交事务
                Db::commit();
                $storage_model = new \app\model\Storage();
                $storage_model ->addStoLog($method, $source, $product['pro_code'], $factory['fac_code'], $position['pos_name'], $scan['amount'],$scan['weight'], $scan['code'], $customer_code);
                // addLog($sto_method.'成功!产品:'.$product['pro_code'].'厂家:'.$factory['fac_code'].',库区'.$position['pos_name'].',数量'.$scan['amount'].'包裹号:'.$scan['code'].'客户:'.$customer['cus_code']);
                return 1;
            }else{
                Db::rollback();
                return 6;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 6;
        }

    }

    /**
     * 包裹部分出库
     *
     * @param [type] $code
     * @param [type] $pos_area_code
     * @param [type] $desc
     * @param [type] $oprate_time
     * @param integer $source
     * @param string $method
     * @param string $cus_code
     * @return void
     */
    public function packoutpart($code,$oprate_time,$amount,$cus_code=''){
        // 启动事务
        Db::startTrans();
        try {
            $scan = $this->where('code',$code)->lock(true) ->find();
            if($scan['status']!==2){return 9;}
            $product_model = new Product();
            $product = $product_model ->where('pro_code',$scan['pro_code']) ->findOrEmpty();
            if ($product->isEmpty()) {
                  return 5; 
            }else{
                $weight = $amount*$product['weight'];
            }

            $position_area_model = new PositionArea();
            $area = $position_area_model ->where('id',$scan['position_area_id'])->findOrEmpty();
            
            if ($area->isEmpty()) { 
                return 2; 
            }else{
                $position_model = new Position();
                $position = $position_model ->where('id',$area['position_id'])->findOrEmpty();
                if ($position->isEmpty()) {  return 3; }
            }

            $fac_model = new Factory();
            $factory = $fac_model->where('fac_code',$scan['fac_code'])->find();
            if(!$factory){
                return 7;
            }

            $cus_model = new Customer();
            if($cus_code){
                $customer = $cus_model->where('cus_code',$cus_code)->find();
            }else{
                $customer = $cus_model->where('cus_code',$scan['cus_code'])->find();
            }
            
            if(!$customer){
                return 10;
            }
            //定位
            $desc = '扫码部分出库，包裹号'.$code.'，库位'.$area['code'].'库区:'.$position['pos_name'];
            $sto_out_model = new \app\model\StorageOut();
            $res =  $sto_out_model->add($area['position_id'],$product['id'],$amount,$desc,$oprate_time,3,$area['id'],$code,$customer['id'],$factory['id'],$weight);
           
            
            if($res){
                // 提交事务
                Db::commit();
                $storage_model = new \app\model\Storage();
                $storage_model ->addStoLog('out', 3, $product['pro_code'], $factory['fac_code'], $position['pos_name'], $amount,$weight, $scan['code'], $customer['cus_code']);
                return 1;
            }else{
                Db::rollback();
                return 6;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 6;
        }

    }

    /**
     * 随车单撤回
     *
     * @param [type] $number
     * @param int $step 撤回步骤
     * @return void
     */
    public function scanrollback($number,$step){
        $scan = $this->where('number',$number)->select();
        if(count($scan)<=0){
            return false;
        }
        
        $data = [];
        foreach($scan as $value){
            if($value['status']!=$step-2){
                $data[]=[
                    'id' =>$value['id'],
                    'number'=>null
                ];
            }else{
                $data[]=[
                    'id' =>$value['id'],
                    'status' => $step,
                    'number'=>null
                ];
            }
            
        }
        
        $res = $this->saveAll($data);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回出库未随车产品数量
     *
     * @param [type] $pro_code
     * @return void
     */
    public function scanOutAmount($pro_code){
        $user_id = getUid();
        // ->where('user_id',$user_id)
        $amount = $this ->where('pro_code',$pro_code)
                        ->where('status',3)
                        ->sum('amount');
        return $amount;
    }

    /**
     * 包裹在库区内移库位
     *
     * @param [type] $packcode
     * @param [type] $new_area_code
     * @return void
     */
    public function transArea($packcode,$new_area_id){
        // 启动事务
        Db::startTrans();
        try {
            $scan = $this->where('code',$packcode)->where('status',2)->lock(true)->find();
            if(!$scan){ return 2;}
            
            $fac_model = new Factory();
            $factory = $fac_model -> where('fac_code',$scan['fac_code'])->find();
            if(!$factory){return 7;}

            $position_area_model =  new PositionArea();
            $old_pos_area = $position_area_model->where('id',$scan['position_area_id'])->find();
            if(!$old_pos_area){ return 3;}

            $old_pos_area->packamount=$old_pos_area['packamount']-1;
            $res1 = $old_pos_area->save();

            $new_pos_area = $position_area_model->where('id',$new_area_id)->find();
            if(!$new_pos_area){ return 4;}
            $new_pos_area->packamount=$new_pos_area['packamount']+1;
            $res2 = $new_pos_area->save();
            
            $product_model = new Product();
            $product = $product_model ->where('pro_code',$scan['pro_code'])->find();
            if(!$product){ return 5;}
            $area_sto_model = new AreaStorage();
            $res3 = $area_sto_model->add($old_pos_area['id'],$product['id'],'-'.$scan['amount'],$factory['id']);
            $res4 = $area_sto_model->add($new_pos_area['id'],$product['id'],$scan['amount'],$factory['id']);

            
            $scan->position_area_id = $new_pos_area['id'];
            $res5 = $scan->save();
            $scan_log_model = new ScanLog();
            
            $res1 = $scan_log_model->add($packcode,'移库:由'.$old_pos_area['code'].'移动到'.$new_pos_area['code']);

            // $sto_in_model = new StorageIn();
            // $sto_in = $sto_in_model ->where('pack_code',$packcode) ->where('position_area_id',$old_pos_area['id'])->find();
            // if(!$sto_in){ return 8;}
            // $sto_in ->position_area_id = $new_pos_area['id'];
            // $res6 = $sto_in->save();
            if($res1&&$res2&&$res3&&$res4&&$res5){
                // 提交事务
                addLog($packcode.'移库:由'.$old_pos_area['code'].'移动到'.$new_pos_area['code']);
                Db::commit();
                return 1;
            }else{
                Db::rollback();
                return 6;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 6;
        }
    }


    /**
     * 盘点包裹出库，不进行增减库存
     *
     * @param [type] $code 包裹二维码
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @return void
     */
    public function checkpackout($code){
        // 启动事务
        Db::startTrans();
        try {
            $scan = $this->where('code',$code)->lock(true) ->find();
            if (!$scan) {  return false; }
            // //修改库位现有包裹数
            $position_area_model = new PositionArea();
            $area = $position_area_model ->where('id',$scan['position_area_id'])->find();
            if(!$area){return false;}

            $position_model = new Position();
            $position = $position_model ->where('id',$area['position_id'])->find();
            if(!$position){return false;}
            $scan->position_area_id = null;
            $scan->status = 3;
            $desc = '扫码盘点出库，包裹号'.$code.'，原库位'.$area['code'].'原库区:'.$position['pos_name'];
            $res = $scan->save();
            //如果在其他库位1.从其他库位出库，再入到该库位，如果库位id为空，则不用出库
            $scan_log_model = new ScanLog();
            //出库
            $area_packamount = $area['packamount']-1;
            if($area_packamount==0){
                $area->last_pro_code = null;
                $area->fac_code = null;
                $area->cus_code = null;
                
            }
            $area->packamount = $area_packamount;
            $res1 = $area->save();
            $res2 = $scan_log_model->add($code,$desc);
            if($res&&$res1&&$res2){
                // 提交事务
                Db::commit();
                $storage_model = new \app\model\Storage();
                $storage_model ->addStoLog('out', 4, $scan['pro_code'], $scan['fac_code'], $position['pos_name'], $scan['amount'],$scan['weight'], $scan['code'], '');
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
     * 盘点包裹入库不进行增减库存
     *
     * @param [type] $code 包裹二维码
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @return void
     */
    public function checkpackin($code,$position_area_id){
        // 启动事务
        Db::startTrans();
        try {
            $scan = $this->where('code',$code)->lock(true) ->find();
            if (!$scan) {  return 2; }
            //修改库位现有包裹数

            $position_area_model = new PositionArea();
            $area = $position_area_model ->where('id',$position_area_id)->find();
            if(!$area){return false;}
            
            $position_model = new Position();
            $position = $position_model ->where('id',$area['position_id'])->find();
            if(!$position){return false;}
            $scan->position_area_id = $position_area_id;
            $scan->status = 2;
            $desc = '扫码盘点入库，包裹号'.$code.'，库位'.$area['code'].'库区:'.$position['pos_name'];
            $res = $scan->save();

            $scan_log_model = new ScanLog();
            //入库
            $area_packamount = $area['packamount']+1;
            $area->last_pro_code = $scan['pro_code'];
            $area->fac_code = $scan['fac_code'];
            $area->cus_code = $scan['cus_code'];
            $area->packamount = $area_packamount;
            $res1 = $area->save();

            $res2 = $scan_log_model->add($code,$desc);
            
            if($res&&$res1&&$res2){
                // 提交事务
                Db::commit();
                $storage_model = new \app\model\Storage();
                $storage_model ->addStoLog('out', 4, $scan['pro_code'], $scan['fac_code'], $position['pos_name'], $scan['amount'],$scan['weight'], $scan['code'], '');
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 6;
        }
    }

    /**
     * 返回有权限的列表
     *
     * @return array
     */
    public function get_auth($uid){
        $auth_position_ids = [];
        $factory_model = new Factory();
        $positions = $factory_model->select()->toArray();
        foreach($positions as $value){
            $ids = json_decode($value['employs'],true);
            if(count($ids)<=0){continue;}
            if(in_array($uid,$ids)){
                $auth_position_ids[]=$value['fac_code'];
            }
        }
        return $auth_position_ids;
    }

}