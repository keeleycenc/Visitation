<?php
/**
* @author Keeleycenc
* @param  none
* @version 1.0
* @link   https://keeleycenc.com
*/

// 连接到数据库
$servername = "localhost";
$username = "用户名";
$password = "密码";
$dbname = "数据库名";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("数据库连接失败：" . $conn->connect_error);
}

// 创建新表保存计算结果，仅在第一次运行时创建
createResultTable($conn);

// 记录访问信息
recordVisit($conn);

function createResultTable($conn) {
    // SQL语句，用于创建result表
    $sql = "CREATE TABLE IF NOT EXISTS visitors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        time DATETIME,
        ip VARCHAR(255),
        address VARCHAR(255)
    )";

    // 执行SQL语句
    if ($conn->query($sql) !== TRUE) {
        echo "创建result表时出错：" . $conn->error;
    }
}


function recordVisit($conn) {
    // 获取访问时间和IP地址
    $time = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];

    // 使用IP地址查询接口获取地址信息
    $address = getAddressByIP($ip);

    // 将访问信息和地址信息插入数据库
    $sql = "INSERT INTO visitors (time, ip, address) VALUES ('$time', '$ip', '$address')";
    if ($conn->query($sql) !== TRUE) {
        echo "记录访问信息时出错：" . $conn->error;
    }
}

function getAddressByIP($ip) {
    // 使用IP地址查询服务接口获取地址信息，这里以ip-api.com为例
    $url = 'http://ip-api.com/json/' . $ip;
    $response = file_get_contents($url);
    $result = json_decode($response);

    // 解析API返回的数据，获取地址信息
    if ($result && $result->status === 'success') {
        $address = $result->country . ', ' . $result->regionName . ', ' . $result->city;
        return $address;
    } else {
        return '未知';
    }
}

header('Content-Type: application/json');
echo json_encode($response);
// 关闭数据库连接
$conn->close();
?>
