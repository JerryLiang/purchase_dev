<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/1/9 0009 14:03
 */

class Purchase_service {

    /**
     * 验证采购单结算比例是否正确
     * @author Jolon
     * @param string $ratio  结算比例（目前支持三次）
     * @return bool
     */
    public function checkSettlementRatio($ratio){
        if(!$ratio) return false;

        $ratio = explode('+', $ratio);
        if(count($ratio) > 3) return false;

        $total = 0;
        foreach($ratio as $v){
            $total += intval($v);
            if($total > 100 || $total < 1){
                return false;
            }
        }

        return $total == 100;
    }

    /**
     * 合同 按比例请款 请款金额按比例拆分（最高3段）
     * @author Jolon
     * @param string $ratio        合同的结算比例 ，如：30%+70% 或 30%+30%+40%
     * @param float  $productMoney 合同的总产品额
     * @param float  $freight      合同的总运费
     * @param float  $discount     合同的总优惠额
     * @param int    $is_drawback  是否退税（1.退税,0.不退税）
     * @return bool|array          返回  订金、中款、[尾款、] 尾款总额、实际总金额 信息
     */
    public function compactPaymentPlan($ratio, $productMoney, $freight, $discount, $is_drawback = 0){
        $_ratio = explode('+', $ratio);
        $dj     = 0;// 订金（扣减优惠额）
        $mk     = 0;// 中款
        $wk     = 0;// 尾款
        $wk_t   = 0;// 尾款总额（含运费）

        if(count($_ratio) < 1 or count($_ratio) > 3){
            return false;
        }

        if($is_drawback == 1){ // 含税（对公）
            if(count($_ratio) > 1){
                if(count($_ratio) == 2){
                    $djRatio = $_ratio[0];
                    $wkRatio = $_ratio[1];
                    $dj_tmp  = format_two_point_price(($productMoney * (int)$djRatio) / 100);// 产品总额*首款百分比

                //    $dj      = $dj_tmp - $discount;  // 订金 = 产品总额*首款百分比 - 优惠
                    $dj      = $dj_tmp;  // 订金 = 产品总额*首款百分比
                    $wk      = $productMoney - $dj_tmp; // 尾款（确保  首款 + 尾款 = 产品总额）
                    $wk_t    = $wk;// 尾款总额
                }else{
                    $djRatio = $_ratio[0];
                    $mkRatio = $_ratio[1];
                    $wkRatio = 100 - (int)$djRatio - (int)$mkRatio;
                    $dj_tmp  = format_two_point_price(($productMoney * (int)$djRatio) / 100);// 产品总额*首款百分比
                    $mk_tmp  = format_two_point_price(($productMoney * (int)$mkRatio) / 100);// 中款

                   // $dj      = $dj_tmp - $discount; // 订金除去优惠额
                    $dj      = $dj_tmp; // 订金
                    $mk      = $mk_tmp;   // 中款
                    $wk      = $productMoney - $dj_tmp - $mk_tmp; // 尾款（确保  首款 + 中款 + 尾款 = 产品总额）
                    $wk_t    = $wk; // 尾款总额
                }
            }
        }else{ // 不含税（对私）
            if(count($_ratio) > 1){
                if(count($_ratio) == 2){
                    $djRatio = $_ratio[0];
                    $wkRatio = $_ratio[1];
                    $dj_tmp  = format_two_point_price(($productMoney * (int)$djRatio) / 100 );// 产品总额*首款百分比
                  //  $dj      = $dj_tmp - $discount;  // 订金 = 产品总额*首款百分比-优惠
                    $dj      = $dj_tmp;  // 订金 = 产品总额*首款百分比
                    $wk      = $productMoney - $dj_tmp;// 尾款（确保  首款 + 尾款 = 产品总额）
                    //$wk_t    = $wk + $freight; // 尾款总额 = 产品总额*尾款百分比 + 运费
                    $wk_t    = $wk; // 尾款总额 = 产品总额*尾款百分比
                }else{
                    $djRatio = $_ratio[0];
                    $mkRatio = $_ratio[1];
                    $wkRatio = 100 - (int)$djRatio - (int)$mkRatio;
                    $dj_tmp  = format_two_point_price(($productMoney * (int)$djRatio) / 100 );// 产品总额*首款百分比
                    $mk_tmp  = format_two_point_price(($productMoney * (int)$mkRatio) / 100);// 中款

                  //  $dj      = $dj_tmp - $discount; // 订金除去优惠额
                    $dj      = $dj_tmp; // 订金
                    $mk      = $mk_tmp; // 中款不含运费与优惠
                    $wk      = $productMoney - $dj_tmp - $mk_tmp; // 尾款（确保  首款 + 中款 + 尾款 = 产品总额）
                 //   $wk_t    = $wk + $freight; // 尾款含运费
                     $wk_t    = $wk; // 尾款
                }
            }
        }

        $result = [
            'dj'   => format_price($dj),
            'mk'   => format_price($mk),
            'wk'   => format_price($wk),
            'wk_t' => format_price($wk_t),
            'real_money' => format_price($productMoney + $freight - $discount),
          //   'real_money' => format_price($productMoney),
        ];
        if(empty($result['mk']) or $result['mk'] == 0){// 删除中款
            unset($result['mk']);
        }
        return $result;
    }

