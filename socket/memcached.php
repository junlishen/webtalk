<?php
header("Content-Type:text/html;charset=utf-8");
//连接
$mem = new Memcache;
$mem->connect("127.0.0.1", 11211) or die ("Could not connect");

//保存数据
/*$mem->set('key1', 'This is first value', 0, 60);*/
$val = $mem->get('key1');
echo "Get key1 value: " . $val ."<br>";

//替换数据
/*$mem->replace('key1', 'This is replace value', 0, 60);
$val = $mem->get('key1');
echo "Get key1 value: " . $val . "<br>";*/

//保存数组
/*$arr = array('aaa', 'bbb', 'ccc', 'ddd');
$mem->set('key2', $arr, 0, 60);*/
$val2 = $mem->get('key2');
echo "Get key2 value: ";
print_r($val2);
echo "<br>";

//删除数据
/*$mem->delete('key1');
$val = $mem->get('key1');
echo "Get key1 value: " . $val . "<br>";*/

//清除所有数据
/*$mem->flush();
$val2 = $mem->get('key2');
echo "Get key2 value: ";
print_r($val2);
echo "<br>";*/

//关闭连接
$mem->close();
?>