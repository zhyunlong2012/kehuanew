<?php
namespace app\model;
use think\Model;
/**
 * 解放订单表格
 */
class JfPlan extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title'     => 'string',  
        'url'     => 'string',
        'create_time' => 'int',
        'update_time' =>'int' 
    ];


    /**
     * 添加计划
     *
     * @param [type] $title
     * @param [type] $url
     * @return void
     */
    public function add($title,$url){
        $res = JfPlan::where('url', $url)->findOrEmpty();
        if (!$res->isEmpty()) {
            return false;
        }

        $jfplan_model = new JfPlan();
        $jfplan_model->title = $title;
        $jfplan_model->url = $url;
        if($jfplan_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回车型列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $title
     * @param [type] $url
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$title,$url,$excel=false){
        $map = [];
        if(!empty($title)){$map[]=['title','=',$title];}
        if(!empty($url)){$map[]=['url','=',$url];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;
        return json($data);
    }


    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $title
     * @param [type] $url
     * @return void
     */
    public function updata($id,$title,$url){
        $res = JfPlan::find($id);
        if (empty($res)) {
            return false;
        }

        $res->title = $title;
        $res->url = $url;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        return JfPlan::destroy($ids);
    }


}