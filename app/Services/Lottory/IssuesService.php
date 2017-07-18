<?php

namespace App\Services\Lottory;

use Exception;
use App\Exceptions\Handler;
use App\Exceptions\IssuesException;
use App\Models\Lottory as LotteryModel;
use App\Models\Issues as IssueModel;
use Illuminate\Support\Facades\DB;

/**
 *   彩票判奖过滤服务
 *   大小单双  【1：小 2：大 3：单 4：双】
 *   龙虎和    【1：龙 2：虎 0：和】
 *   @auther logen
 */
class IssuesService
{

    function __construct()
    {

    }

    /**
     * 奖期批量生成
     * @param type $lottery
     * @param type $data
     * @return int
     * @throws IssuesException
     */
    static public function genIssue($lottery, $data)
    {
        $firstIssue = $data['firstIssue'];
        $startTS = strtotime($data['startDate']);
        $endTS = strtotime($data['endDate']);

        //常规判断
        if( $startTS > $endTS || $endTS - $startTS > 86400 * 365 )
        {
            throw new IssuesException(50002);
        }
        if( !$lottery )
        {
            throw new IssuesException(50001);
        }

        // 判断是否需要起始期号
        if( strpos($lottery->issue_rule, 'd') === false )
        {
            if( !$firstIssue )
            {
                throw new IssuesException(50003);
            }
            if( !issues::checkIssueRule($firstIssue, $lottery->issue_rule) )
            {
                throw new IssuesException(50004);
            }
        }

        //删除可能有的重复奖期先
        self::deleteItemByDate($lottery->lottery_id, $startTS);
        /**
         *
         * CQSSC:   100121-054       ymd-[n3]
         * JX-SSC:  20100121-036    Ymd-[n3]
         * HLJSSC:  0016571         [n7]
         * SSL:     20100121-11     Ymd-[n2]
         * SD11Y:   10012131        ymd-[n2]
         * 格式符： y,m,d的值分别为0,1,0，0表示清零，1表示不清零
         */
        $rules = self::analyze($lottery->issue_rule);
        var_dump($rules);die;
        $totalCounter = 0;
        $curIssueNumber = intval(substr($firstIssue, 0 - $rules['n'])); // 获取期号，一般在最后几位
        for( $i = $startTS; $i <= $endTS; $i += 86400 ) {
            //休市则忽略
            if( $i >= strtotime($lottery->yearly_start_closed) && $i <= strtotime($lottery->yearly_end_closed) )
            {
                continue;
            }

            $belong_date = date('Y-m-d', $i);    // 属于哪天的奖期
            $sample = $rules['sample'];
            // 先替换日期大部
            if( $rules['ymd'] )
            {
                $sample = preg_replace('`([ymd]+)`ie', "date('\\1', $i)", $sample);
            }
            // 得到当前期号$curIssue
            if( $rules['n'] )
            {
                // 如果按天清零，则每天数字部分从头开始
                if( !$rules['d'] )
                {
                    $curIssueNumber = 1;
                }
                //按年清零的时候跨年了
                if( !$rules['y'] && isset($lastDay) && date('Y', $i) > date('Y', $lastDay) )
                {
                    $curIssueNumber = 1;
                }
            }
            // 开始生成
            /*
             *     [0] => Array
              (
              [start_time] => 05:00:00
              [end_time] => 09:58:30
              [cycle] => 10
              [end_sale] => 60
              [code_time] => 120
              [drop_time] => 60
              )
             */
            $datas = array();
            $settings = json_decode($lottery['settings'],true);
            foreach( $settings as $v ) {
                if( !$v['is_use'] )
                {
                    continue;
                }
                $startTime = time2second($v['start_time']);
                $endTime = time2second($v['end_time']);
                $isFirst = 0;
                if( $endTime < $startTime )
                {
                    $endTime += 86400;
                }

                for( $j = $startTime; $j <= $endTime - $v['cycle']; ) {
                    $curIssueStartTime = date('Y-m-d H:i:s', $i + $j - $v['end_sale']);
                    if( !$isFirst )
                    {
                        $curIssueEndTimeStamp = $i + time2second($v['first_end_time']);
                    }
                    else
                    {
                        $curIssueEndTimeStamp = $i + $j + $v['cycle'];
                    }
                    $curIssueEndTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp - $v['end_sale']);
                    $curDropTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp - $v['drop_time']);
                    $curCodeTime = date('Y-m-d H:i:s', $curIssueEndTimeStamp + $v['code_time']);
                    $finalIssue = str_replace("[n{$rules['n']}]", str_pad($curIssueNumber, $rules['n'], '0', STR_PAD_LEFT), $sample);

                    // 写入
                    $data = array(
                        'lottery_id' => $lotteryId,
                        'belong_date' => $belong_date,
                        'issue' => $finalIssue,
                        'start_sale_time' => $curIssueStartTime,
                        'end_sale_time' => $curIssueEndTime,
                        'cannel_deadline_time' => $curDropTime,
                        'earliest_input_time' => $curCodeTime,
                    );
                    $datas[] = $data;
                    if( !$isFirst )
                    {
                        $j = strtotime($v['first_end_time']);
                    }
                    else
                    {
                        $j += $v['cycle'];
                    }
                    $isFirst++;
                    $curIssueNumber++;
                    $totalCounter++;
                }
            }
            //批量添加
            $sql = 'INSERT INTO issues (' . implode(',', array_keys(reset($datas))) . ') VALUES ';
            foreach( $datas as $v ) {
                $sql .= "('" . implode("','", $v) . "'),";
            }
            $sql = rtrim($sql, ',');

