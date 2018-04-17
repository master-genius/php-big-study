<?php
function is_ind($a) {
    if (is_array($a)) {
        $keys = array_keys($a);
        return ($keys!==array_keys($keys));
    }
    return false;
}

function json_num_str($v)
{ 
    //转换" -> \"  \ -> \\ ，但是 ' 不能是 \' 要变成 ',object直接转换成{}
    return (is_object($v)?'{},':
                (is_numeric($v)?($v . ','):
                    ('"' . str_replace("\\'","'",addslashes($v))  . '",')
                )
           );
}

function arr_to_json($arr)
{
    $ii = is_ind($arr);
    $json = ($ii?'{':'[');
    if ($ii) {
        foreach($arr as $k=>$v) {
            if(is_array($v)) {
                $json .= '"'.$k.'":' . arr_to_json($v) . ',';
            } else {
                $json .= '"' . $k . '":' . json_num_str($v);
            }
        }
    }
    else{
        foreach($arr as $v) {
            $json .= (is_array($v)?(arr_to_json($v) . ','):json_num_str($v));
        }
    }
    return rtrim($json,',') . ($ii?'}':']');
}

/*
   start test
*/
$a = [
    'a' => 'abc',
    'b' => 123,
    'c' => [
        1,2,3
    ]
];

$b = [1,2,3,4];

$c = [
    'name'=>'BraveWang',
    'age' => 28,
    'skill' => [
        'Linux','C','PHP','Python','Shell Script','MySQL','Nginx'
    ]
];

$d = [
    'sdf' => [
        '"sdf"sdf"','\'sdfewer\''
    ],
    'dch' => [
        ":sdf:\"",':,\\'
    ]
];

$more = [
    100 => [
        'a','b',12,344,5,6
    ],
    'list' => [
        ['id'=>12,'name'=>'dwoerj'],
        ['id'=>13,'name'=>'dw456y'],
        ['id'=>14,'name'=>'dwtreg'],
        ['id'=>15,'name'=>'dw345t'],
    ],
    'h' => [
        'a' => [1,2,34],
        'b' => [
            'vh' => 12,
            'sdf' => [
                2,3,4,5
            ]
        ]
    ],
    [
        ['abc','bcd','cde'],
        ['xyz','aui','qwe']
    ]
];

$oa = [
    'redis' => (new Redis()),
    'memcache' => (new Memcached())
];

$aset = [$a,$b,$c,$d,$more,$oa];

foreach ($aset as $ar) {
    echo arr_to_json($ar),"\n";
    echo json_encode($ar),"\n\n";
}

try{
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=hzwjsmall','master','master2018');
    $sql = "select goods_id,goods_serial,goods_name,goods_price".
            " from sh_goods limit 0,10";
    $ph = $pdo->query($sql);
    $goods_list = $ph->fetchAll(PDO::FETCH_ASSOC);
    $json_goods = arr_to_json($goods_list);
    echo $json_goods,"\n";
} catch (Exception $e) {
    exit($e->getMessage());
}