    /**
     * 合同 按比例请款 请款金额按比例拆分（最高3段）-【不计算运费和优惠额】
     * @author Jolon
     * @param string $ratio        合同的结算比例 ，如：100% 或 30%+70% 或 30%+30%+40%
     * @param float  $productMoney 合同的总产品额
     * @param bool   $is_string    是否拼接比例与金额（用 / 号拼接）
     * @return array|bool
     */
    public function compactPaymentPlanByRatio($ratio, $productMoney,$is_string = false){
        $_ratio = explode('+', $ratio);

        if(count($_ratio) < 1 or count($_ratio) > 3){
            return false;
        }else{
            $total_percent = 0;
            $total_percent += intval(isset($_ratio[0])?$_ratio[0]:0);
            $total_percent += intval(isset($_ratio[1])?$_ratio[1]:0);
            $total_percent += intval(isset($_ratio[2])?$_ratio[2]:0);
            if(intval($total_percent) != 100){
                return false;
            }
        }

        if(count($_ratio) == 1 or intval($_ratio) === 100){
            $firstRatio      = is_array($_ratio)?$_ratio[0]:$_ratio;
            $firstRatioMoney = format_two_point_price($productMoney);// 产品总额

            $result = [
                $firstRatio => $firstRatioMoney,
            ];
        }elseif(count($_ratio) == 2){
            $firstRatio      = $_ratio[0];
            $lastRatio       = $_ratio[1];
            $firstRatioMoney = format_two_point_price(($productMoney * (int)$firstRatio) / 100);// 产品总额*首款百分比
            $lastRatioMoney  = format_two_point_price($productMoney - $firstRatioMoney); // 尾款

            $result = [
                $firstRatio => $firstRatioMoney,
                $lastRatio  => $lastRatioMoney,
            ];
        }else{
            $firstRatio       = $_ratio[0];
            $secondRatio      = $_ratio[1];
            $lastRatio        = $_ratio[2];
            $firstRatioMoney  = format_two_point_price(($productMoney * (int)$firstRatio) / 100);// 产品总额*首款百分比
            $secondRatioMoney = format_two_point_price(($productMoney * (int)$secondRatio) / 100);// 中款
            $lastRatioMoney   = format_two_point_price($productMoney - $firstRatioMoney - $secondRatioMoney); // 尾款（确保  首款 + 中款 + 尾款 = 产品总额）

            $result = [
                $firstRatio  => $firstRatioMoney,
                $secondRatio => $secondRatioMoney,
                $lastRatio   => $lastRatioMoney,
            ];
        }

        if($is_string and $result){
            $result_tmp = [];
            foreach($result as $key_ratio => $value_money){
                $result_tmp[] = $key_ratio .'/'. $value_money;
            }
            $result = $result_tmp;
        }

        return $result;
    }


