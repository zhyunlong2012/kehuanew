<?php
namespace app\model;
use think\facade\Db;
use QL\QueryList;
use QL\Services\HttpService;
use QL\Ext\CurlMulti;
/**
 * 解放车型翻译
 */

class CelectJf{
    private static $base_url ='http://scm.fawjiefang.com.cn'; //解放网站
    private static $language =';oracle.apps.pom.cookie.language=ZHS';  //header中默认中文部分
    /**
     * 登录解放网站，获得解放cookie
     *
     * @param [type] $username
     * @param [type] $password
     * @return void
     */
    public function login($username,$password){
        $ql = new QueryList();
        $ql -> post(self::$base_url.'/services/login.jsp',[
            'ssousername' => $username,
            'password' => $password,
            'submitted'=>'T'
        ]);
        $hs = new HttpService();
        $cookie = $hs ->getCookieJar()->toArray();
        if(count($cookie)>1){
            $cookie_string = $cookie[0]['Name'].'='. $cookie[0]['Value'] ;
            return $cookie_string;
        }else{
            return false;
        }
    }

    /**
     * 辽弹计划
     *
     * @param [type] $cookie
     * @return void
     */
    public function getPlan($cookie){
        $ql = new QueryList();
        //看板计划下载网址
        $url = 'http://scm.fawjiefang.com.cn/qmscm/LGT/PM/qmPlanInfo.jsp?app=eleflow'; 
        $params = [];
        $headers = [
            'headers' => [ 'cookie' => $cookie.self::$language]
        ];

        $table = $ql->get($url,$params,$headers)->encoding('UTF-8','UTF-8')->find("table[cellpadding='5']:eq(1)");
        // 采集表的每行链接
        $tableRows = $table->find('tr')->map(function($row){
            return $row->find('td>a:not(:empty)')->attrs('href')->all();
        });
        $urls = $tableRows->all();
        // 采集表的每行标题
        $tableRowsText = $table->find('tr')->map(function($row){
            return $row->find('td>a:not(:empty)')->texts();
        });
        $resText = $tableRowsText->all();

        $plans = [];
        foreach($urls as $key=> $value){
            if(empty($value)){
                continue;
            }else{
                $plans[] =[
                    'url' =>self::$base_url.$value[0],
                    'title' =>$resText[$key][0]
                ] ;
            }
        }
        // dump($plans);
        return $plans;
    }

