<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class Scan
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 扫码打包
     */
    public function add(){
        $pro_code = input('pro_code');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $amount = input('amount');
        $desc = input('desc');
        $code =input('code')?input('code'):time().rand(10,99);
        $status = input('status')?intval(input('status')):1;
        // $code = time().rand(10,99);
        $scan_desc = '扫码打包,产品'.$pro_code.'数量:'.$amount.','.$fac_code.','.$cus_code.'备注:'.$desc;
        
        if(empty($pro_code)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $scan_model = new \app\model\Scan();
        if(input('code')){
            $scan = $scan_model->where('code',$code)->find();
            if($scan){
                return  $this->JsonDataArr(ApiMsg::ERR_SCAN_CODE);
            }
        }
        $customer='';
        $cus_model = new \app\model\Customer();
        if($cus_code){
            $customer = $cus_model->where('cus_code',$cus_code)->find();
            if(!$customer){
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
            }
        }
        $product_model = new \app\model\Product();
        $product = $product_model->where('pro_code',$pro_code)->find();
        if($product){
            $weight = $amount*$product['weight'];
        }else{
            $weight = 0;
        }
        

        $scan_log_model = new \app\model\ScanLog();
        
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $scan_model->add($pro_code,$fac_code,$cus_code,$amount,$code,$weight,$desc,$status);
            $res2 = $scan_log_model->add($code,$scan_desc);
            $proscan_model = new \app\model\ProScan();
            $res3 = $proscan_model->proscaned($code);
            if($res1&&$res2&&$res3){
                // 提交事务
                Db::commit();
                $uid = getUid();
                $profile_model = new \app\model\Profile();
                $profile= $profile_model ->where('user_id',$uid)->find();
                $user_model = new \app\model\User();
                $user = $user_model->where('id',$uid)->find();
                $nickname= $profile?$profile['nickname']:$user['username'];
                $srcode = 'one6666,'.$code.','.$fac_code.','.$pro_code.','.$amount.','.$cus_code;
                $data = [
                    $pro_code,
                    '数量:'.$amount,
                    $nickname,      //'操作人'
                    '单号:'.$code,
                    $srcode,             //二维码
                    $customer?$customer['cus_name']:'未输入客户',
                    $fac_code
                ];
                return $this->JsonSuccess($data);
            }else{
                Db::rollback();
                return $this->JsonErr();
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->JsonErr();
        }
    }

    /**
     * 拆包（修改）                             
     */
    public function updata(){
        $code = input('code');
        $pro_code = input('pro_code');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $cus_name = input('cus_name');
        $amount = input('amount');
        if(empty($code)||empty($amount)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $scan_model = new \app\model\Scan();
        $scan = $scan_model ->where('code',$code)->find();
        if(!$scan){
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_CODE);
        }
        if($scan['status']==2){
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_UNPACK);
        }

        $cus_model = new \app\model\Customer();
        $customer = $cus_model->where('cus_code',$cus_code)->find();
        if(!$customer){
            return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
        }

        $product_model = new \app\model\Product();
        $product = $product_model->where('pro_code',$pro_code)->find();
        if($product){
            $weight = $amount*$product['weight'];
        }else{
            $weight = 0;
        }

        $res = $scan_model->updata($pro_code,$fac_code,$cus_code,$amount,$code,$weight);
        if($res){
            $uid = getUid();
            $profile_model = new \app\model\Profile();
            $profile= $profile_model ->where('user_id',$uid)->find();
            $nickname= $profile?$profile['nickname']:'id:'.$uid;
            $srcode = 'one6666,'.$code.','.$fac_code.','.$pro_code.','.$amount.','.$cus_code;
            $data = [
                $pro_code,
                '数量:'.$amount,
                $nickname,      //'操作人'
                '单号:'.$code,
                // $code,             //二维码
                $srcode ,//二维码
                $customer['cus_name'],
                $fac_code
            ];
            
            return $this->JsonSuccess($data);
        }else{
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_UNPACK);
        }
    }

    public function printtag(){
        $code = input('code');
        if(empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_model = new \app\model\Scan();
        $scan = $scan_model ->where('code',$code)->find();
        if(!$scan){
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_CODE);
        }
        $profile_model = new \app\model\Profile();
        $profile= $profile_model ->where('user_id',$scan['user_id'])->find();
        $nickname= $profile?$profile['nickname']:'id:'.$scan['user_id'];
        $cus_name = '未输入客户';
        $cus_code = '';
        if($scan['cus_code']){
            $cus_model = new \app\model\Customer();
            $customer = $cus_model->where('cus_code',$scan['cus_code'])->find();
            $cus_name = $customer?$customer['cus_name']:'未输入客户';
            $cus_code = $customer?$customer['cus_code']:'';
        }
        $srcode = 'one6666,'.$code.','.$scan['fac_code'].','.$scan['pro_code'].','.$scan['amount'].','.$cus_code;
        $data = [
            $scan['pro_code'],
            '数量:'.$scan['amount'],
            $nickname,      //'操作人'
            '单号:'.$code,
            $srcode,             //二维码
            $cus_name,
            $scan['fac_code']
        ];
        return $this->JsonSuccess($data);
    }


    /**
     * 扫码列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $code=input('code');
        $pro_code=input('pro_code');
        $fac_code=input('fac_code');
        $cus_code=input('cus_code');
        input('position_area_id')?$position_area_id=input('position_area_id'):$position_area_id=input('pos_area_code');
        $status=input('status')?input('status'):0;
        $excel = input('excel')?input('excel'):false;
        $number = input('number');
        $position_id = input('position_id');
        $create_time = input('create_time');
        $other_code = input('other_code');
        $desc = input('scandesc');
        $exact = input('exact')?input('exact'):false;
        $scan_model = new \app\model\Scan();
        return $scan_model->list($current,$pageSize,$code,$pro_code,$fac_code,$cus_code,$position_id,$position_area_id,$status,$number,$create_time,$excel,$other_code,$desc,$exact);
    }

    /**
     * 扫码操作记录
     * @return [type] [description]
     */
    public function log(){
        $code=input('code');
        $desc=input('desc');
        if(empty($code)&&empty($desc)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_log_model = new \app\model\ScanLog();
        return $scan_log_model->list($code,$desc);
    }

    /**
     * 作废扫码
     */
    public function invalid(){
        $code = input('code');
        
        if(empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $desc = '作废包裹,包裹号:'.$code;
        $scan_model = new \app\model\Scan();
        $scan_log_model = new \app\model\ScanLog();
        // 启动事务
        Db::startTrans();
        try {
            $res1 = $scan_model->invalid($code);
            $res2 = $scan_log_model->add($code,$desc);
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                return $this->JsonSuccess();
            }else{
                Db::rollback();
                return $this->JsonErr();
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return $this->JsonErr();
        }

    }

    /**
     * 扫码详细信息
     *
     * @return void
     */
    public function info(){
        $code = input('code');
        if(empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_model = new \app\model\Scan();
        $scan = $scan_model ->where('code',$code) ->find();
        if($scan){
            $customer_model = new \app\model\Customer();
            $customer = $customer_model ->where('cus_code',$scan['cus_code'])->find();
            $scan['cus_name'] = $customer?$customer['cus_name']:'注销客户';
            return $this->JsonSuccess($scan);
        }else{
            return $this->JsonErr();
        }
    }

    /**
     * 扫包裹入出库定位
     *
     * @return void
     */
    public function packin(){
        $code = input('code');
        $pos_area_code = input('pos_area_code');
        $position_id = input('position_id');
        $desc = input('desc');
        $oprate_time = time();
        $source = 3;
        $method = input('method');
        $cus_code = input('cus_code')?input('cus_code'):'';
        
        //增加出库数量 如果存在则先拆包再走单个出库
        $amount = input('amount');

        if(( empty($code)||empty($method) ) || ( empty($pos_area_code)&&($method == 'in') ) ) {
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $scan_model = new \app\model\Scan();
        $scan = $scan_model->where('code',$code)->find();
        if(!$scan){return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);}
        $position_area_model = new \app\model\PositionArea();
        if($method=='out'){
            if($scan['status']=='3'){
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_OUT_ALREADY);
            }
            $area = $position_area_model->where('id',$scan['position_area_id'])->find();
            if($area){
                $position_area_id = $area['id'];
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
            }
        }else{
            
            $area = $position_area_model->where('position_id',$position_id)->where('code',$pos_area_code)->find();
            if($area){
                $position_area_id = $area['id'];
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
            }
        }

        
        if(($amount>0)&&($method=='out')){
                // $scan_model = new \app\model\Scan();
                // $scan = $scan_model->where('code',$code)->find();
                // if(!$scan){
                //     return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);
                // }else{
                    $new_amount = $scan['amount']-$amount;
                    if($new_amount!=0){
                        if($new_amount<0){
                            return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_AMOUNT);
                        }
                        if($new_amount>0){
                            $product_model = new \app\model\Product();
                            $product = $product_model->where('pro_code',$scan['pro_code'])->find();
                            if($product){
                                $weight = $new_amount*$product['weight'];
                            }else{
                                $weight = 0;
                            }
                            // 启动事务
                           Db::startTrans();
                           try {
                                $new_code =time().rand(10,99);
                                // $code = time().rand(10,99);
                                $scan_desc = '扫码拆包,产品'.$scan['pro_code'].'本包数量:'.$amount.'原包:'.$code;
                                $new_weight = $amount*$product['weight'];
                                //增加拆包后，拆出去的部分打新包
                                $res4 = $scan_model->add($scan['pro_code'],$scan['fac_code'],$cus_code,$amount,$new_code,$new_weight,'拆包',3);
                                $scan_log_model = new \app\model\ScanLog();
                                $res3 = $scan_log_model->add($new_code,$scan_desc);

                                $res2 = $scan_model->updata(null,null,null,$new_amount,$code,$weight);
                                $res1 = $scan_model->packoutpart($code,$oprate_time,$amount,$cus_code);

                                
                                switch($res1){
                                    case 2:
                                        return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
                                        break;
                                    case 3:
                                        return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
                                        break;
                                    case 4:
                                        return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);
                                        break;
                                    case 5:
                                        return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                                        break;
                                    case 6:
                                        return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                                        break;
                                    case 7:
                                        return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                                        break;
                                    case 8:
                                        return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_IN);
                                        break;
                                    case 9:
                                        return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_OUT);
                                        break;
                                    case 10:
                                        return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                                        break;
                                    default:
                                        $res1=1;
                                }
                                if($res1&&$res2&&$res3&&$res4){
                                // 提交事务
                                    Db::commit();
                                    return $this->JsonSuccess();
                                }else{
                                    Db::rollback();
                                    return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                                }
                            } catch (\Exception $e) {
                                // 回滚事务
                                Db::rollback();
                                return $this->JsonSuccess();
                            }
                        }
                    // }
                }
                
            
        }

        // $scan_model = new \app\model\Scan();
        $res = $scan_model->packin($code,$position_area_id,$desc,$oprate_time,$source,$method,$cus_code,$position_id);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_AREA);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                break;
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_IN);
                break;
            case 9:
                return $this->JsonDataArr(ApiMsg::ERR_SCAN_PACKAGE_OUT);
                break;
            case 10:
                return $this->JsonDataArr(ApiMsg::ERR_CUSTOMER_NOTEXIST);
                break;
            default:
                return $this->JsonSuccess();
        }
    }

    //增加记录每个产品是否扫码
    public function addProScan(){
        $qrcode = input('qrcode');
        $pro_code = input('pro_code');
        if(empty($qrcode)||empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $proscan_model = new \app\model\ProScan();
        $res = $proscan_model->add($qrcode,$pro_code);
        if($res){
            return $this->JsonSuccess();
        }else{
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_PRO);
        }
    }

    
    /**
     * 撤回已扫产品
     *
     * @return void
     */
    public function scanback(){
        $proscan_model = new \app\model\ProScan();
        $res = $proscan_model->proscanrollback();
        if($res){
            return $this->JsonSuccess();
        }else{
            return $this->JsonDataArr(ApiMsg::ERR_SCAN_PRO_BACK);
        }
    }

    /**
     * 产品扫码记录列表
     * @return [type] [description]
     */
    public function proscanlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):1;
        $qrcode = input('qrcode');
        $code = input('code');
        $excel = input('excel')?input('excel'):false;
        $proscan_model = new \app\model\ProScan();
        return $proscan_model->list($current,$pageSize,$qrcode,$code,$status,$excel);
    }

    /**
     * 已扫码未打包数据(pda重新刷新页面时数据初始化)
     *
     * @return void
     */
    public function unpackpros(){
        $proscan_model = new \app\model\ProScan();
        return $proscan_model->unpackpro();
    }

    /**
     * 扫码出库未入库产品数量
     *
     * @return void
     */
    public function outnotcar(){
        $pro_code = input('pro_code');
        if(empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $scan_model = new \app\model\Scan();
        $res = $scan_model->scanOutAmount($pro_code);
        return $this->JsonSuccess($res);
    }

    /**
     * 库区内移动库位
     *
     * @return void
     */
    public function transarea(){
        $packcode = input('code');
        $new_area_code = input('position_area_code');
        $position_area_model =  new \app\model\PositionArea();
        $area = $position_area_model->where('code',$new_area_code)->find();
        if(!$area){ return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_MOVE_NEW_AREA);}

        $scan_model = new \app\model\Scan();
        $res = $scan_model->transArea($packcode,$area['id']);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_STATE_MOVE);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_MOVE_OLD_AREA);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_PACKAGE_MOVE_NEW_AREA);
                break;
            case 5:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
                break;
            case 6:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            case 7:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_FACTORY);
                break;
            case 8:
                return $this->JsonDataArr(ApiMsg::ERR_AREA_AMOUNT);
                break;
            default:
                return $this->JsonSuccess();
        }
    }



}