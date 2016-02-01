<?php

if (!function_exists('trees')) {
    /**
     * 输出树状数组.
     *
     * @param array $data  传入数组
     * @param int   $pid   父亲级别id
     * @param int   $level 分类层级
     *
     * @return [type] [description]
     */
    function trees(array $data, $pid = 0, $level = 0)
    {
        ++$level;
        $subs = [];
        foreach ($data as $key => $value) {
            // 如果当前值数组属于父级别就开始合并函数
            if ($value['pid'] == $pid) {
                // 将父亲自己函数压入父亲级别后面
                $value['level'] = $level;
                $subs[]         = $value;
                $subs           = array_merge($subs, trees($data, $value['id'], $level));
            }
        }

        return $subs;
    }
}

/*
 * 传入数据为数组
 */
$arr = [
    ['id' => '1', 'name' => '父亲级别1', 'pid' => 0],
    ['id' => '2', 'name' => '父亲级别1的子级别1', 'pid' => 1],
    ['id' => '3', 'name' => '父亲级别1的子级别2', 'pid' => 1],
    ['id' => '4', 'name' => '父亲级别1的子级别2的子级别1', 'pid' => 3],
];

print_r(trees($arr));
/*
 Array
(
    [0] => Array
        (
            [id] => 1
            [name] => 父亲级别1
            [pid] => 0
            [level] => 1
        )

    [1] => Array
        (
            [id] => 2
            [name] => 父亲级别1的子级别1
            [pid] => 1
            [level] => 2
        )

    [2] => Array
        (
            [id] => 3
            [name] => 父亲级别1的子级别2
            [pid] => 1
            [level] => 2
        )

    [3] => Array
        (
            [id] => 4
            [name] => 父亲级别1的子级别2的子级别1
            [pid] => 3
            [level] => 3
        )

)
 */