    /**
     * 解放查找车型
     *
     * @param [type] $cookie
     * @param [type] $carcode
     * @param [type] $cartype
     * @param [type] $continue 1跳过已有，2全部查询
     * @return void
     */
    public function getCar($cookie,$carcode,$cartype=null,$continue){
        if($continue==1){
            $jfcar_model = new Jfcar();
            $jfcar = $jfcar_model->where('carcode',$carcode)->findOrEmpty();
            if(!$jfcar->isEmpty()){
                $jfcar_detail_model = new JfcarDetail();
                $jfcar_detail = $jfcar_detail_model->where('carcode',$carcode)->findOrEmpty();
                if(!$jfcar_detail->isEmpty()){
                   return true; 
                }
                
            }
        }

        $ql = new Querylist();
        //查询车型网址
        $url = self::$base_url.'/qmscm/LGT/BOM/qmBOMViewBGY.jsp';
        $params = [
            'Action'=> 'Requery',
            'linenum'=>null, 
            'Search'=> 'Y',
            'NO'=> $carcode
        ];
        $headers = [
            'headers' => [ 'cookie' => $cookie.self::$language]
        ];

        $rule = [
            'href' =>['.qmTableCellText1>a:not(:empty)','href'],
        ];
        
        $res =  $ql ->get($url,$params,$headers)
                    ->rules($rule)
                    ->encoding('UTF-8','UTF-8')
                    ->query()
                    ->getData(); 
        
        if(count($res)<1){
            if(strlen($carcode)==17){
                $short_no = substr($carcode,0,15);
                return $this->getcar($cookie,$short_no,$cartype,$continue);
            }else{
                return false;
            }
        }else{
            $detail_url =  self::$base_url.$res[0]['href'];
            $detail = $this->getCarDetail($detail_url,$headers,$carcode);
            if(empty($detail)){
                return false;
            }
            // 启动事务
            Db::startTrans();
            try {
                $jfcar_model = new Jfcar();
                $jfcar_detail_model = new JfcarDetail();
                $res = $jfcar_model->add($carcode,$cartype);
                $jfcar_detail_model->where('carcode',$carcode)->delete();
                $res1 = $jfcar_detail_model->saveAll($detail);
                if($res&&$res1){
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
    }

    /**
     * 辽弹车型内容查找
     *
     * @param [type] $url
     * @param [type] $headers
     * @param [type] $carcode  辽弹数据有时候在车型部分是错误的，车型采用传入数据
     * @return void
     */
    public function getCarDetail($url,$headers,$carcode){
        $ql = new QueryList();
        //车型详情网址
        $table = $ql->get($url,null,$headers)->encoding('UTF-8','UTF-8')->find("table[cellpadding='1']")->htmls();
        // 采集表的每行内容
        $rql = QueryList::html($table[0]);
        $tableRows = $rql->find('tr:gt(0)')->map(function($row){
            return $row->find('td')->texts()->all();
        });
        $data = $tableRows->all();
        $detail = [];
        foreach($data as $value){
            if(count($value)<10){
                continue;
            }else{
                $detail[] = [
                    // 'carcode' => $value[1],
                    'carcode' => $carcode,
                    'pro_code' => $value[2],
                    'amount' => $value[5]
                ];
            }
            
        }
        //去重
        $hash_detail = [];
        foreach($detail as $value){
            $hash_detail[$value['pro_code']]=0;
        }
        if(count($hash_detail)<count($detail)){
            $res = [];
            foreach($hash_detail as $key=> $value){
                $sum = 0;
                foreach($detail as $key1 => $value1){
                    if($value1['pro_code'] == $key){
                        $sum = $sum + $value1['amount'];
                    }else{
                        continue;
                    }
                }
                $res[]=[
                    // 'carcode' =>$detail[0]['carcode'],
                    'carcode' => $carcode,
                    'pro_code' => $key,
                    'amount' =>$sum
                ];
            }
        }else{
            $res = $detail;
        }
        
        return $res;
    }
    
    /**
     * 将解放表单中数据，查询出对应车型，并返回
     *
     * @param [type] $data
     * @return void
     */
    public function getPlanExcel($data){
        $jfcar_detail_model = new JfcarDetail();
        $excel = [];
        foreach ($data as $value) {
            $carname = isset($value['车型名称'])?$value['车型名称']:'';
            $month_plan_total = isset($value['月计划量'])?$value['月计划量']:0;
            $arr_header = [
                '产品代码' =>$value['产品代码'],
                '车型名称' =>$carname,
                '月计划量' => $month_plan_total
            ];
            $arr_day = [];
            for($i=1;$i<=31;$i++){
                if(isset($value[$i])){
                    $arr_day[$i.'日']=$value[$i];
                }else{
                    $arr_day[$i.'日']=0;
                }
            }
            $arr_code = [
                '2902010-' => '',
                '2902010数量' => '',
                '2902015-' => '',
                '2902015数量' => '',
                '2932010-' => '',
                '2932010数量' => '',
                '2912010-' => '',
                '2912010数量' => '',
                '2913010-' => '',
                '2913010数量' => '',
                '2919172-' => '',
                '2919172数量' => '',
                '2942010-' => '',
                '2942010数量' => '',
                '2912005-' => '',
                '2912005数量' => '',
                '2912020-' => '',
                '2912020数量' => '',
                '2912015-' => '',
                '2912015数量' => ''
            ];
            $hash_excel =array_merge($arr_header,$arr_day,$arr_code);
            $tmp = $jfcar_detail_model ->where('carcode',$value['产品代码'])->select()->toArray();
            foreach($tmp as $value){
                if(isset($hash_excel[substr($value['pro_code'],0,7)]).'-'){
                    $hash_excel[substr($value['pro_code'],0,7).'-']=$value['pro_code'];
                    $hash_excel[substr($value['pro_code'],0,7).'数量']=$value['amount'];
                }
            }
            $excel[]=$hash_excel;

        }
        
        addLog('翻译解放订单');
        return $excel;

    }

    /**
     * 根据解放订单，生产月计划
     *
     * @param [type] $data
     * @return void
     */
    public function getMonthExcel($data){
        addLog('生成月计划');
        //建立车型代码HASH表,用于统计
        $jfcar_detail_model = new JfcarDetail();
        $pro_list = [];
        foreach ($data as $key => $value) {
            $cartype_tmp = $jfcar_detail_model ->where('carcode',$value['产品代码'])->column('amount','pro_code');
            $data[$key]['pro_code_arr'] = $cartype_tmp;
            //HASH数组VALUE由数量转换为车型
            foreach($cartype_tmp as $key1 => $value1){
                $pro_list[$key1] =isset($value['车型名称'])? $value['车型名称']:'未定义车型';
            }
        }
        $month_data = [];
        foreach($pro_list as $key => $value){
            $month_tmp = [];
            $month_tmp['产品代码'] = $key;
            $month_tmp['车型名称'] = $value;
            $month_tmp['月需求量']=0;
            $month_total = 0; //月需求量
            for($i=1;$i<=31;$i++){
                $sum=0;//计数
                foreach ($data as $key1 => $value1) {
                    if(!isset($value1[$i])){continue;}
                    if(!isset($value1['pro_code_arr'][$key])){continue;}
                    $sum = $sum + $value1[$i]*$value1['pro_code_arr'][$key];
                }

                $month_tmp[$i.'日']=$sum;
                $month_total=$month_total+$sum;
            }
            $month_tmp['月需求量']=$month_total;
            $month_data[]=$month_tmp;
        }
        // print_r($month_data);
        return $month_data;
    }

    /**
     * 批量查询车型(解放网站查询时间过慢，暂时无法启用)
     *
     * @return void
     */
    public function multiCar(){
        $cookie = input('cookie');
        $no = input('no');
        $no =  ['B01AM581C1RJ5AYB4','C014H45561T74QYB4'];

        //看板下载网址
        $base_url ='http://scm.fawjiefang.com.cn';
        $url = 'http://scm.fawjiefang.com.cn/qmscm/LGT/BOM/qmBOMViewBGY.jsp?Action=Requery&Search=Y&NO='; 
        $headers = [
            'headers' => [ 'cookie' => $cookie.';oracle.apps.pom.cookie.language=ZHS']
        ];

        $rule = [
            'href' =>['.qmTableCellText1>a:not(:empty)','href'],
        ];
        
        $ql = QueryList::use(CurlMulti::class);
        $urls =[];
        foreach($no as $value){
            $urls[] = $url.$value;
        }
        // $urls = [
        //         'http://www.bjnews.com.cn/realtime',
        //         'http://www.bjnews.com.cn/opinion/',
        //         //.....more urls
        // ];
        $ql->rules($rule)->curlMulti($urls)
        // 每个任务成功完成调用此回调
        ->success(function (QueryList $ql,CurlMulti $curl,$r){
            // echo "Current url:{$r['info']['url']} \r\n";
            $res =  $ql
                    ->encoding('UTF-8','UTF-8')
                    ->query()
                    ->getData(); 
                    print_r($res->all());
            // echo '---------------<hr />';
        })
        // 每个任务失败回调
        ->error(function ($errorInfo,CurlMulti $curl){
            // echo "Current url:{$errorInfo['info']['url']} \r\n";
            print_r($errorInfo['error']);
        })
        ->start([
            // 最大并发数
            'maxThread' => 10,
            // 错误重试次数
            'maxTry' => 3,
            'opt' => [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('cookie:JSESSIONID=QMLBpzSdLl9lySrdTNhSzrgNKh5cllK1wMlJjs7JBPYgqC7pHyDv!-605063992;oracle.apps.pom.cookie.language=ZHS')
               
            ],
        ]);
    }

}