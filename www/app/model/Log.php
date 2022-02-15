<?php
namespace app\model;
use think\Model;

/**
 * 操作日志表格
 */
class Log extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'user_id'        => 'int',
        'content'      => 'sting',
        'create_time' => 'int',
    ];

    //关联用户组
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * 添加操作日志
     * @param  [type]  $uid      [description]
     * @param  [type]  $content      [description]
     * @return [type]                 [description]
     */
    public function add($content,$uid=NULL){
        if(empty($content)){
            return false;
        }

        $Log_model = new Log();
        if($uid==NULL){
            $uid = getUid();
        }
        $Log_model->user_id = $uid;
        $Log_model->content = $content;
        if($Log_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回操作日志列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param string $username
     * @param string $content
     * @param string $oprate_time
     * @return void
     */
    public function list(int $current=1,int $pageSize=10,$username='',$content='',$oprate_time='',$excel=false){
        $map1 = []; //查询用户id条件
        $map = [];  //查询日志条件
        if(!empty($username)){$map1[] = ['username','like','%'.$username.'%'];}
        if(count($map1)>0){
            $user_model = new User();
            $user_id_list = $user_model-> where($map1)->column('id');
            if(count($user_id_list)>0){
                $map[]=['user_id','in',$user_id_list];
            }else{
                $data['total'] = 0;
                $data['success'] = true;
                $data['data']=[];
                return json($data);
            }
        }
        if(!empty($content)){$map[] = ['content','like','%'.$content.'%'];}
        if(!empty($oprate_time)){
            $a = $oprate_time;
            // $a = json_decode($oprate_time,true);
            $between_time = [strtotime($a['begin_time']) ,strtotime($a['end_time'])];
            $map[] = ['create_time','between',$between_time];
        }
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        //用户管理员ID，装换为登录用户名
        $user_model = new User();
        $profile_model = new Profile();
        foreach ($data['data'] as $key => $value) {
            $profile = $profile_model->where('user_id',$value['user_id'])->find();
            if($profile){
                $data['data'][$key]['username']= $profile['nickname'];
            }else{
                $tmp = $user_model-> where('id',$value['user_id'])->find();
                $data['data'][$key]['username']= $tmp?$tmp['username']:'注销用户';
            }
            
        }

        if($excel==true){
            $excel_data = [];
            foreach($data['data'] as $value){
                $excel_data[] = [
                    '操作人' => $value['username'],
                    '操作内容' => $value['content'],
                    '操作时间' => $value['create_time']
                ];
            }
            $data['data']=$excel_data;

        }
        
        return json($data);
    }

    

}