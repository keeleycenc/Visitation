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

// 查询访问记录的行数
$sql = "SELECT COUNT(*) AS count FROM visitors";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$visitCount = $row['count'];

// 增加访问总数
$visitCount++;

// 输出访问总数
echo '访问量：'.$visitCount;

$conn->close();
?>
