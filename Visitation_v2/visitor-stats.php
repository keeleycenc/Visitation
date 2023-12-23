<?php
/**
* @author Keeleycenc
* @param  none
* @version 2.1
* @link   https://keeleycenc.com
*/

// 连接数据库
$servername = "localhost";
$username = "用户名";
$password = "密码";
$dbname = "数据库名";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentDateTime = date("Y-m-d H:i:s"); // 设置当前日期和时间

// 检查并更新表结构
$table = "CREATE TABLE IF NOT EXISTS visitor_count (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- 主键，自增，用于唯一标识每条记录
    ip VARCHAR(50),                                -- 访问者的IP地址
    location VARCHAR(100),                         -- 访问者的地理位置
    device VARCHAR(255),                           -- 访问者使用的设备信息
    visits INT(30) NOT NULL,                       -- 当天该IP和设备的访问次数
    date DATE NOT NULL,                            -- 记录的日期
    total_visits INT(11) NOT NULL DEFAULT 1,       -- 该IP和设备的总访问次数，默认为1
    first_visit_time DATETIME NULL,                -- 该IP和设备的首次访问时间
    last_visit_time DATETIME NULL,                 -- 该IP和设备的最后访问时间
    UNIQUE KEY unique_visit (ip, device, date)     -- 组合键，保证同一IP和设备在同一天的记录是唯一的
)";

if ($conn->query($table) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// 检查并添加daily_order列
$checkColumn = "SHOW COLUMNS FROM visitor_count LIKE 'daily_order'";
$columnResult = $conn->query($checkColumn);

if ($columnResult->num_rows == 0) {
    // 如果daily_order列不存在，则添加它
    $alterTable = "ALTER TABLE visitor_count ADD daily_order INT(6) UNSIGNED AFTER date";
    $conn->query($alterTable);
}

$conn->query($table);

// 获取用户IP和设备信息
$user_IpAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];
$userAgent = preg_replace('/\s+/', ' ', $userAgent); // 替换所有空格为标准空格

// IP归属地查询
function getIpLocation($ip) {
    // 构建查询的URL
    $apiUrl = 'http://ip.plyz.net/ip.ashx?ip=' . urlencode($ip);

    // 初始化cURL会话
    $curl = curl_init();
    // 设置cURL选项
    curl_setopt($curl, CURLOPT_URL, $apiUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 100);  // 设置超时时间（可根据需要调整）

    // 执行cURL会话
    $response = curl_exec($curl);

    // 检查是否有错误发生
    if(curl_errno($curl)){
        curl_close($curl);
        return '未知归属地'; // 在出错时返回默认值
    }

    curl_close($curl);

    // 解析响应
    if ($response) {
        // 假设响应的格式是 "IP|归属地"，以竖线分隔
        $parts = explode('|', $response);            
        if (count($parts) >= 2) {
            // 进一步解析归属地信息，假设格式是 "国家 省份 城市 运营商"
            $locationParts = explode(' ', $parts[1]);
            if (count($locationParts) >= 3) {
                // 如果有省份和城市
                return $locationParts[1] . ' ' . $locationParts[2]; // 返回省份和城市部分
            } elseif (count($locationParts) == 2) {
                // 只有国家和省份
                return $locationParts[1]; // 只返回省份部分
            } else {
                return '未知归属地'; // 如果无法解析省份，则返回默认值
            }
        } else {
            return '未知归属地'; // 如果无法解析响应，则返回默认值
        }
    } else {
        return '未知归属地'; // 如果无法获取响应，则返回默认值
    }
}


$userLocation = getIpLocation($user_IpAddress);

// 计算当天的访客顺序
function calculateDailyOrder($date) {
    global $conn;
    $query = "SELECT MAX(daily_order) as maxOrder FROM visitor_count WHERE date='$date'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return ($row["maxOrder"] + 1);
}

// 检查今天此IP和设备的访问记录
$date = date("Y-m-d");
$checkQuery = "SELECT daily_order, total_visits FROM visitor_count WHERE ip='$user_IpAddress' AND device='$userAgent' AND date='$date'";
$checkResult = $conn->query($checkQuery);

$isNewVisitor = false; // 新增变量用于标识是否为新访客

if ($checkResult->num_rows == 0) {
    // 新访客逻辑
    $dailyOrder = calculateDailyOrder($date);
    $insert = "INSERT INTO visitor_count (ip, location, device, visits, date, daily_order, first_visit_time, last_visit_time) VALUES ('$user_IpAddress', '$userLocation', '$userAgent', 1, '$date', '$dailyOrder', '$currentDateTime', '$currentDateTime')";
    $conn->query($insert);
    $isNewVisitor = true; // 标记为新访客
} else {
    // 不是新访客，更新现有记录
    $row = $checkResult->fetch_assoc();
    $dailyOrder = $row["daily_order"];
    $currentTotalVisits = $row["total_visits"]; // 获取当前的 total_visits 值
    $newTotalVisits = $currentTotalVisits + 1;  // 增加1

    // 更新数据库中的 total_visits 和 last_visit_time 值
    $updateQuery = "UPDATE visitor_count SET total_visits=$newTotalVisits, last_visit_time='$currentDateTime' WHERE ip='$user_IpAddress' AND device='$userAgent' AND date='$date'";
    $conn->query($updateQuery);
}


// 获取统计数据
function getVisits($date) {
    global $conn;
    $query = "SELECT SUM(visits) as totalVisits FROM visitor_count WHERE date='$date'";
    $result = $conn->query($query);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["totalVisits"];
    } else {
        return 0;
    }
}

// 获取今日、昨日和总访问量
$todayVisits = getVisits(date("Y-m-d"));
$yesterdayVisits = getVisits(date("Y-m-d", strtotime("-1 day")));
$totalResult = $conn->query("SELECT SUM(visits) AS totalVisits FROM visitor_count");
$totalRow = $totalResult->fetch_assoc();
$totalVisits = $totalRow["totalVisits"];

// 返回JSON格式的数据
echo json_encode(array(
    "daily_order" => $dailyOrder, 
    "today" => $todayVisits, 
    "yesterday" => $yesterdayVisits, 
    "total" => $totalVisits,
    "location" => $userLocation
));


$conn->close();
?>

