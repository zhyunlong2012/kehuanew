<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
use think\facade\Db;
class User
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    public function add(){
        $username = input('username');
        $password = input('password');
        $tel = input('tel');
        $user_group_id = input('user_group_id');
        $truename = input('truename');
        $access = input('access')?input('access'):'[]';
        if(empty($username)||empty($password)||empty($truename)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $user_model = new \app\model\User;
        $res = $user_model->add($username,$password,$tel,$user_group_id,$truename,$access);
        return $this->JsonCommon($res);
    }
    /**
     * 修改
     */
    public function updata(){
        $id = input('id');
        $username = input('username');
        $tel = input('tel');
        $password  = input('password');
        $user_group_id  = input('user_group_id');
        $truename = input('truename');
        $access = input('access')?input('access'):'[]';
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($truename)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $user_model = new \app\model\User();
        $res = $user_model->updata($id,$username,$tel,$user_group_id,$status,$password,$truename,$access);
        return $this->JsonCommon($res);
    }

    public function profile(){
        $uid= getUid();
        $user = Db::table('profile')->where('user_id', $uid)->find();
        if(!$user){
            return $this->JsonDataArr(ApiMsg::ERR_UID);
        }else{
            $data = [
             'name'=> $user['nickname'],
             'avatar'=> 'https://gw.alipayobjects.com/zos/antfincdn/XAosXuNZyF/BiazfanxmamNRoxxVxka.png',
             "country"=> "China",
             "geographic"=>[
                "province" => [
                    "label"=>"浙江省", "key"=>"330000"
                ],
                "city"=> ["label"=> "杭州市", "key"=> "330100"]
            ],
             "userid"=>"00000001"
            ];
            return $this->JsonSuccess($data);
        }
    }

    // /**
    //  * 获取用户资料
    //  *
    //  * @return void
    //  */
    // public function info(){
    //     $uid= getUid();
    //     $user = Db::table('user')->where('id', $uid)->withoutField('password')->find();
    //     if(!$user){
    //         return $this->JsonDataArr(ApiMsg::ERR_UID);
    //     }else{
    //         $group_model = new \app\model\UserGroup();
    //         $group = $group_model ->where('id',$user['user_group_id'])->find();
    //         $group_name = $group?$group['groupname']:'未分组';
    //         $data = [
    //             'id' =>$user['id'],
    //             'name'=> $user['username'],
    //             // 'avatar'=> 'https://gw.alipayobjects.com/zos/antfincdn/XAosXuNZyF/BiazfanxmamNRoxxVxka.png',
    //             'group_name' => $group_name
    //         ];
    //         return $this->JsonSuccess($data);
    //     }
    // }

     /**
     * 获取用户资料
     *
     * @return void
     */
    public function info(){
        $uid= getUid();
        $user_model = new \app\model\User();
        $res = $user_model ->info($uid);
        return $this->JsonSuccess($res);
    }

    /**
     * 个人资料
     *
     * @return void
     */
    public function myAccount(){
        $uid= getUid();
        $user = Db::table('user')->where('id', $uid)->withoutField('password')->find();
        if(!$user){
            return $this->JsonDataArr(ApiMsg::ERR_UID);
        }else{
            return $this->JsonSuccess($user);
        }
    }

    /**
     * 个人修改自己密码
     *
     * @return void
     */
    public function upAccount(){
        $id= getUid();
        $username = input('username');
        $tel = input('tel');
        $password  = input('password');
        if(empty($id)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $user_model = new \app\model\User();
        $res = $user_model->updata($id,$username,$tel,'',3,$password);
        return $this->JsonCommon($res);
    }

    /**
     * 用户列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $username=input('username');
        $tel=input('tel');

        $user_model = new \app\model\User;
        return $user_model->list($current,$pageSize,$status,$username,$tel);
    }

    /**
     * 删除用户
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if(($id==NULL)||(in_array(1, $id))){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $user_model = new \app\model\User;
        $res =  $user_model ->deluser($id);
        return $this->JsonCommon($res);
    }

}