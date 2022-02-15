<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class PositionArea
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加库位
     */
    public function add(){
        $code = input('code');
        $position_id = input('position_id');
        $size = input('size');
        $status=input('status')?2:1;
        $pos_code = input('pos_code');
        if($pos_code){
            $position_model = new \app\model\Position();
            $position = $position_model -> where('pos_code',$pos_code)->find();
            if($position){
                $position_id = $position['id'];
            }else{
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
            }
        }
        if(empty($position_id)||empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pos_area_model = new \app\model\PositionArea();
        $res = $pos_area_model->add($position_id,$code,$size,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 批量添加库位(按code)
     */
    public function addtogather(){
        $pos_code = input('pos_code');
        if(empty($pos_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $position_model = new \app\model\Position();
        $position = $position_model -> where('pos_code',$pos_code)->findOrEmpty();
        if($position->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
        }
        
        // $last_pro_code = input('last_pro_code');
        // if(!empty($last_pro_code)){
        //     $product_model = new \app\model\Product();
        //     $product = $product_model ->where('pro_code',$last_pro_code)->findOrEmpty();
        //     if($product->isEmpty()){
        //         return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
        //     }
        // }
        
        $code = input('code');
        $size = input('size');
        $status=input('status')?2:1;

        if(empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pos_area_model = new \app\model\PositionArea();
        $res = $pos_area_model->add($position['id'],$code,$size,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 修改库位
     */
    public function updata(){
        $id = input('id');
        $code = input('code');
        $position_id = input('position_id');
        $size = input('size');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = null;
        }

        if(empty($id)||empty($position_id)||empty($code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pos_area_model = new \app\model\PositionArea();
        $res = $pos_area_model->updata($id,$position_id,$code,$size,$status);
        switch($res){
            case 2:
                return $this->JsonDataArr(ApiMsg::ERR_SEARCH_POSITION);
                break;
            case 3:
                return $this->JsonDataArr(ApiMsg::ERR_AREA_CODE);
                break;
            case 4:
                return $this->JsonDataArr(ApiMsg::ERR_SAVE);
                break;
            default:
                return $this->JsonSuccess();
        }
    }


    /**
     * 库位列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $code = input('code');
        $position_id = input('position_id');
        $size = input('size');
        $last_pro_code = input('last_pro_code');
        $excel = input('excel')?input('excel'):false;
        $pos_area_model = new \app\model\PositionArea();
        return $pos_area_model->list($current,$pageSize,$position_id,$code,$size,$last_pro_code,$status,$excel);
    }

    /**
     * 扫码入库推荐
     * @return [type] [description]
     */
    public function inlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $position_id = input('position_id');
        $last_pro_code = input('last_pro_code');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $excel = input('excel')?input('excel'):false;
        if(empty($position_id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pos_area_model = new \app\model\PositionArea();
        return $pos_area_model->inlist($current,$pageSize,$position_id,$last_pro_code,$fac_code,$cus_code,$excel);
    }

    /**
     * 扫码出库推荐
     * @return [type] [description]
     */
    public function outlist(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $position_id = input('position_id');
        $pro_code = input('pro_code');
        $fac_code = input('fac_code');
        $cus_code = input('cus_code');
        $excel = input('excel')?input('excel'):false;
        if(empty($position_id)||empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pos_area_model = new \app\model\PositionArea();
        return $pos_area_model->outlist($current,$pageSize,$position_id,$pro_code,$fac_code,$cus_code,$excel);
    }

    /**
     * 删除库位
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if($id==NULL){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pos_area_model = new \app\model\PositionArea();
        $res = $pos_area_model ->del($id);
        return $this->JsonCommon($res);
    }

  


}