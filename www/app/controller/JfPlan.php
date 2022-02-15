<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class JfPlan
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加信息
     */
    public function add(){
        $title = input('title');
        $url = input('url');
        if(empty($title)||empty($url)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $jf_plan_model = new \app\model\JfPlan();
        $res = $jf_plan_model->add($title,$url);
        return $this->JsonCommon($res);
    }

    /**
     * 修改信息
     */
    public function updata(){
        $id = input('id');
        $title = input('title');
        $url = input('url');
        if(empty($id)||empty($title)||empty($url)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        
        $jf_plan_model = new \app\model\JfPlan();
        $res = $jf_plan_model->updata($id,$title,$url);
        return $this->JsonCommon($res);
    }


    /**
     * 车型列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $title = input('title');
        $url = input('url');
        $excel = input('excel')?input('excel'):false;
        $jf_plan_model = new \app\model\JfPlan();
        return $jf_plan_model->list($current,$pageSize,$title,$url,$excel);
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
        $jf_plan_model = new \app\model\JfPlan();
        $res = $jf_plan_model ->del($id);
        return $this->JsonCommon($res);
    }

     /**
     * 查询当前最新
     */
    public function lastplan(){
        $jfcar_detail_model = new \app\model\JfPlan();
        $res = $jfcar_detail_model->order('id','desc')->limit(1)->select();
        if(count($res)>0){
            return $this->JsonSuccess($res[0]);
        }else{
            return $this->JsonSuccess();
        }
        // dump($res);
        
    }

}