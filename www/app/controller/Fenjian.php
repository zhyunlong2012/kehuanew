<?php
namespace app\controller;
use app\common\ApiMsg;
use app\middleware\Auth;
/**
 * 按两条线E,F，车序，零件匹配，生成计划，随车单，零件标识单
 */
class Fenjian
{
    protected $middleware = [Auth::class];
    use \app\common\ResponseMsg;

    /**
     * 生成计划
     */
    public function getplan()
	{
		$data = input('data');
		$SAB = input('AB');
		$newData = [];
		//排序
		$dataModle= new \app\model\Ltproduct();
		foreach ($data as $key => $value) {
			$value = json_decode($value,true);
			
			$time[]=$value['fcsj'];
			$xian[]=$value['gongwei'];
			$chexu[]=$value['chexu'];

			$tmp1 = [
				'发车时间' => $value['fcsj'],
				'物料编号' => $value['pro_code'],
				'数量' => $value['amount'],
				'工位' => $value['gongwei'],
				'车序' => $value['chexu'],
				'单据编号' => $value['djbh'],
				'AB' => $value['AB']
			];
			// dump($value);
			$res = $dataModle->where('pro_code',$value['pro_code'])->find();

			if($res){
				$tmp2 = [
					'全称' => $res['pro_name'],
					'配对物料号' => $res['pp_code'],
					'配对物料' => $res['pp_name'],
					'一托物料数' => $res['geshu'],
					'一格托盘数' => $res['tuoshu'],
					'E线装车比' => $res['bili'],
					'重量' => $res['weight'],
				];
			}else{
				$tmp2 = [
					'全称' => '',
					'配对物料号' => '',
					'配对物料' => '',
					'一托物料数' => '',
					'一格托盘数' => '',
					'E线装车比' => '',
					'重量' => '',
				];
			}
			$tmp = array_merge($tmp1,$tmp2);
			$newData[] = $tmp;
		}
		array_multisort($time,SORT_ASC,$xian,SORT_ASC,$chexu,SORT_ASC,$newData);
		//AB
		$sab_excel=[];
		if($SAB=='SB'){
			foreach($newData as $value){
				if($value['AB']=='SB')
				{
					$sab_excel[]=$value;
				}
			}
		}else{
			foreach($newData as $value){
				if($value['AB']=='SA')
				{
					$sab_excel[]=$value;
				}
			}
		}
		
		return $sab_excel;
	}

