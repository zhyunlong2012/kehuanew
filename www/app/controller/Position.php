<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Position
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加库区
     */
    public function add(){
        $pos_name = input('pos_name');
        $pos_code = input('pos_code');
        $area = input('area');
        $size = input('size');
        if(input('employs')===NULL){
            $employs = [];
        }else{
            $employs = input('employs');
        }
        $employs = json_encode($employs,256);
        $status=input('status')?2:1;

        if(empty($pos_name)||empty($pos_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pos_model = new \app\model\Position();
        $res = $pos_model->add($pos_name,$pos_code,$area,$size,$employs,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 修改库区
     */
    public function updata(){
        $id = input('id');
        $pos_name = input('pos_name');
        $pos_code = input('pos_code');
        $area = input('area');
        $size = input('size');
        $employs = json_encode(input('employs'),256);

        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($pos_name)||empty($pos_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pos_model = new \app\model\Position();
        $res = $pos_model->updata($id,$pos_name,$pos_code,$area,$size,$employs,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 库区列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $pos_name=input('pos_name');
        $pos_code=input('pos_code');
        $excel = input('excel')?input('excel'):false;
        $tree = input('tree')?true:false;
        $pos_model = new \app\model\Position();
        return $pos_model->list($current,$pageSize,$status,$pos_name,$pos_code,$excel,$tree);
    }


    /**
     * 删除库区
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pos_model = new \app\model\Position();
        $res = $pos_model ->del($id);
        return $this->JsonCommon($res);
    }


     /**
     * 库区信息
     *
     * @return void
     */
    public function posinfo(){
        $id = input('id');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pos_model = new \app\model\Position();
        $list = $pos_model->where('id',$id)->find();
        return $this->JsonCommon(true,$list);
    }


}