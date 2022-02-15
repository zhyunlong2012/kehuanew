<?php
namespace app\model;
use think\Model;

/**
 * 菜单表格
 */
class Menu extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'pid'         => 'int',
        'name'        => 'string',
        'text'        => 'string',
        'path'        => 'string',
        'icon'        => 'string',
        'hideInMenu'  => 'int',  //1显示 2隐藏
        'authority'   =>'string',
        'order' => 'int'
    ];


    
    /**
     * 返回菜单列表
     * [ {
     *   "path": "/dashboard",
     *   "name": "dashboard",
     *   "icon": "dashboard",
     *   "authority": ['admin'],
     *   "children": [
     *       {
     *       "path": "/dashboard/dashboardanalysis",
     *       "name": "analysis",
     *       "authority": ['admin'],
     *       }]
     *   }]
     * @return void
     */
    public function list(){
        $data = $this->where('hideInMenu',0)->order('order','desc')->select()->toArray();
        foreach($data as $key => $value){
            $data[$key]['authority'] = explode(',',$value['authority']);
            $data[$key]['hideInBreadcrumb'] = true;
        }
        $res = recursive($data,0);
        return json($res);
    }

    /**
     * 返回chidren
     *
     * @param array $array
     * @param integer $pid
     * @return void
     */
    // function recursive(array $array, int $pid = 0)
    // {
    //     $result = [];
    //     foreach ($array as $key => $value) {
    //         if ($value['pid'] == $pid) { // 如果找到传过来的父级ID
    //             $value['children'] = $this->recursive($array, $value['id']); // 把该条目的ID做为父类，寻找其下级
    //             if (!$value['children']) {
    //                 unset($value['children']);
    //             }
    //             $result[] = $value;
    //         }
    //     }
    //     return $result;
    // }

     /**
     * 返回用户所在用户组菜单(修改菜单用)
     */
    function groupAuthList(int $group_id){
        // $menu_model = new \app\model\Menu();
        $menu = $this->where('hideInMenu',0)->order('order','desc')->select()->toArray();
        $menu_show = [];
        $auth_array = [];
        foreach($menu as $value){
            if($value['authority']==NULL){continue;}
            $auth = explode(',',$value['authority']);
            if(in_array($group_id,$auth)){
                $auth_array[]= $value['id'];
            }
            $menu_show[]=[
                'title' => $value['text'],
                'id' =>$value['id'],
                'key' =>$value['id'],
                'pid' =>$value['pid'],
            ];
        }
        $data['data'] = recursive($menu_show);
        $data['auth'] = $auth_array;
        return json($data); 
    }

    /**
     * 返回用户所在用户组菜单(修改菜单用)
     */
    function groupAuthList_bak(int $group_id){
        $menu_model = new \app\model\Menu();
        $menu = $menu_model->select();

        $auth_array = [];
        foreach($menu as $value){
            if($value['authority']==NULL){continue;}
            $auth = explode(',',$value['authority']);
            if(in_array($group_id,$auth)){
                $auth_array[]= $value['id'];
            }
        }
        $data['data'] = $menu;
        $data['auth'] = $auth_array;
        return json($data); 
    }

   /**
    * 修改信息
    *
    * @param integer $group_id
    * @param array $auth
    * @return void
    */
    public function upauth(int $group_id,array $auth){
        if((empty($auth))||(empty($group_id))){
            return false;
        }
        $menu_model = new \app\model\Menu();
        $menu = $menu_model->select();
        $data = [];
        foreach($menu as $value){
            if($value['authority']==NULL){
                $auth_tmp = [];
            }else{
                $auth_tmp = explode(',',$value['authority']);
            }
            
            if(in_array($value['id'],$auth)){
                if(!in_array($group_id,$auth_tmp)){
                    $data[] = [
                        'id' => $value['id'],
                        'authority' => $value['authority'].','.$group_id
                    ];
                }
            }else{
                $key = array_search($group_id,$auth_tmp);
                if ($key !== false){
                    array_splice($auth_tmp, $key, 1);
                }
                $data[] = [
                    'id' => $value['id'],
                    'authority' => implode(',',$auth_tmp)
                ];
            }
        }
        // print_r($data);
        addLog('修改权限,权限id:'.$group_id.'权限:'.implode(',',$auth));
        $res= $menu_model->saveAll($data);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回用户所在用户组菜单
     */
    function mylist(){
        $uid = getUid();
        $user_model = new \app\model\User();
        $user = $user_model->where('id',$uid)->findOrEmpty();
        if ($user->isEmpty()) {
            return [];
        }

        $user_group_id = $user['user_group_id'];
        $menu_model = new \app\model\Menu();
        $menu = $menu_model->select();

        $auth_array = [];
        foreach($menu as $value){
            if($value['authority']==NULL){continue;}
            $auth = json_decode($value['authority']);
            if(in_array($user_group_id,$auth)){
                $auth_array[]=$value['id'];
            }
        }
        $data['data'] = $menu;
        $data['auth'] = $auth_array;
        return json($data); 
    }


    


}