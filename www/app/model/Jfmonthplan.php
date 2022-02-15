<?php
namespace app\model;
use think\Model;
use think\facade\Db;
/**
 * 解放历史计划表格
 */
class Jfmonthplan extends Model{
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title'     => 'string',  //*年*月计划
        'create_time' => 'int',
        'update_time' => 'int'
    ];


    /**
     * 添加计划(有则修改)
     *
     * @param [type] $title
     * @return void
     */
    public function add($title){
        $res = Jfmonthplan::where('title', $title)->findOrEmpty();
        if (!$res->isEmpty()) {
            return $this->updata($res['id'],$title);
        }

        $Jfmonthplan_model = new Jfmonthplan();
        $Jfmonthplan_model->title = $title;
        if($Jfmonthplan_model->save() == true){
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
     * @param boolean $excel
     * @return void
     */
    public function list($current=1,$pageSize=10,$title,$excel=false){
        $map = [];
        if(!empty($title)){$map[]=['title','=',$title];}
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
     * @return void
     */
    public function updata($id,$title){
        $res = Jfmonthplan::find($id);
        if (empty($res)) {
            return false;
        }

        $res->title = $title;
        if($res->save() == true){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除车型的同时，删除车型详细信息
     * @param  array  $ids [description]
     * @return [boolen]      [description]
     */
    public function del($ids = []){
        // 启动事务
        Db::startTrans();
        try {
            $Jfmonthplan_detail_model = new JfmonthplanDetail();
            $Jfmonthplan_detail_titles = $this->where('id','in',$ids)->column('title');
            $res = Jfmonthplan::destroy($ids);
            $res1 = $Jfmonthplan_detail_model->where('title','in',$Jfmonthplan_detail_titles)->delete();
            if($res&&$res1){
                // 提交事务
                Db::commit();
                addLog('删除解放生产计划id:'.$ids);
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

   /**
    * 添加计划并录入详细信息
    *
    * @param [type] $title
    * @param [type] $data
    * @return void
    */
    public function addTogather($title,$data){
        $jfmonth_plan_model = new Jfmonthplan();
        $jfmonth_detail_model = new JfmonthplanDetail();

        //信息整理(将解放样式表格转换为系统需要)
        $exceldata = [];
        foreach($data as $value){
            $tmp = [
                'title' => $title,
                'pro_code' => $value['产品代码'],
                'total' => $value['月需求量'],
                'cartype' => $value['车型名称']
            ];
            unset($value['产品代码']);
            unset($value['月需求量']);
            unset($value['车型名称']);
            $tmp['content'] = json_encode($value,256);
            $exceldata[] = $tmp;
        }
        // print_r($exceldata);

        // 启动事务
        Db::startTrans();
        try {
            $res1 = $jfmonth_plan_model ->add($title);
            $jfmonth_detail_model ->where('title',$title)->delete();
            $res2 = $jfmonth_detail_model ->saveAll($exceldata);
            if($res1&&$res2){
                // 提交事务
                Db::commit();
                return true;
            }else{
                Db::rollback();
                return false;
            }
            
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return false;
        }
    }

    /**
    * 对比上版计划差异
    *
    * @param [type] $data
    * @return void
    */
    public function difflastplan($data){
        $jfmonth_plan_model = new Jfmonthplan();
        $last_plan = $jfmonth_plan_model->order('id','desc')->limit(1)->select();
        if(count($last_plan)!=1){
            return false;
        }
        $jfmonth_detail_model = new JfmonthplanDetail();

        $last_plan_detail = $jfmonth_detail_model->where('title',$last_plan[0]['title'])->column('*','pro_code');
        if(count($last_plan_detail)<=0){
            return false;
        }
        // print_r($last_plan_detail);
        //建立当前表格的hash数组，用于整合所有数据
        $hash_data = [];
        foreach($data as $value){
            $hash_data[$value['产品代码']]=$value;
        }
        //生成所有数据表格
        // $hash_all =array_merge($last_plan_detail,$hash_data);
        //计算差异
        $diff = [];
        //1先查找新表中数据
        // print_r($hash_data);
        // print_r($last_plan_detail);
        foreach($hash_data as $key => $value){
            if(isset($last_plan_detail[$key])){
                $content = json_decode($last_plan_detail[$key]['content'],true);
                // print_r($content);
                $head = [
                    '产品代码' => $value['产品代码'],
                    '车型名称' => $value['车型名称'],
                    '月需求量' => $value['月需求量']-$last_plan_detail[$key]['total'],
                ];
                $days = [];
                for($i=1;$i<=31;$i++){
                    $days[$i.'日'] = $value[$i.'日'] - $content[$i.'日'];
                }
                $diff[] = array_merge($head,$days);
            }else{
                $diff[] = $value;
            }
        }
        
        foreach($last_plan_detail as $key => $value){
            if(isset($hash_data[$key])){
                continue;
            }else{
                $content = json_decode($value['content'],true);
                $head = [
                    '产品代码' => $value['pro_code'],
                    '车型名称' => $value['cartype'],
                    '月需求量' => '-'.$value['total'],
                ];
                $days = [];
                for($i=1;$i<=31;$i++){
                    $days[$i.'日'] ='-'. $content[$i.'日'];
                }
                $diff[] = array_merge($head,$days);
            }
        }
        return $diff;

    }



}