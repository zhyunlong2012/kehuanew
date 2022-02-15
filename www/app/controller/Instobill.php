<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Instobill
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 将随车单内容添加到入库随车单表
     */
    public function add(){
        $number = input('number');  //随车单号
        if(empty($number)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_detail_model = new \app\model\BillDetail();
        $bill_detail = $bill_detail_model->where('number',$number)->select();
        if(count($bill_detail)<=0){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_NOTEXIST);
        }else{
            $data = [];
            foreach($bill_detail as $value){
                $data[] = [
                    'number'      => $number,
                    'pro_code'    => $value['pro_code'], 
                    'fac_code'   =>$value['fac_code'],
                    'cus_code'   => $value['cus_code'],
                    'other_code'   => $value['other_code'],
                    'amount'      => $value['amount'],
                    'amount_checked' =>0
                ];
            }
        }

        $bill_detail_in_model = new \app\model\BillDetailIn();
        $res=$bill_detail_in_model->where('number',$number)->delete();
        $res = $bill_detail_in_model->saveAll($data);
        return $this->JsonCommon($res);
    }


    /**
     * 查询随车单单项详情
     */
    public function prodetail(){
        $number = input('number');
        $pro_code = input('pro_code');
        $bill_detail_in_model = new \app\model\BillDetailIn();
        return $bill_detail_in_model->list($number,$pro_code);
    }


    /**
     * 随车单入库(根据包裹)
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function checkbillpack(){
        $number = trim(input('number'));
        $code = trim(input('code'));
        $position_id = trim(input('position_id'));
        $pos_area_code = input('pos_area_code');
        $desc = '随车单'.$number.input('desc');
        $oprate_time = time();
        $cus_code = input('cus_code')?input('cus_code'):'';
        
        if(empty($number)||empty($code)||empty($position_id)||empty($pos_area_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $position_area_model = new \app\model\PositionArea();
        $area = $position_area_model->where('position_id',$position_id)->where('code',$pos_area_code)->find();
        if($area){
            $position_area_id = $area['id'];
        }else{
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
        }
        // 启动事务
        Db::startTrans();
        try {
            $res =1;
            $bill_model = new \app\model\BillDetailIn();
            $res1 = $bill_model ->checkbillinsto($number,$code);
            if($res1!=1){
                $res = $res1;
            }else{
                $scan_model = new \app\model\Scan();
                $res2 =$scan_model->  packin($code,$position_area_id,$desc,$oprate_time,3,'in',$cus_code,$position_id);
                switch($res2){
                    case 2:
                        $res = 7; break;
                    case 3:
                        $res = 8; break;
                    case 4:
                        $res = 5; break;
                    case 5:
                        $res = 9; break;
                    case 6:
                        $res = 3; break;
                    case 7:
                        $res = 10; break;
                    case 8:
                        $res = 4; break;
                    case 9:
                        $res = 11; break;
                    case 10:
                        $res = 12; break;
                    default:
                        $res = 1;
                }
            }
            
            switch($res){
                case 2:
                    return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_AMOUNT);
                    break;
                case 3:
                    return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                    break;
                case 4:
                    return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_IN);
                    break;
                case 5:
                    return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);
                    break;
                case 6:
                    return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_BILL);
                    break;
                case 7:
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
                    break;
                case 8:
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
                    break;
                case 9:
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                    break;
                case 10:
                    return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                    break;
                case 11:
                    return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_OUT);
                    break;
                case 12:
                    return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                case 1:
                     // 提交事务
                    Db::commit();
                    // $scan_log_model = new \app\model\ScanLog();
                    // $scan_log_model->add($code,'随车单入库,随车单:'.$number);
                    $bill_res = $bill_model ->checkpass($number);
                    if($bill_res){
                        return $this->JsonDataArr(ApiMsg::SUCCESS_BILL_CHECK);
                    }else{
                        return $this->JsonSuccess();
                    } 
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return 6;
        }
        
    }

     /**
     * 随车单重置，已入库包裹不能出库
     *
     * @return void
     */
    public function scanback(){
        $number = input('number');
        $step = input('step');
        if(empty($number)||empty($step)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\BillDetailIn();
        $res = $bill_model->detailrollback($number,$step);
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
     * 校验产品，在随车单则入库
     *
     * @param [type] $number
     * @param [type] $code
     * @return void
     */
    public function billproinsto(){
        $number = trim(input('number'));
        $pro_code = trim(input('pro_code'));
        $fac_code = trim(input('fac_code'));
        $cus_code = trim(input('cus_code'));
        $position_id = trim(input('position_id'));
        $position_area_code = trim(input('position_area_code'));
        $amount = trim(input('amount'));
        $oprate_time  = input('oprate_time')?input('oprate_time'):date("Y-m-d H:i:s");
        if(empty($number)||empty($pro_code)||empty($position_id)||empty($position_area_code)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $bill_model = new \app\model\BillDetailIn();
        $bill_detail = $bill_model->where('number',$number)->select();
        if(count($bill_detail)<=0){
            return $this->JsonDataArr(ApiMsg::ERR_BILL_NOTEXIST);
        }
        $res = $bill_model->billproinsto($number,$pro_code,$fac_code,$cus_code,$position_id,$position_area_code,$amount,$oprate_time);
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
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_AMOUNT);
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_BILL_DETAIL_NOTEXIST);
            case 9:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
            case 1:
                $bill_res = $bill_model ->checkpass($number);
                if($bill_res){
                    return $this->JsonDataArr(ApiMsg::SUCCESS_BILL_CHECK);
                }else{
                    return $this->JsonSuccess();
                }
                
        }
        
    }
    

}