            if( !DB::insert($sql) )
            {
                throw new IssuesException(50005);
            }

            //记录前一天 为年清零之用
            $lastDay = $i;
        }

        return $totalCounter;
    }

    //删除日期之后的奖期 批量生成奖期时使用
    static private function deleteItemByDate($lotteryId, $belong_date = 0, $start_sale_time = 0)
    {
        if( $lotteryId <= 0 )
        {
            throw new IssuesException(50006);
        }
        if( !$belong_date && !$start_sale_time )
        {
            throw new IssuesException(50007);
        }
        IssueModel::where('lottery_id', $lotteryId)
                ->where('belong_data', '>=', $belong_date)
                ->where('start_sale_time', '>=', $start_sale_time)
                ->save();
//        $sql = 'DELETE FROM issues WHERE lottery_id = ' . intval($lotteryId);
//        if( $belong_date )
//        {
//            $sql .= ' AND belong_date >= "' . date('Y-m-d', $belong_date) . '"';
//        }
//        if( $start_sale_time )
//        {
//            $sql .= ' AND start_sale_time >= "' . date('Y-m-d H:i:s', $start_sale_time) . '"';
//        }
    }

    /**
     * 私有方法 分析奖期规则
     * @param type $issuerule
     * @return type
     */
    static private function analyze($issuerule)
    {
        $tmp = explode('|', $issuerule);
        $result['sample'] = $tmp[0];
        $result['ymd'] = '';
        $result['n'] = 0;
        preg_match_all('`\[(n)(\d+)\]`', $tmp[0], $matches);

        if( $matches[1] )
        {
            $result['n'] = $matches[2][0];
        }

        if( preg_match('`^[yY][md]*`i', $tmp[0], $match) )
        {
            $result['ymd'] = $match[0];
        }

        $result['ymd_length'] = strlen(date($result['ymd']));
        $result['length'] = $result['n'];

        if( $result['ymd'] )
        {
            $result['length'] += $result['ymd_length'];
        }
        $result['length'] += strlen(preg_replace(array('`^[yY][md]*`i', '`\[(n)(\d+)\]`i'), '', $result['sample']));

        $tmp3 = explode(',', $tmp[1]);
        $result['y'] = $tmp3[0] ? true : false;
        $result['m'] = $tmp3[1] ? true : false;
        $result['d'] = $tmp3[2] ? true : false;

        return $result;
    }

}
