<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
class Product
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 添加产品
     */
    public function add(){
        $pro_name = input('pro_name');
        $pro_code = input('pro_code');
        $pro_cates_id = input('pro_cates_id');
        $price  = input('price')?input('price'):0;
        $weight = input('weight')?input('weight'):0;
        $size  = input('size')?input('size'):0;
        $high_line = input('high_line')?input('high_line'):0;
        $low_line = input('low_line')?input('low_line'):0;
        $status=input('status')?2:1;
        $other_code = input('other_code');
        $upexist = input('upexist')?input('upexist'):1;
        //表格批量录入
        $pro_cates_code = input('pro_cates_code');
        if($pro_cates_code){
            $pro_cates_model = new \app\model\ProCates();
            $cates = $pro_cates_model ->where('cates_code',$pro_cates_code)->findOrEmpty();
            if($cates->isEmpty()){
                return $this->JsonDataArr(ApiMsg::ERR_PRO_CATES_CODE);
            }else{
                $pro_cates_id = $cates['id'];
            }
        }
        
        if(empty($pro_name)||empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pro_model = new \app\model\Product();
        $res = $pro_model->add($pro_name,$pro_code,$pro_cates_id,$price,$weight,$size,$high_line,$low_line,$other_code,$status,$upexist);
        return $this->JsonCommon($res);
    }

    /**
     * 修改产品
     */
    public function updata(){
        $id = input('id');
        $pro_name = input('pro_name');
        $pro_code = input('pro_code');
        $pro_cates_id = input('pro_cates_id');
        $price  = input('price')?input('price'):0;
        $weight = input('weight')?input('weight'):0;
        $size  = input('size')?input('size'):0;
        $high_line = input('high_line')?input('high_line'):0;
        $low_line = input('low_line')?input('low_line'):0;
        $other_code = input('other_code');
        if(input('status')===true){
            $status =2;
        }elseif(input('status')===false){
            $status =1;
        }else{
            $status = 3;
        }

        if(empty($id)||empty($pro_name)||empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }

        $pro_model = new \app\model\Product();
        $res = $pro_model->updata($id,$pro_name,$pro_code,$pro_cates_id,$price,$weight,$size,$high_line,$low_line,$other_code,$status);
        return $this->JsonCommon($res);
    }


    /**
     * 产品列表
     * @return [type] [description]
     */
    public function list(){
        $current=input('current')?input('current'):1;
        $pageSize=input('pageSize')?input('pageSize'):10;
        $status=input('status')?input('status'):0;
        $pro_name=input('pro_name');
        $pro_code=input('pro_code');
        $excel = input('excel')?input('excel'):false;
        $other_code = input('other_code');
        $pro_cates_id = input('pro_cates_id');
        $pro_model = new \app\model\Product();
        return $pro_model->list($current,$pageSize,$status,$pro_name,$pro_code,$other_code,$excel,$pro_cates_id);
    }

    /**
     * 查找产品
     * @return [type] [description]
     */
    public function findpro(){
        $pro_code=input('pro_code');
        if(empty($pro_code)){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pro_model = new \app\model\Product();
        $product = $pro_model ->where('code',$pro_code)->findOrEmpty();
        if($product->isEmpty()){
            return $this->JsonDataArr(ApiMsg::ERR_SEARCH_PRODUCT);
        }else{
            return $this->JsonSuccess($product);
        }
    }


    /**
     * 删除产品
     * @return [type] [description]
     */
    public function del(){
        $id = input('id');
        if($id==NULL){
            return $this->JsonDataArr(ApiMsg::ERR_PARAMS_EMPTY);
        }
        $pro_model = new \app\model\Product();
        $res = $pro_model ->del($id);
        return $this->JsonCommon($res);
    }

}