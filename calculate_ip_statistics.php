<!-- /**
* @author Keeleycenc
* @param  none
* @version 1.2
* @link   https://keeleycenc.com
*/ -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LightweightTracker</title>
<style>
      h1 {
        color: #e74c3c;
        border-bottom: 2px solid #e74c3c;
        padding-bottom: 10px;
        margin-bottom: 20px;
        font-size: 14px;
    }
      h2 {
        color: #3498db;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
        margin-bottom: 20px;
        font-size: 14px;
    }
    code {
        display: block;
        white-space: pre-wrap;
        background-color: #373b3d;
        border: 1px solid #2c3032;
        padding: 20px;
        overflow-x: auto;
        line-height: 1.6;
        font-size: 14px;
        color: #d4d4d4;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    code span {
        display: block;
        padding: 5px 0;
    }
    .success {
        color: #5cb85c;
        font-weight: bold;
    } 
    .error {
        color: #d9534f;
        font-weight: bold;
    }
    .pagination {
        text-align: center;
        margin-top: 20px;
    }
    .pagination a, .pagination span {
        display: inline-block;
        padding: 5px 10px;
        margin-right: 5px;
        border: 1px solid #ddd;
        color: #337ab7;
        text-decoration: none;
    }
    .pagination span.current-page {
        font-weight: bold;
        color: #fff;
        background-color: #337ab7;
        border-color: #337ab7;
    }
</style>
</head>

<body>
    <code>
        <?php
        // 数据库连接信息
        $servername = "localhost";
        $username = "用户名";
        $password = "密码";
        $dbname = "数据库名";        

        // 创建数据库连接
        $conn = new mysqli($servername, $username, $password, $dbname);

        // 检查连接是否成功
        if ($conn->connect_error) {
            die("连接失败: " . $conn->connect_error);
        }

        // 创建新表保存计算结果，仅在第一次运行时创建
        $newTableName = "ip_access_summary";
        $sqlCreateTable = "CREATE TABLE IF NOT EXISTS $newTableName (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            IP VARCHAR(50) NOT NULL,
            TotalCount INT(6) NOT NULL,
            LastAccessTime DATETIME NOT NULL,
            FirstAccessTime DATETIME NOT NULL,
            Address VARCHAR(255) NOT NULL,
            UNIQUE KEY (IP) -- 确保IP字段为唯一键，避免重复插入
        )";

        if ($conn->query($sqlCreateTable) === TRUE) {
            echo "
<h1>/**
* @author Keeleycenc
* @Time   2023/07/11
* @brief  LightweightTracker轻量追踪器
* @param  none
* @retval uniqueIPCount;
          ip;totalCount;
          lastAccessTime;
          firstAccessTime;
          address;
* @note   简单而高效尽管它没有高级功能
* @version 1.2
* @link   https://keeleycenc.com
*/</h2>\n
<h2>* This lightweight tracker is designed for simplicity and efficiency. It provides basic tracking
* functionality for monitoring user visits, including IP address analysis and access statistics.
* While it may not have advanced features, its minimalistic approach makes it easy to integrate
* and suitable for projects where a simple visit tracking solution is preferred.</h2>";
        } else {
            echo "<span class='error'>插入数据失败: " . $conn->error . "</span>";
        }
        
        // 分页设置
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $recordsPerPage = 25;
        $offset = ($currentPage - 1) * $recordsPerPage;


        // /计算总页数
        $sqlPageCount = "SELECT COUNT(DISTINCT ip) AS count FROM visitors";
        $totalCountResult = $conn->query($sqlPageCount);
        $totalCountRow = $totalCountResult->fetch_assoc();
        $totalPages = ceil($totalCountRow['count'] / $recordsPerPage);

       //获取带有当前页面偏移量和限制的结果
        $sqlFetchResults = "SELECT 
        ip AS IP, 
        COUNT(ip) AS TotalCount, 
        MAX(time) AS LastAccessTime, 
        MIN(time) AS FirstAccessTime,
        address AS Address 
        FROM visitors 
        GROUP BY ip 
        ORDER BY LastAccessTime DESC 
        LIMIT $offset, $recordsPerPage";
        $results = $conn->query($sqlFetchResults);

        // 统计不同IP地址的总数量
        $uniqueIPCount = 0;

        // 将计算结果插入新表中，使用 INSERT INTO ... ON DUPLICATE KEY UPDATE 来确保数据插入或更新
        if ($results->num_rows > 0) {
            while ($row = $results->fetch_assoc()) {
            $ip = $row["IP"];
            $totalCount = $row["TotalCount"];
            $lastAccessTime = $row["LastAccessTime"];
            $firstAccessTime = $row["FirstAccessTime"];
            $address = $row["Address"];

            $sqlInsert = "INSERT INTO $newTableName (IP, TotalCount, LastAccessTime, FirstAccessTime, Address)
            VALUES ('$ip', $totalCount, '$lastAccessTime', '$firstAccessTime', '$address')
            ON DUPLICATE KEY UPDATE TotalCount = VALUES(TotalCount), LastAccessTime = VALUES(LastAccessTime), FirstAccessTime = VALUES(FirstAccessTime), Address = VALUES(Address)";

            if ($conn->query($sqlInsert) !== TRUE) {
            echo "<span class='error'>插入数据失败: " . $conn->error . "</span>";
            } else {
            echo "<span class='success'>Data replacement successful: IP=$ip, TotalCount=$totalCount, LastAccessTime=$lastAccessTime, FirstAccessTime=$firstAccessTime, Address=$address</span>";
            }

            // 增加不同IP地址的总数量
            $uniqueIPCount++;
        }} else {
        echo "<span class='error'>没有数据需要处理。</span>";
        }

        // 如果有多个页面，则输出分页链接
        if ($totalPages > 1) {
            echo "<div class='pagination'>";
            for ($i = 1; $i <= $totalPages; $i++) {
                if ($currentPage == $i) {
                    echo "<span class='current-page'>$i</span>";
                } else {
                    echo "<a href='?page=$i'>$i</a>";
                }
            }
            echo "</div>";
        }
        // 输出不同IP地址的总数量
        echo "<span class='success'>Valid visitor: $uniqueIPCount</span>";

        // 关闭数据库连接
        $conn->close();
        ?>
    </code>
</body>
</html>