	/**
	 * 根据计划数据，自动匹配，自动排序算法
	 *
	 * @return void
	 */
// 	public function getcarbill()  //上传保存好的计划单
// 	{
// 		$data = input('data');  //excel数据
// 		$boxnum = input('boxnum');     //boxnum 一个多少格子
			
// 		//装托盘  配对的进行查找，不足一托的按数量补齐一托
// 		for($i=0;$i<count($data);$i++)
// 		{
// 			if(($data[$i]['4']=='已配对')or($data[$i][3]==0)){ //如果物料数为空或者已配对略过
// 				continue;
// 			}else{
// 				$tuopans=floor($data[$i][3]/$data[$i][6]);//使用托盘数
// 				$wuliao_left=$data[$i][3]-$tuopans*$data[$i][6]; //不足一托
// 				$wuliao_queshao=$data[$i][6]-$wuliao_left;
// 				for($j=0;$j<$tuopans;$j++){
// 					$tuopan_today[]=[
// 						'wuliaohao'=>$data[$i][1],
// 						'wuliaoname'=>$data[$i][2],
// 						'shuliang'=>$data[$i][6],
// 						'height'=>floor(12/$data[$i][7]),
// 						'time'=>$data[$i][0],
// 						'peidui'=>$data[$i][4],
// 						'peiduiname'=>$data[$i][5],
// 						'piaoju'=>$data[$i][11],
// 						'xian'=>$data[$i][9],
// 						'zhuangchebi'=>$data[$i][8]
// 					];
// 				}
// 				if($wuliao_left==0){
// 					continue; //整除则跳过
// 				}else{
// 					//向下补全
// 					//如果是最后一托
// 					if($i==count($data)-1){
// 						$tuopan_today[]=[
// 							'wuliaohao'=>$data[$i][1],
// 							'wuliaoname'=>$data[$i][2],
// 							'shuliang'=>$wuliao_left,
// 							'height'=>floor(12/$data[$i][7]),
// 							'time'=>$data[$i][0],
// 							'peidui'=>$data[$i][4],
// 							'peiduiname'=>$data[$i][5],
// 							'piaoju'=>$data[$i][11],
// 							'xian'=>$data[$i][9],
// 							'zhuangchebi'=>$data[$i][8]
// 						];
// 					}else{
// 						//不是最后一托，向后补全
// 						for($j=$i+1;$j<count($data);$j++)
// 						{
// 							if($data[$j][1]==$data[$i][1]) //物料号 线
// 							{
// 								if($data[$j][3]>=$wuliao_queshao){
// 									$data[$j][3]=$data[$j][3]-$wuliao_queshao;
// 									$tuopan_today[]=[
// 										'wuliaohao'=>$data[$i][1],
// 										'wuliaoname'=>$data[$i][2],
// 										'shuliang'=>$data[$i][6],
// 										'height'=>floor(12/$data[$i][7]),
// 										'time'=>$data[$i][0],
// 										'peidui'=>$data[$i][4],
// 										'peiduiname'=>$data[$i][5],
// 										'piaoju'=>$data[$i][11],
// 										'xian'=>$data[$i][9],
// 										'zhuangchebi'=>$data[$i][8]
// 									];
// 									break;
// 								}else{
// 									$wuliao_queshao=$wuliao_queshao-$data[$j][3];
									
// 									$wuliao_left=$wuliao_left+$data[$j][3];
// 									$data[$j][3]=0;
// 									if($j==count($data)-1){
// 										$tuopan_today[]=[
// 											'wuliaohao'=>$data[$i][1],
// 											'wuliaoname'=>$data[$i][2],
// 											'shuliang'=>$wuliao_left,
// 											'height'=>floor(12/$data[$i][7]),
// 											'time'=>$data[$i][0],
// 											'peidui'=>$data[$i][4],
// 											'peiduiname'=>$data[$i][5],
// 											'piaoju'=>$data[$i][11],
// 											'xian'=>$data[$i][9],
// 											'zhuangchebi'=>$data[$i][8]
// 										];
// 										break;
// 									}
// 								}
// 							}else{ //找到最后没有找到匹配项
// 								if($j==count($data)-1){
// 									$tuopan_today[]=[
// 										'wuliaohao'=>$data[$i][1],
// 										'wuliaoname'=>$data[$i][2],
// 										'shuliang'=>$wuliao_left,
// 										'height'=>floor(12/$data[$i][7]),
// 										'time'=>$data[$i][0],
// 										'peidui'=>$data[$i][4],
// 										'peiduiname'=>$data[$i][5],
// 										'piaoju'=>$data[$i][11],
// 										'xian'=>$data[$i][9],
// 										'zhuangchebi'=>$data[$i][8]
// 									];
// 									break;
// 								}
// 							}


// 						}
// 					}
					
// 				} 		
// 			}	
// 		} 

// 		   	// dump($tuopan_today);die;
// 			// 
// 			// dump($tuopan_today);die;
// 			//分线 分时间
// 			$e_data=[];
// 			$f_data=[];
// 			$car_time[0]=$tuopan_today[0]['time'];
// 			$i=0;
// 			foreach ($tuopan_today as $key => $value) {
// 				if($car_time[$i]!=$value['time']){
// 					$i++;
// 					$car_time[$i]=$value['time'];
// 				}
// 				if($value['xian']=="E"){
// 					$e_data[]=$value;
// 				}else{
// 					$f_data[]=$value;
// 				}
// 			}

// 			// dump($e_data);die;
// 			// dump($f_data);die;
// 			// dump($car_time);die;

// 			//装车
// 			$car=[];	
// 			 $i=0; //车序
// 			$top_height=12;//最高承载量
// 			$now_height=0; //即时承载量
// 			$top_box=9;//最多格子数
// 			$now_box=0;//即时格子数
// 			// $box_line=$tuopan_today[0]['zhuangchebi'];
//             $f_flag=0;
//             $e_flag=0;
//             $f_end=0;//是否到达底部
//             $e_end=0;
// 			$z=0;
// 			//新算法
// 			$e_line=0;
// 			$e_now=0;
// 			// echo count($f_data);
// 			// dump($car_time);
// 			foreach ($car_time as $key => $value) 
// 			{ //分时段装车
// 				// echo '<Br>$value'.$value;
// 				if((count($e_data)>0)and($e_flag<count($e_data))){ //e有数据则先考虑e
// 					// echo 'e线查找数据$counte'.count($e_data).'$e_flag'.$e_flag;
// 					for($e=$e_flag;$e<count($e_data);$e++)
// 					{
// 						// echo '<br>开始在E线查找数据'.$e.'<br>';
// 						if($e_data[$e]['time']==$value)
// 						{
// 							$e_line=$e_data[$e]['zhuangchebi']*$top_height;
// 							// echo '查找E线数据e的id'.$e.'flag::'.$f_flag.'$z::'.$z.'count($f_data)'.count($f_data).'-----<BR>';
// 							$box_limit=floor($e_data[$e]['zhuangchebi']); //装车比例整数
// 		            		$height_line=($e_data[$e]['zhuangchebi']-$box_limit)*$top_height;
// 		            		// dump($z);
// 							$now_height=$now_height+$e_data[$e]['height'];
// 							$e_now=$e_now+$e_data[$e]['height'];
// 							//f还有数据 数据没有查询完  当前格子等于限制格子 高度达到限制高度
// 							if($f_flag<count($f_data) and ($z!=count($f_data)-1) and ($e_now>$e_line) )
// 							{
// 								// echo 'E线装车完毕，到F线查找f数据个数'.count($f_data);
// 								// echo 'E线装车完毕，到F线查找box_id'.$now_box;
// 								$now_height=$now_height-$e_data[$e]['height'];
// 									$e_now=$e_now-$e_data[$e]['height'];
// 		            				for($f=$f_flag;$f<count($f_data);$f++)
// 		            				{ 
// 										// echo '<br>zhuangFxian'.$f;
// 										if($f_data[$f]['time']==$value)
// 										{
// 											$now_height=$now_height+$f_data[$f]['height'];
// 												if($now_height>$top_height)
// 												{
// 													$now_height=$now_height-$f_data[$f]['height'];
// 							            			$f=$f-1;
// 													$now_height=0;
// 													$now_box++;
// 													if($now_box==$top_box)
// 													{
// 														// echo '---f i+_____换车____car_id'.$i;
// 														$now_box=0;
// 														$e_line=0;	
// 														$i++;
// 														break;
// 													}
// 												}else{
// 													// echo '<br>box_Id'.$now_box.'E超高F线装车flag'.$f_flag;
// 													$f_data[$f]['box_id']=$now_box;
// 													$car[$i][]=$f_data[$f];
// 													$f_flag++;
// 												}
// 										}
// 										$z=$f;
										
// 									}
// 									$e=$e-1;
// 							}else{
// 								// echo '装车前e的ID'.$e.'-----<br>';
// 								if($now_height>$top_height)
// 									{	
// 										$e_now=$e_now-$e_data[$e]['height'];
// 										$now_height=$now_height-$e_data[$e]['height'];
// 				            			$e=$e-1;		
// 										$now_height=0;
// 										$now_box++;
// 										if($now_box==$top_box)
// 										{
// 											$e_line=0;	
// 											// echo '$now_box'.$now_box.'---e 线装车___超高_____'.$e;
// 											$now_box=0;	
// 											$i++;
// 											continue;
// 											// break;
// 										}
// 									}else{
// 										// echo '<br>已装车box'.$now_box.'car'.$i.'E线装车E的ID'.$e;
// 										$e_data[$e]['box_id']=$now_box;
// 										$car[$i][]=$e_data[$e];
// 										$e_flag++;
// 									}
// 							}
// 						}
// 						//e有数据但是没有该时段数据 则F线直接装车
// 						if($e==count($e_data)-1)
// 						{
// 							// echo 'fffffff';
// 							for($f=$f_flag;$f<count($f_data);$f++)
// 		    				{ 
// 		    					// echo 'E线装车完毕，到F线查找f数据个数'.count($f_data);
// 								// echo '<br>zhuangFxian--e段数据为空到F线查找box_id'.$now_box.'车ID'.$i;
// 								// echo '<br>$f_data[$f][]'.$f_data[$f]['time'].'$value'.$value;
// 								if($f_data[$f]['time']==$value)
// 								{
// 									// echo '123';
// 									$now_height=$now_height+$f_data[$f]['height'];
// 										if($now_height>$top_height)
// 										{
// 											// echo 'f超过格子高度';
// 											$now_height=$now_height-$f_data[$f]['height'];
// 					            			$f=$f-1;
// 											$now_height=0;
// 											$now_box++;
// 											if($now_box==$top_box)
// 											{
// 												// echo '<br>---f i+换车_________'.$now_box.'count(f_data)'.count($f_data);
// 												$now_box=0;	
// 												$i++;
// 												$e_line=0;	
// 											}
// 										}else{
// 											// echo '<br>e有数据，没有该时段物料，F线装车fid'.$f_flag;
// 											$f_data[$f]['box_id']=$now_box;
// 											$car[$i][]=$f_data[$f];
// 											$f_flag++;
// 										}
// 								}
								
// 							}
// 						}	
// 					}
// 					// echo 'cccccccc';
// 				}else{ //e没有数据则直接F线装车
// 					for($f=0;$f<count($f_data);$f++)
//     				{ 
// 						// echo '<br>e线没有数据，到F查找'.$f;
// 						if($f_data[$f]['time']==$value)
// 						{
// 							$now_height=$now_height+$f_data[$f]['height'];
// 								if($now_height>$top_height)
// 								{
// 									$now_height=$now_height-$f_data[$f]['height'];
// 			            			$f=$f-1;
// 									$now_height=0;
// 									$now_box++;
// 									if($now_box==$top_box)
// 									{
// 										$e_line=0;	
// 										// echo '---f i+ 换车。。。_________'.$now_box;
// 										$now_box=0;	
// 										$i++;
// 									}
// 								}else{
// 									// echo '<br>e没有任何数据，F线装车。box_id'.$now_box.'车ID'.$i.'f_flag'.$f_flag;
// 									$f_data[$f]['box_id']=$now_box;
// 									$car[$i][]=$f_data[$f];
// 									$f_flag++;
// 								}
// 						}
						
// 					}
// 				}

// 				$z=0; //z归零

// 				//不足一车向后补齐

// 				// $now_box=0;
// 				// $i++;
							
		

// 				// $i++;
// 				// echo '---结尾+_________'.$now_box;


// 			}
// 			// dump($car);die;

// 			//打印随车单
// 			// $suichedan=[];
// 			$suichedan=$car;
// 			// dump($suichedan);
// 			for($i=0;$i<count($suichedan);$i++)
// 			{
// 				for($j=0;$j<count($suichedan[$i]);$j++)
// 				{
// 					$suichedan[$i][$j]['zongshu']=count($suichedan[$i]);
// 					// $suichedan[$i][$j]['danhao']=count($suichedan[$i]);
// 					//不是最后一个，向下查找
// 					if($j!=count($suichedan[$i])-1)
// 					{
// 						for($z=$j+1;$z<count($suichedan[$i]);$z++)
// 						{
// 							if($suichedan[$i][$j]['wuliaohao']==$suichedan[$i][$z]['wuliaohao'])
// 							// if(($suichedan[$i][$j]['wuliaohao']==$suichedan[$i][$z]['wuliaohao']) and ($suichedan[$i][$j]['xian']==$suichedan[$i][$z]['xian']))
// 							{
// 								$suichedan[$i][$j]['shuliang']=$suichedan[$i][$j]['shuliang']+$suichedan[$i][$z]['shuliang'];
// 								$suichedan[$i][$z]['shuliang']=0;
// 							}
// 						}
// 					}
					
// 				}
// 				// die;
// 			}
// 			// dump($suichedan);die;
// 			//空数据清理
// 			$dataModle= new \app\wareh\model\Ltproduct();
		    	
// 			foreach ($suichedan as $key => $value) {
// 				foreach ($value as $key1 => $value1) {
// 					//不是最后一个，向下查找
// 					if($value1['shuliang']==0)
// 					{
// 						unset($suichedan[$key][$key1]);
// 					}else{
// 						$res = $dataModle->where('my_num',$value1['wuliaohao'])->find();
// 						if($res){
// 							$suichedan[$key][$key1]['zhongliang']=$res['zhongliang'];
// 							$suichedan[$key][$key1]['zongzhong']=$res['zhongliang']*$suichedan[$key][$key1]['shuliang'];
// 						}
// 					}
// 				}


// 			}

// // dump($suichedan);die;
// 			$suiche_add=$this->exportSuichedan($suichedan);
// 			$this->assign('suichedan',$suiche_add);
// 			//打印标识
// 			//
// 			//
// 			// dump($car);die;
// 			// $biaoshi_add=$this->exportAll($tuopan_today); //打印全天标识单
// 			$biaoshi_add=$this->exportAll($car); 
// 			$this->assign('biaoshidan',$biaoshi_add);
// 			return $this->fetch();
// 			}else{
// 				echo $file->getError();
// 			}
// 		}
// 	}
}

