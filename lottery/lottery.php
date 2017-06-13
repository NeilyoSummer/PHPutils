<?php
/* 
百分百中奖
活动规定：
    用户后台设定是百分百中奖的，意思就是玩儿十次可以中奖两次
    但是这两次百分百中奖的机会，实在十次中均匀分布的
    
    假设活动抽奖次数：10次 | 中奖次数：2次

    配置：
        100% 中奖策略
            [
                ['id' => 1, 'name' => '一等奖', 'probability' => 5],
                ['id' => 2, 'name' => '二等奖', 'probability' => 10],
                ['id' => 3, 'name' => '三等奖', 'probability' => 20],
                ['id' => 4, 'name' => '四等奖', 'probability' => 30],
                ['id' => 5, 'name' => '五等奖', 'probability' => 40],
                ['id' => 6, 'name' => '六等奖', 'probability' => 50],
            ]
        正常中奖策略
            [
                ['id' => 1, 'name' => '一等奖', 'probability' => 5],
                ['id' => 2, 'name' => '二等奖', 'probability' => 10],
                ['id' => 3, 'name' => '三等奖', 'probability' => 10],
                ['id' => 4, 'name' => '四等奖', 'probability' => 10],
                ['id' => 5, 'name' => '五等奖', 'probability' => 10],
                ['id' => 6, 'name' => '六等奖', 'probability' => 10],
                ['id' => 7, 'name' => '无奖品', 'probability' => 50],
            ]
    输入：
        抽奖次数
        中奖次数
        用户已抽奖次数
        用户已中奖次数
        

    输出：
        检索用户抽奖次数 | 中奖次数
            抽奖次数
                抽奖次数有：
                    开始读中奖次数
                    中奖次数有：
                        随机分布在接下来的中间概率中随机分布
                    中奖次数没有：
                        标记不中奖
                抽奖次数没有：
                    提示超过抽奖次数

    测试：
        活动设置抽奖次数 10 中奖次数 2
        用户当前抽奖次数  0 中奖次数 0
        结果，100% 中奖必定是两次
*/

/**
 * 中奖算法类
 */
class Lucky
{
    // 百分百中奖策略
    public $rules_perfect = [];
    // 正常中奖策略
    public $rules_normal = [];
    // 100%中奖队列
    public $bingo_queue = [];
    // 活动配置的抽奖次数
    public $lottery_times = 0;
    // 活动配置的中奖次数
    public $bingo_times = 0;
    // 当前用户的抽奖次数
    public $user_lottery_times = 0;
    // 当前用户的中奖次数
    public $user_bingo_times = 0;

    public function __construct($lottery_times = 10, $bingo_times = 2, $user_lottery_times = 0, $user_bingo_times = 0)
    {
        $this->setLotteryTimes($lottery_times);
        $this->setBingoTimes($bingo_times);
        $this->setUserLotteryTimes($user_lottery_times);
        $this->setUserBingoTimes($user_bingo_times);

        // 数据库读取抽奖次数
        $current_lottery_times = $this->getLotteryTimes() - $this->getUserLotteryTimes();
        $current_bingo_times = $this->getBingoTimes() - $this->getUserBingoTimes();

        for ($i = 0; $i < $current_lottery_times; $i++) {
            if ($current_bingo_times > 0) {
                $this->bingo_queue[] = 100;
                $current_bingo_times--;
            } else {
                $this->bingo_queue[] = 0;
            }
        }

        shuffle($this->bingo_queue);
    }

    // 开始100% 中奖
    public function get_perfect_prize()
    {
        $result = [];
        $current_bingo_times = $this->getBingoTimes() - $this->getUserBingoTimes();

        if ($current_bingo_times <= 0) {
            // 返回不中奖数据
            return ['id' => 7, 'name' => '无奖品', 'probability' => 100];
        }

        // 开始正常的中奖逻辑
        $total_probability = $this->getPerfectTotalProbability();

        foreach ($this->getRulesPerfect() as $value) {
            $randNum = mt_rand(1, $total_probability);

            if ($randNum <= $value['probability']) {
                $result = $value;
                break;
            } else {
                $total_probability -= $value['probability'];
            }
        }

        return $result;
    }

