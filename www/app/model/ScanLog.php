<?php
namespace app\model;
use think\Model;

/**
 * 扫码打包操作记录表格
 */
class ScanLog extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'code'  => 'string',
        'user_id'      => 'int',
        'desc'      => 'string',
        'create_time' => 'int',
        'update_time' =>'int'
    ];


    /**
     * 添加记录
     *
     * @param [type] $code
     * @param [type] $desc
     * @return void
     */
    public function add($code,$desc){
        $scan_log_model = new ScanLog();
        $scan_log_model->code = $code;
        $scan_log_model->desc = $desc;
        $scan_log_model->user_id = getUid();
        if($scan_log_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回扫码操作列表
     *
     * @param string $code
     * @return void
     */
    public function list($code,$desc=''){
        $map = [];
        if(!empty($code)){$map[]=['code','like',$code];}
        if(!empty($desc)){$map[]=['desc','like','%'.$desc.'%'];}
        $data = $this->where($map)->order('id','asc')->select();
        $profile_model = new Profile();
        $user_model = new User();
        foreach ($data as $key => $value) {
            $profile = $profile_model->where('user_id',$value['user_id'])->find();
            if($profile){
                $data[$key]['username']= $profile['nickname'];
            }else{
                $user_tmp = $user_model-> where('id',$value['user_id'])->find();
                $data[$key]['username'] =$user_tmp?$user_tmp['username']:'注销用户';
            }
            
        }

        return json($data);
    }

    


}