    /**
     * 数字的金额转成中文字符串
     * @author Jolon
     * @param  float $num 金额 （只支持3位小数，最大 9999999.999）
     * @return string|bool
     */
    public function numberPriceToCname($num){
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "厘分角元拾佰仟万拾佰仟亿";

        $num = round($num * 1000, 0);// 将数字转化为整数，去掉小数点后面的数据
        if(strlen($num) > 10){
            return "金额太大，请检查";
        }

        $i = 0;
        $c = "";
        while(1){
            if($i == 0){
                // 获取最后一位数字
                $n = substr($num, strlen($num) - 1, 1);
            }else{
                $n = $num % 10;
            }
            // 每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))){
                $c = $p1.$p2.$c;
            }else{
                $c = $p1.$c;
            }
            $i = $i + 1;
            // 去掉数字最后一位了
            $num = $num / 10;
            $num = (int)$num;
            // 结束循环
            if($num == 0){
                break;
            }
        }
        $j    = 0;
        $slen = strlen($c);
        while($j < $slen){
            // utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            // 处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零'){
                $left  = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c     = $left.$right;
                $j     = $j - 3;
                $slen  = $slen - 3;
            }
            $j = $j + 3;
        }
        // 这个是为了去掉类似23.0中最后一个“零”字
        if(substr($c, strlen($c) - 3, 3) == '零'){
            $c = substr($c, 0, strlen($c) - 3);
        }
        // 将处理的汉字加上“整”
        if(empty($c)){
            return "零元";
        }else{
            if(preg_match('/分|角|厘/', $c)){
                return $c;
            }else{
                return $c."整";
            }
        }
    }

    /**
     * 采购金额（运费或优惠额） 按 SKU 数量比重分摊到每个 采购单和SKU 上
     * @author Jolon
     * @param float $total_amount           总金额
     * @param array $purchase_sku_qty_list  采购单和SKU
     * @return array|bool
     * @example
     *    参数  $purchase_sku_qty_list
     *                = array(
     *                  'purchase_number1' => array(
     *                       'sku_1' => '数量_1',
     *                       'sku_2' => '数量_2',
     *                   ),
     *                  'purchase_number2' => array(
     *                       'sku_1' => '数量_1',
     *                       'sku_2' => '数量_2',
     *                   )
     *               )
     *  分摊结果 $average_distribute_result =
     *                = array(
     *                  'purchase_number1' => array(
     *                       'sku_1' => '10.423',
     *                       'sku_2' => '23.577',
     *                   ),
     *                  'purchase_number2' => array(
     *                       'sku_1' => '10.423',
     *                       'sku_2' => '23.577',
     *                   )
     *               )
     */
    public function amountAverageDistribute($total_amount,$purchase_sku_qty_list){
        $total_sku_species  = 0;// SKU 种类
        $total_sku_qty      = 0;// SKU 总数量

        if(empty($purchase_sku_qty_list) or !is_array($purchase_sku_qty_list)) return false;// 参数错误
        foreach($purchase_sku_qty_list as $purchase_number => $sku_list){
            if(empty($sku_list) or !is_array($sku_list)) return false;// 参数错误
            foreach($sku_list as $sku => $sku_qty){
                $total_sku_species  ++;
                $total_sku_qty      += $sku_qty;
            }
        }
        if(empty($total_sku_qty) or $total_sku_qty <= 0) return false;// 参数错误

        $average_price = round( $total_amount / $total_sku_qty ,5);// 计算 每个SKU 的平均金额

        $distribute_price_total     = 0.0;// 已分摊的金额
        $average_distribute_result  = [];// 采购单 SKU 金额分摊结果

        $last_purchase_number       = $last_sku = null;// 最后一个采购单和SKU 用来缓冲 金额偏差
        foreach($purchase_sku_qty_list as $purchase_number => $sku_list){
            foreach($sku_list as $sku => $sku_qty){
                $distribute_price_item  = format_two_point_price($average_price * $sku_qty);// 三位小数
                $distribute_price_total += $distribute_price_item;
                $average_distribute_result[$purchase_number][$sku] = $distribute_price_item;// 采购单-SKU 所占的金额

                // 最后一个采购单和SKU
                $last_purchase_number = $purchase_number;
                $last_sku             = $sku;
            }
        }

        // 判断 总分摊的金额 是否 和 总金额相等（实际上是三位小数，这里比较到四位）
        if(bccomp($total_amount,$distribute_price_total,4)){
            $distribute_price_diff = $distribute_price_total - $total_amount;

            $average_distribute_result[$last_purchase_number][$last_sku] -= $distribute_price_diff;// 最后一个采购单和SKU缓冲 金额偏差
        }

        return $average_distribute_result;
    }

    /**
     * 损益值 按 比例分摊到 指定数组集上（最后一个做减法）
     * @author Jolon
     * @param float $change_amount          损益值
     * @param array $apportion_list         键名和键值
     * @param string $sort                  对 $apportion_list 的排序，ASC 升序，DESC 降序
     * @return array|bool
     * @example
     *    参数  $apportion_list
     *                = array(
     *                  'key1' => 10,
     *                  'key2' => 30
     *               )
     *  分摊结果 $apportion_list =
     *                = array(
     *                  'key1' => 11,
     *                  'key2' => 33
     *               )
     */
    public function apportion_change_amount($change_amount, $apportion_list = [], $sort = null){
        $sum_count  = count($apportion_list);// 元素总个数
        $sum_amount = array_sum($apportion_list);// 元素值之和

        // 排序
        if(strtoupper($sort) == 'ASC') asort($apportion_list);
        elseif(strtoupper($sort) == 'DESC') arsort($apportion_list);

        $apportion_change_total = 0;// 总 已分配的数量
        $apportion_list_new     = [];// 新结果集
        foreach($apportion_list as $key => $value){
            $now_change               = intval($change_amount * ($value / $sum_amount));// 向下取整：取整数 舍去小数位
            if(count($apportion_list_new) == $sum_count - 1){
                $apportion_list_new[$key] = $value + ($change_amount - $apportion_change_total);// 最后一个做 减法
            }else{
                $apportion_list_new[$key] = $value + $now_change;
            }

            $apportion_change_total   += $now_change;
        }

        return $apportion_list_new;
    }


}