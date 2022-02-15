<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Profile
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加档案
     */
    public function add(){
        $user_id = input('user_id');
        $nickname = input('nickname');
        $sex = input('sex');
        $thumb_url = input('thumb_url');
        $email = input('email');
        $card = input('card');
        $pro_city_area = input('pro_city_area');
        if($pro_city_area){
            $province = $pro_city_area[0];
            $city = $pro_city_area[1];
            $area = $pro_city_area[2];
        }else{
            $province = $city = $area = NULL;
        }
        $address = input('address');

        if(empty($user_id)||empty($nickname)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $profile_model = new \app\model\Profile();
        $res = $profile_model->add($user_id,$nickname,$sex,$email,$card,$thumb_url,$province,$city,$area,$address);
        return $this->JsonCommon($res);
    }

    /**
     * 修改档案
     */
    public function updata(){
        $id = input('id');
        $user_id = input('user_id');
        $nickname = input('nickname');
        $sex = input('sex');
        $thumb_url = input('thumb_url');
        $email = input('email');
        $card = input('card');
        $pro_city_area = input('pro_city_area');
        if($pro_city_area){
            $province = $pro_city_area[0];
            $city = $pro_city_area[1];
            $area = $pro_city_area[2];
        }else{
            $province = $city = $area = NULL;
        }
        $address = input('address');

        if(empty($id)||empty($user_id)||empty($nickname)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $profile_model = new \app\model\Profile();
        $res = $profile_model->updata($id,$user_id,$nickname,$sex,$email,$card,$thumb_url,$province,$city,$area,$address);
        return $this->JsonCommon($res);
    }

    /**
     * 档案信息
     *
     * @return void
     */
    public function info(){
        $user_id = input('user_id');
        $profile_model = new \app\model\Profile();
        $profile = $profile_model ->where('user_id',$user_id)->find();
        return $this->JsonCommon($profile);
    }



}