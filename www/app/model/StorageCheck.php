<?php
namespace app\model;
use think\Model;
use think\facade\Db;
use think\model\concern\SoftDelete;
/**
 * 盘点表格
 */
class StorageCheck extends Model{
    // use SoftDelete;
    // protected $deleteTime = 'delete_time';
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title'       => 'string',
        'position_id' => 'int',
        'method' => 'int',  //1excel 盘点，2 扫码盘点
        'status'      => 'int',  //1盘点中 2已完成
        'user_id'     =>'int',
        'create_time' => 'int',
        'update_time' => 'int',
        'delete_time' => 'int',
    ];


    /**
     * 添加盘点表
     *
     * @param string $title 盘点表
     * @param integer $status
     * @return void
     */
    public function add($title,$position_id,$status=1,$method=1){
        $res = $this->where('title', $title)->where('position_id',$position_id)->findOrEmpty();
        if (!$res->isEmpty()) {  return 2; }
        $res1 = $this->where('status', 1)->where('position_id',$position_id)->find();
        if ($res1) {  return 3; }
        $user_id = getUid();
        $sto_check_model = new StorageCheck();
        $sto_check_model->title = $title;
        $sto_check_model->position_id = $position_id;
        $sto_check_model->method = $method;
        $sto_check_model->user_id = $user_id;
        $sto_check_model->status = $status;
        addLog('添加盘点表,盘点日期:'.$title);
        if($sto_check_model->save() == true){
            return 1;
        }else{
            return 4;
        }
    }

    /**
     * 返回盘点表列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param integer $car_id
     * @param string $title
     * @param integer $status
     * @return void
     */
    public function list($current,$pageSize,$title,$position_id,$status,$create_time=''){
        $map = [];
        if(!empty($title)){$map[]=['title','like','%'.$title.'%'];}
        if(!empty($position_id)){$map[]=['position_id','=',$position_id];}
        if(!empty($status)){$map[]=['status','=',$status];}
        if(!empty($create_time)){
            $between_time = [strtotime($create_time[0]) ,strtotime($create_time[1])];
            $map[] = ['create_time','between',$between_time];
        }
        
        $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        
        $position_model = new Position();
        $profile_model = new Profile();
        foreach ($data['data'] as $key => $value) {
            $pos_tmp = $position_model-> where('id',$value['position_id'])->find();
            $data['data'][$key]['pos_name'] =$pos_tmp?$pos_tmp['pos_name']:'注销库区';
            $data['data'][$key]['pos_code'] =$pos_tmp?$pos_tmp['pos_code']:'注销库区';
            $profile_tmp = $profile_model-> where('user_id',$value['user_id'])->find();
            $data['data'][$key]['username'] =$profile_tmp?$profile_tmp['nickname']:'未填写';
        }

        return json($data);
    }

   

    /**
     * 删除盘点表
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        $res = $this->where('id','in',$ids)->column('status');
        if(in_array(2,$res)){
            return false;
        }
        Db::startTrans();
        try {
            
            $res1 =  StorageCheck::destroy($ids);
            $storage_check_detail_model = new StorageCheckDetail();
            $detail_ids = $storage_check_detail_model->where('storage_check_id','in',$ids)->column('id');
            $res2 =  StorageCheckDetail::destroy($detail_ids);
            //删除扫码盘点
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                addLog('删除盘点表,盘点表id:'.implode(',', $ids));
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
     * 根据盘点数据自动开始盘点(Excel)
     * 1.对area_storage_customer进行出入库调整
     * 2.更新area_storage,storage_customer,storage数据
     * 3.对以上数据进行整理
     * @param [type] $storage_check_id
     * @return void
     */
    public function autoadd($storage_check_id,$position_id){
        $uid= getUid();
        $sto_check_detail_model = new StorageCheckDetail();
        $check_data = $sto_check_detail_model->where('storage_check_id',$storage_check_id)->select();  //盘点明细
        $num = count($check_data);

        $position_area_model = new PositionArea();
        $area_ids = $position_area_model->where('position_id',$position_id)->column('id');  //库位库位
        $area_storage_customer_model = new AreaStorageCustomer();
        $area_cus_sto = $area_storage_customer_model->where('position_area_id','in',$area_ids)->where('amount','<>',0)->select(); //库位库存
        // 启动事务
        Db::startTrans();
        try {
            $i=0;
            $area_sto_cus_already = []; //库位中已盘点
            foreach($check_data as $value){
                $area_sto_cus_already[] =[
                    'position_area_id' =>   $value['position_area_id'],
                    'product_id' =>  $value['product_id'],
                    'factory_id'  => $value['factory_id'],
                    'customer_id'  => $value['customer_id']
                ];
                if($value['amount']==0){$i++;continue;}
                //更改库存
                $res = $this->changeSto($position_id,
                                $value['position_area_id'],
                                $value['product_id'],
                                $value['factory_id'],
                                $value['different'],
                                $value['customer_id']);
                if($res){$i++;}
            }
            if($num==$i){
                // 提交事务
                Db::commit();
                // return true;
            }else{
                Db::rollback();
                return false;
            }

            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }

        //去掉库位库存中已盘点过的数据,对盘点单没有的数据进行出库，并且在盘点表中增加该条记录
        foreach($area_cus_sto as $value){
            $tmp=[
                'position_area_id' =>   $value['position_area_id'],
                'product_id' =>  $value['product_id'],
                'factory_id'  => $value['factory_id'],
                'customer_id'  => $value['customer_id']
            ];
            if(!in_array($tmp,$area_sto_cus_already)&&($value['amount']!=0)){
                if($value['amount']<0){
                    $new_amount = abs($value['amount']);
                }else{
                    $new_amount = '-'.$value['amount'];
                }
                $res = $this->changeSto($position_id,
                                        $value['position_area_id'],
                                        $value['product_id'],
                                        $value['factory_id'],
                                        $new_amount,
                                        $value['customer_id']);

                if($res){
                    $detail_row = [
                        'storage_check_id' => $storage_check_id,
                        'position_id' => $position_id,
                        'position_area_id' => $value['position_area_id'],
                        'product_id' =>$value['product_id'],
                        'factory_id' => $value['factory_id'],
                        'customer_id' => $value['customer_id'],
                        'amount' => 0,
                        'different' =>$new_amount,
                        'user_id' => $uid
                    ];
                    $sto_check_detail_model->save($detail_row);
                }
            }

        }
        //2.清空area_storage,storage_customer,storage数据

        $area_cus_sto = $area_storage_customer_model->where('position_area_id','in',$area_ids)->where('amount','<>',0)->select(); //库位库存
        //将库位客户库存整理成多维哈西数组 库位数组[库区][库位][产品][厂家][数量] 
        //客户数组[库区][产品][厂家][客户][数量] 库区数组[库区][产品][厂家][数量]
        $hash_cus_areas_sto = [];
        $hash_cus_pos_sto = [];
        $hash_pos_sto = [];
        // printf($area_cus_sto);
        foreach($area_cus_sto as $value){
            if(isset($hash_cus_areas_sto[$value['position_area_id']][$value['product_id']][$value['factory_id']])){
                $hash_cus_areas_sto[$value['position_area_id']][$value['product_id']][$value['factory_id']] =$hash_cus_areas_sto[$value['position_area_id']][$value['product_id']][$value['factory_id']] +$value['amount'];
            }else{
                $hash_cus_areas_sto[$value['position_area_id']][$value['product_id']][$value['factory_id']]=$value['amount'];
            }

            if(isset($hash_cus_pos_sto[$value['product_id']][$value['factory_id']][$value['customer_id']])){
                $hash_cus_pos_sto[$value['product_id']][$value['factory_id']][$value['customer_id']] =$hash_cus_pos_sto[$value['product_id']][$value['factory_id']][$value['customer_id']] +$value['amount'];
            }else{
                $hash_cus_pos_sto[$value['product_id']][$value['factory_id']][$value['customer_id']]=$value['amount'];
            }

            if(isset($hash_pos_sto[$value['product_id']][$value['factory_id']])){
                $hash_pos_sto[$value['product_id']][$value['factory_id']] = $hash_pos_sto[$value['product_id']][$value['factory_id']] +$value['amount'];
            }else{
                $hash_pos_sto[$value['product_id']][$value['factory_id']]=$value['amount'];
            }
            
        }
        //将hash数组转换为数据库格式
        $save_area_stos = []; //库位库存
        $save_cus_stos = []; //客户库存
        $save_pos_stos = []; //库区库存
        foreach($hash_cus_areas_sto as $key => $value){
            foreach($value as $key1=>$value1){
                foreach($value1 as $key2=>$value2){
                    $save_area_stos[] = [
                        'position_area_id'=>$key,
                        'product_id'=>$key1,
                        'factory_id'=>$key2,
                        'amount' =>$value2
                    ];
                }
            }
        }

        foreach($hash_cus_pos_sto as $key => $value){
            foreach($value as $key1=>$value1){
                foreach($value1 as $key2=>$value2){
                    $save_cus_stos[] = [
                        'position_id' => $position_id,
                        'product_id'=>$key,
                        'factory_id'=>$key1,
                        'customer_id'=>$key2,
                        'amount' =>$value2
                    ];
                }
            }
        }

        foreach($hash_pos_sto as $key => $value){
            foreach($value as $key1=>$value1){
                $save_pos_stos[] = [
                    'position_id' => $position_id,
                    'product_id'=>$key,
                    'factory_id'=>$key1,
                    'amount' =>$value1
                ];
            }
        }
        
        // 启动事务
        Db::startTrans();
        try {
            $area_sto_model = new AreaStorage();
            $area_sto_model ->where('position_area_id','in',$area_ids)->delete();
            $res1 = $area_sto_model->saveAll($save_area_stos);
            
            $sto_customer_model = new StorageCustomer();
            $sto_customer_model ->where('position_id',$position_id)->delete();
            $res2 = $sto_customer_model->saveAll($save_cus_stos);

            $sto_model = new Storage();
            $sto_model ->where('position_id',$position_id)->delete();
            $res3 = $sto_model->saveAll($save_pos_stos);

            $area_storage_customer_model->where('amount',0)->delete();
            //盘点完成，更改盘点状态
            $sto_check = $this->where('id',$storage_check_id)->find();
            $sto_check->status=2;
            $res4 = $sto_check->save();

            if($res1&&$res2&&$res3&&$res4){
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
     * 根据盘点数据更改库存(Excel)
     * 1.对area_storage_customer进行出入库调整
     *
     * @param [type] $position_id
     * @param [type] $position_area_id
     * @param [type] $product_id
     * @param [type] $factory_id
     * @param string $amount  差异
     * @return void
     */
    public function changeSto($position_id,$position_area_id,$product_id,$factory_id,$amount,$customer_id=''){
        if($amount==0){return true;}
        Db::startTrans();
        try {
            $product_model = new Product();
            $product = $product_model->where('id',$product_id)->find();
            if (!$product) {
                $weight = 0;
            }else{
                $weight = $amount*$product['weight'];
            }
            $sto_in_model = new StorageIn();
            //对area_storage_customer进行出入库调整
            $res = $sto_in_model->panadd($position_id,$product_id,$amount,'盘点',time(),4,$position_area_id,'',$factory_id,$weight,$customer_id);

            if($res){
                // 提交事务
                Db::commit();
                addLog('盘点!产品id:'.$product_id.',库区id'.$position_id.',库位id'.$position_area_id.',变化数量'.$amount);
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
     * 1先清空库位包裹数信息
     * 2根据盘点数据，仅调整包裹库位和状态信息，不调整库存，都采取入库操作，不进行调库，没有的包裹进行出库
     * 3并整理到EXCEL格式(扫码)
     * 4调整库存
     *
     * @param [type] $storage_check_id
     * @param [type] $position_id
     * @return void
     */
    public function autoscan($storage_check_id,$position_id){
        $scan_check_model = new ScanCheck();
        
        $check_data = $scan_check_model->where('storage_check_id',$storage_check_id)->select();
        $num = count($check_data);
        $i=0;
        //1先清空库位包裹数信息
        $position_area_model = new PositionArea();
        $areas = $position_area_model->where('position_id',$position_id)->select();
        $tmp_area = [];
        foreach($areas as $value){
            $tmp_area[] = [
                'id' => $value['id'],
                'last_pro_code' => '',
                'fac_code' => '',
                'cus_code' => '',
                'packamount' => 0
            ];
        }
        
        $position_area_model->saveAll($tmp_area);
        //2根据盘点数据，仅调整包裹库位信息，不调整库存，都采取入库操作，不进行调库，没有的包裹进行出库
        if($num>0) {
            foreach($check_data as $value){
                //调整包裹位置
                $res = $this->changeScanSto($position_id,$value['position_area_id'],$value['code']);
                if($res){$i++;}
            }
        }
        
        $scan_model = new Scan();
        //对包裹列表中在该库区，但盘点表没有的数据进行调整出库，不更改库存，仅更改包裹状态
        if($num==$i){
            // echo '包裹中有盘点数据没有';
            $area_ids = $position_area_model->where('position_id',$position_id)->column('id');
            // $area_storage_model = new AreaStorage();
            // $sto_scans = $scan_model->where('position_area_id','in',$area_ids)->select();
            $sto_scans_check_codes = $scan_check_model->where('storage_check_id',$storage_check_id)->where('position_id',$position_id)->column('code');
            $sto_scans = $scan_model->whereIn('position_area_id',$area_ids)  ->whereNotIn('code',$sto_scans_check_codes)->select();
            $tmp_sto_scans = [];
            foreach($sto_scans as $value){
                $tmp_sto_scans[] = [
                    'id' => $value['id'],
                    'position_area_id' => '',
                    'status' => 3
                ];
            }
            $scan_model ->saveAll($tmp_sto_scans);
            $scan_log_model = new ScanLog();
            $desc = '包裹批量盘点出库';
            $scan_log_model->add($value['code'],$desc);
            //更改状态、形成excel数据完成
            $res = $this->autoadd($storage_check_id,$position_id);
            // echo '更改状态、形成excel数据完成';
            if($res){
                return true;
            }else{
                return false;
            }
            
            
        }else{
            return false;
        }
    }
    

   /**
    * 扫码盘点，调整包裹位置
    *
    * @param [type] $position_area_id
    * @param [type] $code
    * @return void
    */
    public function changeScanSto($position_id,$position_area_id,$code){
        Db::startTrans();
        try {
            $scan_model = new Scan();
            $scan = $scan_model->where('code',$code)->lock(true) ->find();
            if (!$scan) {  return false; }
            $position_area_model = new PositionArea();
            $position_area = $position_area_model ->where('id',$position_area_id)->find();
            if(!$position_area) {return false;}
            $position_area->packamount = $position_area['packamount'] +1;
            $position_area->last_pro_code = $scan['pro_code'];
            $position_area->fac_code = $scan['fac_code'];
            $position_area->cus_code = $scan['cus_code'];
            $res1 = $position_area->save();
            //没有信息变化则不变动
            if(($scan['position_area_id']==$position_area_id)&&($scan['status']==2)){
                $res2 =  true;
            }else{
                $scan->position_area_id = $position_area_id;
                $scan->status = 2;
                $scan_log_model = new ScanLog();
                $desc = '盘点调整包裹状态';
                $res2 = $scan_log_model->add($code,$desc);
                $res2 = $scan->save();
            }
            //添加至扫码盘点表无则添加，有则累计
            $cus_model = new \app\model\Customer();
            if($scan['cus_code']){
                $customer = $cus_model->where('cus_code',$scan['cus_code'])->find();
                if($customer){
                    $customer_id =$customer['id'];
                }else{
                    $customer_id ='';
                }
            }else{
                $customer_id ='';
            }
            
            $factory_model = new \app\model\Factory();
            $factory = $factory_model->where('fac_code',$scan['fac_code'])->find();
            if($factory){
                $factory_id =$factory['id'];
            }else{
                $factory_id ='';
            }

            $product_model = new \app\model\Product();
            $product = $product_model->where('pro_code',$scan['pro_code'])->find();
            if($product){
                $product_id =$product['id'];
            }else{
                $product_id ='';
            }

            $sto_check_detail_model = new \app\model\StorageCheckDetail();
            $res3 = $sto_check_detail_model->add($position_id,$position_area['id'],$product_id,$factory_id,$customer_id,$scan['amount']);

            if($res1&&$res2&&$res3){
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