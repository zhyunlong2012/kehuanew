<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
/**
 * 条码、二维码码库
 */
class Qrcode
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加
     */
    public function add(){
        $factory_code = input('factory_code');
        $qrcode = input('qrcode');
        $code = input('code');
        if(empty($qrcode)||empty($factory_code)||empty($factory_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $qrcode_model = new \app\model\Qrcode();
        $res = $qrcode_model->add($factory_code,$qrcode,$code);
        return $this->JsonCommon($res);
    }

    /**
     * 修改
     */
    public function updata(){
        $id = input('id');
        $factory_code = input('factory_code');
        $qrcode = input('qrcode');
        $code = input('code');
        
        if(empty($id)||empty($qrcode)||empty($factory_code)||empty($factory_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $qrcode_model = new \app\model\Qrcode();
        $res = $qrcode_model->updata($id,$factory_code,$qrcode,$code);
        return $this->JsonCommon($res);
    }


    /**
     * 列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $factory_code=input('factory_code');
        $qrcode=input('qrcode');
        $code=input('code');
        $excel = input('excel')?input('excel'):false;
        $qrcode_model = new \app\model\Qrcode();
        return $qrcode_model->list($current,$pageSize,$factory_code,$qrcode,$code,$excel);
    }

    /**
     * 删除
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $qrcode_model = new \app\model\Qrcode();
        $res = $qrcode_model ->del($id);
        return $this->JsonCommon($res);
    }

    /**
     * 解码
     *
     * @return void
     */
    public function decrypt(){
        $qrcode = input('qrcode');
        if(empty($qrcode)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $qrcode_model = new \app\model\Qrcode();
        $res = $qrcode_model ->where('qrcode',$qrcode) ->findOrEmpty();
        if($res->isEmpty()){
            return $this->JsonErr($qrcode);
        }else{
            return $this->JsonSuccess($res['code']);
        }
    }

}