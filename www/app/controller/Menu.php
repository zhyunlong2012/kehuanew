<?php
namespace app\controller;
use app\middleware\Auth;
class Menu
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 按前端格式返回菜单
     */
    public function index(){
        $menu_model = new \app\model\Menu();
        return $menu_model->list();
    }

    /**
     * 返回用户组菜单
     */
    public function list(){
        $menu_model = new \app\model\Menu();
        $group_id = input('id');
        return $menu_model->groupAuthList($group_id);
    }

    public function upauth(){
        $id = input('id');
        $authority = input('authority');
        $menu_model = new \app\model\Menu();
        $res = $menu_model ->upauth($id,$authority);
        return $this->JsonCommon($res);
    }


}