    // 正常中奖概率
    public function get_normal_prize()
    {
        $result = [];
        $current_bingo_times = $this->getBingoTimes() - $this->getUserBingoTimes();

        if ($current_bingo_times <= 0) {
            // 返回不中奖数据
            return $this->getRulesNormal()[count($this->getRulesNormal()) - 1];
        }

        // 开始正常的中奖逻辑
        $total_probability = $this->getNormalTotalProbability();

        foreach ($this->getRulesNormal() as $value) {
            $randNum = mt_rand(1, $total_probability);

            if ($randNum <= $value['probability']) {
                $result = $value;
                break;
            } else {
                $total_probability -= $value['probability'];
            }
        }

        return $result;
    }

    public function run()
    {
        $current = array_shift($this->bingo_queue);

        if ($current == 100) {
            $result = $this->get_perfect_prize();
        } else {
            $result = $this->get_normal_prize();
        }

        return $result;
    }

    // 获取100%中奖总概率
    public function getPerfectTotalProbability()
    {
        $total = 0;
        foreach ($this->getRulesPerfect() as $value) {
            $total += $value['probability'];
        }

        return $total;
    }

    // 获取普通抽奖总概率
    public function getNormalTotalProbability()
    {
        $total = 0;
        foreach ($this->getRulesNormal() as $value) {
            $total += $value['probability'];
        }

        return $total;
    }

    /**
     * @return array
     */
    public function getRulesPerfect()
    {
        return $this->rules_perfect;
    }

    /**
     * @param array $rules_perfect
     * @return Lucky
     */
    public function setRulesPerfect($rules_perfect)
    {
        $this->rules_perfect = $rules_perfect;
        return $this;
    }

    /**
     * @return array
     */
    public function getRulesNormal()
    {
        return $this->rules_normal;
    }

    /**
     * @param array $rules_normal
     * @return Lucky
     */
    public function setRulesNormal($rules_normal)
    {
        $this->rules_normal = $rules_normal;
        return $this;
    }

    /**
     * @return int
     */
    public function getLotteryTimes()
    {
        return $this->lottery_times;
    }

    /**
     * @param int $lottery_times
     * @return Lucky
     */
    public function setLotteryTimes($lottery_times)
    {
        $this->lottery_times = $lottery_times;
        return $this;
    }

    /**
     * @return int
     */
    public function getBingoTimes()
    {
        return $this->bingo_times;
    }

    /**
     * @param int $bingo_times
     * @return Lucky
     */
    public function setBingoTimes($bingo_times)
    {
        $this->bingo_times = $bingo_times;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserLotteryTimes()
    {
        return $this->user_lottery_times;
    }

    /**
     * @param int $user_lottery_times
     * @return Lucky
     */
    public function setUserLotteryTimes($user_lottery_times)
    {
        $this->user_lottery_times = $user_lottery_times;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserBingoTimes()
    {
        return $this->user_bingo_times;
    }

    /**
     * @param int $user_bingo_times
     * @return Lucky
     */
    public function setUserBingoTimes($user_bingo_times)
    {
        $this->user_bingo_times = $user_bingo_times;
        return $this;
    }
}

$j = 0;

for($i =1; $i <=10; $i++) {
    $lucky = new Lucky(10, 2, $i, $j);
    $lucky->setRulesPerfect([
        ['id' => 1, 'name' => '一等奖', 'probability' => 5],
        ['id' => 2, 'name' => '二等奖', 'probability' => 10],
        ['id' => 3, 'name' => '三等奖', 'probability' => 10],
        ['id' => 4, 'name' => '四等奖', 'probability' => 20],
        ['id' => 5, 'name' => '五等奖', 'probability' => 30],
        ['id' => 6, 'name' => '六等奖', 'probability' => 44],
    ]);

    $lucky->setRulesNormal([
        ['id' => 1, 'name' => '一等奖', 'probability' => 5],
        ['id' => 2, 'name' => '二等奖', 'probability' => 10],
        ['id' => 3, 'name' => '三等奖', 'probability' => 10],
        ['id' => 4, 'name' => '四等奖', 'probability' => 10],
        ['id' => 5, 'name' => '五等奖', 'probability' => 10],
        ['id' => 6, 'name' => '六等奖', 'probability' => 10],
        ['id' => 7, 'name' => '无奖品', 'probability' => 50],
    ]);
    $result = $lucky->run();
    if ($result['id'] != 7) {
        echo '中奖';
        $j++;
    }
    print_r($result);
}

