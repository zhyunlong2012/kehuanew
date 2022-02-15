<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Billlist
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加随车单信息
     */
    public function add(){
        $number = input('number');
        $car_id = input('car_id');
        $factory_id = input('factory_id');
        $customer_id = input('customer_id');
        $status=1;
        if(empty($number)||empty($car_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $bill_model = new \app\model\Billlist();
        $res = $bill_model->add($number,$car_id,$status,$factory_id,$customer_id);
        return $this->JsonCommon($res);
    }

    /**
     * 添加随车单详情
     */
    public function adddetail(){
        $number = input('number');
        $pro_code = input('pro_code');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $other_code = input('other_code');
        $amount=input('amount');
        if(empty($number)||(empty($other_code)&&empty($pro_code))||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_detail_model = new \app\model\BillDetail();
        $res = $bill_detail_model->add($number,$fac_code,$cus_code,$pro_code,$other_code,$amount);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_STATE);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                break;
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            case 9:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_EXIST);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 修改随车单详情
     */
    public function moddetail(){
        $id = input('id');
        $number = input('number');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $other_code = input('other_code');
        $pro_code = input('pro_code');
        $amount=input('amount');
        if(empty($number)||(empty($other_code)&&empty($pro_code))||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_detail_model = new \app\model\BillDetail();
        $res = $bill_detail_model->updata($id,$number,$fac_code,$cus_code,$pro_code,$other_code,$amount);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_STATE);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                break;
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            case 9:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_NOTEXIST);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    /**
     * 查询随车单详情
     */
    public function detail(){
        $number = input('number');
        $bill_detail_model = new \app\model\BillDetail();
        return $bill_detail_model->list($number);
    }

       /**
     * 查询随车单单项详情
     */
    public function prodetail(){
        $number = input('number');
        $pro_code = input('pro_code');
        $bill_detail_model = new \app\model\BillDetail();
        return $bill_detail_model->list($number,$pro_code);
    }

    /**
     * 修改随车单信息
     */
    public function updata(){
        $id = input('id');
        $number = input('number');
        $car_id = input('car_id');
        $factory_id = input('factory_id');
        $customer_id = input('customer_id');
        $status=input('status');
        if(empty($id)||empty($number)||empty($car_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $bill_model = new \app\model\Billlist();
        $res = $bill_model->updata($id,$number,$car_id,$status,$factory_id,$customer_id);
        return $this->JsonCommon($res);
    }


    /**
     * 随车单列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $number = input('number');
        $car_id = input('car_id');
        $status=input('status');
        $create_time = input('create_time');
        $excel = input('excel')?input('excel'):false;
        $factory_id = input('factory_id');
        $customer_id = input('customer_id');
        $bill_model = new \app\model\Billlist();
        return $bill_model->list($current,$pageSize,$number,$car_id,$status,$create_time,$excel,$factory_id,$customer_id);
    }


    /**
     * 删除随车单
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $res = $bill_model ->del($id);
        return $this->JsonCommon($res);
    }

     /**
     * 作废扫码
     */
    public function invalid(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $res = $bill_model ->invalid($id);
        return $this->JsonCommon($res);
            
    }

    /**
     * 校验随车单
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function checkbill(){
        $number = trim(input('number'));
        $code = trim(input('code'));
        if(empty($number)||empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $bill = $bill_model->where('number',$number)->where('status',1) ->findOrEmpty();
        if($bill->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
        }
        $res = $bill_model ->checkbill($number,$code);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_AMOUNT);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL_STATE);
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL);
            case 1:
                $scan_log_model = new \app\model\ScanLog();
                $scan_log_model->add($code,'随车校验通过,随车单号:'.$number);
                $bill_res = $bill_model ->checkpack($number);
                if($bill_res){
                    return $this->JsonDataArr(ApiMsg::SUCCESS_BILL_CHECK);
                }else{
                    return $this->JsonSuccess();
                }
                
        }
        
    }

     /**
     * 随车校验包裹是否在随车单
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function checkinbill(){
        $number = trim(input('number'));
        $code = trim(input('code'));
        if(empty($number)||empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $bill = $bill_model->where('number',$number)->where('status',1) ->findOrEmpty();
        if($bill->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
        }
        $res = $bill_model ->checkinbill($number,$code);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_AMOUNT);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL_STATE);
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL);
            case 1:
                return $this->JsonSuccess();
                
        }
        
    }

    /**
     * 出厂校验随车单
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function doorcheck(){
        $number = input('number');
        $code = input('code');
        if(empty($number)||empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $bill = $bill_model->where('number',$number)->where('status',2) ->findOrEmpty();
        if($bill->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
        }
        $res = $bill_model ->doorcheckbill($number,$code);
        switch($res){
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL_STATE_NOT_INCAR);
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_AMOUNT);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
            case 1:
                $scan_log_model = new \app\model\ScanLog();
                $scan_log_model->add($code,'出厂校验通过,随车单号:'.$number);
                $bill_res = $bill_model ->doorcheck($number);
                if($bill_res){
                    return $this->JsonDataArr(ApiMsg::SUCCESS_BILL_CHECK);
                }else{
                    return $this->JsonSuccess();
                }
                
        }
        
    }

    /**
     * 删除随车单产品
     * @return [type] [description]
     */
    public function deldetail(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_detail_model = new \app\model\BillDetail();
        $res = $bill_detail_model ->del($id);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_STATE);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_NOTEXIST);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
            case 1:
                return $this->JsonSuccess();
                
        }
    }

     /**
     * 撤回已扫包裹
     *
     * @return void
     */
    public function scanback(){
        $number = input('number');
        $step = input('step');
        if(empty($number)||empty($step)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $res = $bill_model->billrollback($number,$step);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_BACK_CHECKED);
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SCAN_PRO_BACK);
            case 1:
                return $this->JsonSuccess();
                
        }

    }

    /**
     * 校验产品，在随车单则出库
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function checkproinbill(){
        $number = trim(input('number'));
        $pro_code = trim(input('pro_code'));
        $fac_code = trim(input('fac_code'));
        $cus_code = trim(input('cus_code'));
        $position_id = trim(input('position_id'));
        $position_area_code = trim(input('position_area_code'));
        $amount = trim(input('amount'));
        $oprate_time  = input('oprate_time')?input('oprate_time'):date("Y-m-d H:i:s");
        if(empty($number)||empty($pro_code)||empty($fac_code)||empty($cus_code)||empty($position_id)||empty($position_area_code)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\Billlist();
        $bill = $bill_model->where('number',$number)->where('status',1) ->findOrEmpty();
        if($bill->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
        }
        $res = $bill_model->checkproinbill($number,$pro_code,$fac_code,$cus_code,$position_id,$position_area_code,$amount,$oprate_time);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_CHECK_BILL_PRO_AMOUNT);
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_NOTEXIST);
            case 9:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
            case 1:
                $bill_res = $bill_model ->checkpack($number);
                if($bill_res){
                    return $this->JsonDataArr(ApiMsg::SUCCESS_BILL_CHECK);
                }else{
                    return $this->JsonSuccess();
                }
                
        }
        
    }
    

}