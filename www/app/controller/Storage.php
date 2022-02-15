<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Storage
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 单个产品入出库
     */
    public function single(){
        $pro_code = input('pro_code');
        $factory_id = input('factory_id');
        $position_id = input('position_id');
        $position_area_id = input('position_area_id');
        $amount = input('amount');
        $oprate_time  = input('oprate_time')?input('oprate_time'):date("Y-m-d H:i:s");
        $desc = input('desc');
        $source = input('source');
        $method = input('method');
        $position_area_code = input('position_area_code');
        // $code = input('code');
        $position_area_model = new \app\model\PositionArea();
        $fac_code = input('fac_code');
        $customer_id = input('customer_id');
        $cus_code= input('cus_code');  //批量用
        
        $factory_model = new \app\model\Factory();
        if($fac_code){
            $factory = $factory_model->where('fac_code',$fac_code)->findOrEmpty();
            if($factory->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
            }else{
                $factory_id=$factory['id'];
            }
        }else{
            $factory = $factory_model->where('id',$factory_id)->findOrEmpty();
            if($factory->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
            }
        }
        
        $position_model = new \app\model\Position();
        $position = $position_model->where('id',$position_id)->findOrEmpty();
        if ($position->isEmpty()) {
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
        }
        //批量EXCEL,或扫描非打包录入
        if($source==2){
            if($position_area_code){
                $area = $position_area_model->where('position_id',$position_id)->where('code',$position_area_code)->findOrEmpty();
                if ($area->isEmpty()) {
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
                }else{
                    $position_area_id = $area['id'];
                }
            }
        }else{
            if($position_area_id){
                
                $area = $position_area_model->where('id',$position_area_id)->findOrEmpty();
                if ($area->isEmpty()) {
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
                }
            }
        }

        if((empty($pro_code))||(empty($factory_id))||(empty($amount))||(empty($oprate_time))||(empty($source))||(empty($method))){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $product_model = new \app\model\Product();
        $product = $product_model->where('pro_code',$pro_code)->find();
        if (!$product) {
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
        }else{
            $product_id = $product['id'];
            $weight = $amount*$product['weight'];
        }
        $oprate_time  = strtotime($oprate_time);
        // if($method==='out'){
            $cus_model = new \app\model\Customer();
            if($customer_id||$cus_code){
                if($cus_code){
                    $customer = $cus_model->where('cus_code',$cus_code)->find();
                }else{
                    $customer = $cus_model->where('id',$customer_id)->find();
                }
                
                if($customer){
                    $customer_id = $customer['id'];
                }else{
                    // if($method==='out'){
                        return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                    // }
                }
            }else{
                if($method==='out'){
                    return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                }
            }
        // }
        
        // 启动事务
        Db::startTrans();
        try {
            if($method === 'in'){
                $sto_in_out_model = new \app\model\StorageIn();
                $res = $sto_in_out_model->add($position_id,$product_id,$amount,$desc,$oprate_time,$source,$position_area_id,null,$factory_id,$weight,$customer_id);
            }else{
                $sto_in_out_model = new \app\model\StorageOut();
                $res = $sto_in_out_model->add($position_id,$product_id,$amount,$desc,$oprate_time,$source,$position_area_id,null,$customer_id,$factory_id,$weight);
            }

            if($res){
                // 提交事务
                Db::commit();
                $storage_model = new \app\model\Storage();
                $storage_model ->addStoLog($method, $source, $product['pro_code'], $factory['fac_code'], $position['pos_name'], $amount,$weight,'', $cus_code);
                return $this->JsonSuccess();
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_OUT_STO);
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->JsonErr();
        }

    }

    /**
     * 入库列表
     * @return [type] [description]
     */
    public function inlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $pro_code=input('pro_code');
        $user_id = input('user_id');
        $factory_id = input('factory_id');
        $pro_cates_id = input('pro_cates_id');
        $position_id = input('position_id');
        $position_area_id = input('position_area_id');
        $amount = input('amount');
        $oprate_time = input('oprate_time');
        $excel = input('excel')?input('excel'):false;
        $source = input('source');
        $other_code = input('other_code');
        $cal = input('cal')?input('cal'):2;
        $customer_id = input('customer_id');
        $desc = input('desc');
        $sto_in_model = new \app\model\StorageIn();
        
        return $sto_in_model->inlist($current,$pageSize,$position_id,$position_area_id,$pro_code,$factory_id,$pro_cates_id,$amount,$oprate_time,$excel,$source,$other_code,$user_id,$cal,$customer_id,$desc);
    }

    /**
     * 出库列表
     * @return [type] [description]
     */
    public function outlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $pro_code=input('pro_code');
        $factory_id = input('factory_id');
        $customer_id = input('customer_id');
        $pro_cates_id = input('pro_cates_id');
        $position_id = input('position_id');
        $position_area_id = input('position_area_id');
        $amount = input('amount');
        $oprate_time = input('oprate_time');
        $excel = input('excel')?input('excel'):false;
        $source = input('source');
        $other_code = input('other_code');
        $user_id = input('user_id');
        $cal = input('cal')?input('cal'):2;
        $sto_out_model = new \app\model\StorageOut();
        
        return $sto_out_model->list($current,$pageSize,$position_id,$position_area_id,$pro_code,$factory_id,$pro_cates_id,$amount,$oprate_time,$excel,$source,$customer_id,$other_code,$user_id,$cal);
    }

    /**
     * 期间出入库列表
     * @return [type] [description]
     */
    public function betweenlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $pro_code=input('pro_code');
        $factory_id = input('fac_name');
        $position_id = input('position_id');
        $oprate_time = input('oprate_time');
        $excel = input('excel')?input('excel'):false;
        $source = input('source');
        $other_code = input('other_code');
        $sto_model = new \app\model\Storage();
        
        return $sto_model->list($current,$pageSize,$position_id,$pro_code,$factory_id,$oprate_time,$excel,$source,$other_code);
    }

     /**
     * 库区实时库存
     *
     * @return void
     */
    public function nowsto(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $excel = input('excel')?input('excel'):false;
        $position_id = input('position_id');
        $pro_code = input('pro_code');
        $factory_id = input('factory_id');
        $pro_cates_id = input('pro_cates_id');
        $other_code = input('other_code');
        $status = input('status');
        $storage_model = new \app\model\Storage();
        return $storage_model ->nowSto($current,$pageSize,$position_id,$pro_code,$factory_id,$pro_cates_id,$excel,$other_code,$status);
    }

     /**
     * 库区实时库存(客户)
     *
     * @return void
     */
    public function nowstocus(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $excel = input('excel')?input('excel'):false;
        $position_id = input('position_id');
        $pro_code = input('pro_code');
        $factory_id = input('factory_id');
        $pro_cates_id = input('pro_cates_id');
        $other_code = input('other_code');
        $status = input('status');
        $customer_id = input('customer_id');
        $storage_model = new \app\model\StorageCustomer();
        return $storage_model ->nowSto($current,$pageSize,$position_id,$pro_code,$factory_id,$pro_cates_id,$excel,$other_code,$status,$customer_id);
    }

    /**
     * 库位实时库存
     *
     * @return void
     */
    public function areanowsto(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $excel = input('excel')?input('excel'):false;
        $position_id = input('position_id');
        $position_area_id = input('position_area_id');
        $pro_code = input('pro_code');
        $factory_id = input('factory_id');
        $other_code = input('other_code');
        $area_storage_model = new \app\model\AreaStorage();
        return $area_storage_model ->nowSto($current,$pageSize,$position_id,$position_area_id,$pro_code,$factory_id,$excel,$other_code);
    }

    /**
     * 库龄
     *
     * @return void
     */
    public function agesto(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $excel = input('excel')?input('excel'):false;
        $position_id = input('position_id');
        $position_area_id = input('position_area_id');
        $other_code = input('other_code');
        if((empty($position_id)&&empty($position_area_id))||($position_id&&$position_area_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $storage_model = new \app\model\Storage();
        return $storage_model ->ageSto($current,$pageSize,$position_id,$position_area_id,$excel,$other_code);
    }


    /**
     * 修改库存(待完善)
     */
    public function updata(){
        $id = input('id');
        if(empty($id)){return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);}
        $pro_name = input('pro_name');
        $pro_code = input('pro_code');
        $factory_id = input('factory_id');
        $price  = input('price')?input('price'):0;
        $weight = input('weight')?input('weight'):0;
        $size  = input('size')?input('size'):0;
        $high_line = input('high_line')?input('high_line'):0;
        $low_line = input('low_line')?input('low_line'):0;
        
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        $pro_model = new \app\model\Product();
        $res = $pro_model->updata($id,$pro_name,$pro_code,$factory_id,$price,$weight,$size,$high_line,$low_line,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 初始化库区库存
     * @return [type] [description]
     */
    public function initsto(){
        $user_id =getUid();
        if($user_id!==1){
            return $this->JsonDataArr(ApiMsg::ERR_ACCESS_TOP_ADMIN);
        }
        $position_id = input('position_id');
        if($position_id==NULL){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        // 启动事务
        Db::startTrans();
        try {
            $storage_model = new \app\model\Storage();
            $storage_model ->where('position_id',$position_id)->delete();
            $position_area_model = new \app\model\PositionArea();
            $area_ids = $position_area_model->where('position_id',$position_id)->column('id');
            $areas = $position_area_model->where('position_id',$position_id)->select();
            if(count($area_ids)>0){
                $area_storage_model = new \app\model\AreaStorage();
                $area_storage_model ->where('position_area_id','in',$area_ids)->delete();
                
                $area_storage_customer_model = new \app\model\AreaStorageCustomer();
                $area_storage_customer_model ->where('position_area_id','in',$area_ids)->delete();
                
                $scan_model = new \app\model\Scan();
                $scans = $scan_model ->where('position_area_id','in',$area_ids)->select()->toArray();
                $tmp_scan = [];
                foreach($scans as $value){
                    $value['position_area_id']=null;
                    $value['status']=3;
                    $tmp_scan[] = $value;
                }
                $scan_model->saveAll($tmp_scan);
            }
            $storage_check_model = new \app\model\StorageCheck();
            $storage_check_model ->where('position_id',$position_id)->delete();
            $storage_check_detail_model = new \app\model\StorageCheckDetail();
            $storage_check_detail_model ->where('position_id',$position_id)->delete();
            $storage_customer_model = new \app\model\StorageCustomer();
            $storage_customer_model ->where('position_id',$position_id)->delete();
            $storage_in_model = new \app\model\StorageIn();
            $storage_in_model ->where('position_id',$position_id)->delete();
            $storage_out_model = new \app\model\StorageOut();
            $storage_out_model ->where('position_id',$position_id)->delete();
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

            // 提交事务
            Db::commit();
            addLog('清空库区库存，库区id'.$position_id);
            return $this->JsonSuccess();
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->JsonErr();
        }
    }
    

}