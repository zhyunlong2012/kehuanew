<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class UserGroup
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    public function add(){
        $groupname = input('groupname');
        $status=input('status')?2:1;

        if(empty($groupname)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $user_group_model = new \app\model\UserGroup;
        $res = $user_group_model->add($groupname,$status);
        return $this->JsonCommon($res);
    }

    /**
     * 修改
     */
    public function updata(){
        $id = input('id');
        $groupname = input('groupname');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }
        if(empty($id)||empty($groupname)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $user_group_model = new \app\model\UserGroup();
        $res = $user_group_model->updata($id,$groupname,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 用户组列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $groupname=input('groupname');

        $user_group_model = new \app\model\UserGroup;
        return $user_group_model->list($current,$pageSize,$status,$groupname);
    }

    /**
     * 删除用户组
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(($id==NULL)||(in_array(1, $id))){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $user_group_model = new \app\model\UserGroup;
        $res = $user_group_model ->delgroup($id);
        return $this->JsonCommon($res);
    }

}