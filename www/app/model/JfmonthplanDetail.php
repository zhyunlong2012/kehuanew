<?php
namespace app\model;
use think\Model;
/**
 * 解放车型产品表格
 */
class JfmonthplanDetail extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title' =>'string',
        'pro_code'    => 'string',
        'cartype'     => 'string',
        'total'       => 'int',
        'content'     => 'string',
    ];


    /**
     * 添加计划(有则删除)
     *
     * @param [type] $title
     * @param [type] $pro_code
     * @param [type] $cartype
     * @param [type] $total
     * @param [type] $content
     * @return void
     */
    public function add($title,$pro_code,$cartype,$total,$content){
        $res = JfmonthplanDetail::where('title', $title)->where('pro_code',$pro_code)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res['id'],$title,$pro_code,$cartype,$total,$content);
        }

        $jfcarmonthn_detail_model = new JfmonthplanDetail();
        $jfcarmonthn_detail_model->title = $title;
        $jfcarmonthn_detail_model->pro_code = $pro_code;
        $jfcarmonthn_detail_model->cartype = $cartype;
        $jfcarmonthn_detail_model->total = $total;
        $jfcarmonthn_detail_model->content = json($total);
        if($jfcarmonthn_detail_model->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 返回车型产品列表
     *
     * @param integer $current
     * @param integer $pageSize
     * @param [type] $title
     * @param [type] $pro_code
     * @param [type] $cartype
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$title,$pro_code,$cartype,$excel=false){
        $map = [];
        if(!empty($title)){$map[]=['title','=',$title];}
        if(!empty($pro_code)){$map[]=['pro_code','=',$pro_code];}
        if(!empty($cartype)){$map[]=['cartype','=',$cartype];}
        if($excel){
            $data['data'] = $this->where($map)->order('id','desc')->select();
        }else{
            $data['data'] = $this->where($map)->page($current,$pageSize)->order('id','desc')->select();
        }
        $data['total'] =  $this->where($map)->count();
        $data['current'] = $current;
        $data['pageSize'] = $pageSize;
        $data['success'] = true;

        $excel_data = [];
        foreach($data['data'] as $value){
            $content = json_decode($value['content'],true);
            $head = [
                '计划日期' => $value['title'],
                '产品代码' => $value['pro_code'],
                '车型名称' => $value['cartype'],
                '月需求量' => $value['total'],
            ];
            $days = [];
            for($i=1;$i<=31;$i++){
                $days[$i.'日'] =$content[$i.'日'];
            }
            $excel_data[] = array_merge($head,$days);
        }
        $data['data']=$excel_data;

        return json($data);
    }


    /**
     * 修改信息
     *
     * @param [type] $id
     * @param [type] $pro_code
     * @param [type] $cartype
     * @return void
     */
    public function updata($id,$title,$pro_code,$cartype,$total,$content){
        $res = JfmonthplanDetail::find($id);
        if (empty($res)) {
            return false;
        }

        $res->title = $title;
        $res->pro_code = $pro_code;
        $res->cartype = $cartype;
        $res->total = $total;
        $res->content = json($content);
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
        return JfmonthplanDetail::destroy($ids);
    }


}