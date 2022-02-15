<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class StorageCheck
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加盘点信息
     */
    public function add(){
        $title = input('title')?input('title'):time();
        $position_id = input('position_id');
        $status = input('status');
        $status=1;
        if(empty($title)||empty($position_id)||empty($status)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $sto_check_model = new \app\model\StorageCheck();
        $res = $sto_check_model->add($title,$position_id,$status);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_EXIST);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECKING);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 添加盘点详情
     */
    public function adddetail(){
        $position_id = input('position_id');
        $position_area_code = input('position_area_code');
        $product_code = input('pro_code');
        $factory_code = input('fac_code');
        $customer_code = input('cus_code');
        $amount=input('amount');
        if(empty($position_id)||empty($position_area_code)||empty($product_code)||empty($factory_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $position_area_model = new \app\model\PositionArea();
        $position_area = $position_area_model->where('position_id',$position_id) ->where('code',$position_area_code)->find();
        if(!$position_area){return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);}

        $product_model = new \app\model\Product();
        $product = $product_model ->where('pro_code',$product_code)->find();
        if(!$product){return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);}

        $factory_model = new \app\model\Factory();
        $factory = $factory_model ->where('fac_code',$factory_code)->find();
        if(!$factory){return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);}
        $customer_id = '';
        if($customer_code){
            $customer_model = new \app\model\Customer();
            $customer = $customer_model ->where('cus_code',$customer_code)->find();
            if(!$customer){
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
            }else{
                $customer_id = $customer['id'];
            }
        }
        
        $sto_check_detail_model = new \app\model\StorageCheckDetail();
        $res = $sto_check_detail_model->add($position_id,$position_area['id'],$product['id'],$factory['id'],$customer_id,$amount);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_FINISH);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 盘点列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $title = input('title');
        $status = input('status');
        $position_id = input('position_id');
        $create_time = input('create_time');
        $sto_check_model = new \app\model\StorageCheck();
        return $sto_check_model->list($current,$pageSize,$title,$position_id,$status,$create_time);
    }

     /**
     * 盘点详情列表
     * @return [type] [description]
     */
    public function detaillist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $storage_check_id = input('storage_check_id');
        $position_area_id = input('position_area_id');
        $position_id = input('position_id');
        $product_id = input('product_id');
        $create_time = input('create_time');
        $sto_check_detail_model = new \app\model\StorageCheckDetail();
        return $sto_check_detail_model->list($current,$pageSize,$storage_check_id,$position_id,$position_area_id,$product_id,$create_time);
    }


    /**
     * 删除盘点
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $sto_check_model = new \app\model\StorageCheck();
        $res = $sto_check_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 库位盘点录入完毕后，开始系统盘点过程，自动统计录入库区盘点信息->校正库位库存->校正库区库存
     *
     * @return void
     */
    public function subcheck(){
        $position_id = input('position_id');

        $sto_check_model = new \app\model\StorageCheck();
        $storage_check = $sto_check_model->where('position_id', $position_id)->where('status',1)->find();
        if (!$storage_check) {
            return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_NOTEXIST);
        }
        $res = $sto_check_model ->autoadd($storage_check['id'],$position_id);
        return $this->JsonCommon($res);
    }

    /**
     * 添加盘点详情(包裹，扫码)
     */
    public function addscan(){
        $position_id = input('position_id');
        $position_area_code = input('position_area_code');
        $pack_code = input('pack_code');
        if(empty($position_id)||empty($position_area_code)||empty($pack_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $position_area_model = new \app\model\PositionArea();
        $position_area = $position_area_model->where('position_id',$position_id) ->where('code',$position_area_code)->find();
        if(!$position_area){return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);}

        $scan_model = new \app\model\Scan();
        $pack = $scan_model ->where('code',$pack_code)->find();
        if(!$pack){return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);}

        $scan_check_model = new \app\model\ScanCheck();
        $res = $scan_check_model->add($position_id,$position_area['id'],$pack_code);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_FINISH);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_PAN_EXIST);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

     /**
     * 扫码库位盘点录入完毕后，开始系统盘点过程，更改包裹状态，形成EXCEL数据，再进入EXCEL盘点
     *
     * @return void
     */
    public function subscancheck(){
        $position_id = input('position_id');
        if(empty($position_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $sto_check_model = new \app\model\StorageCheck();
        $storage_check = $sto_check_model->where('position_id', $position_id)->where('status',1)->find();
        if (!$storage_check) {
            return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_NOTEXIST);
        }
        $res = $sto_check_model ->autoscan($storage_check['id'],$position_id);
        return $this->JsonCommon($res);
    }

    /**
     * 扫码盘点列表
     * @return [type] [description]
     */
    public function scanpanlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $storage_check_id = input('storage_check_id');
        $position_area_id = input('position_area_id');
        $position_id = input('position_id');
        $create_time = input('create_time');
        $code = input('code');
        $excel = input('excel')?input('excel'):false;
        if(empty($storage_check_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_check_model = new \app\model\ScanCheck();
        return $scan_check_model->list($current,$pageSize,$storage_check_id,$position_id,$position_area_id,$code,$excel,$create_time);
    }

    /**
     * 删除扫码盘点
     * @return [type] [description]
     */
    public function delpanpack(){
        $storage_check_id = input('storage_check_id');
        $id = input('id');
        if(empty($storage_check_id)||empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_check_model = new \app\model\ScanCheck();
        $res = $scan_check_model ->del($storage_check_id,$id);
        switch($res){
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_NOTEXIST);
                break;
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_STO_CHECK_FINISH);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 删除EXCEL盘点详情
     * @return [type] [description]
     */
    public function delexceldetail(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $sto_check_detail_model = new \app\model\StorageCheckDetail();
        $res = $sto_check_detail_model ->del($id);
        return $this->JsonCommon($res);
    }

    
}