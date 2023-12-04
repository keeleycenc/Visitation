<?php
/**
* @author Keeleycenc
* @param  none
* @version 2.0
* @link   https://keeleycenc.com
*/

// �������ݿ�
$servername = "localhost";
$username = "�û���";
$password = "����";
$dbname = "���ݿ���";

// ��������
$conn = new mysqli($servername, $username, $password, $dbname);

// �������
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$currentDateTime = date("Y-m-d H:i:s"); // ���õ�ǰ���ں�ʱ��

// ��鲢���±�ṹ
$table = "CREATE TABLE IF NOT EXISTS visitor_count (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, -- ����������������Ψһ��ʶÿ����¼
    ip VARCHAR(45),                                -- �����ߵ�IP��ַ
    location VARCHAR(100),                         -- �����ߵĵ���λ��
    device VARCHAR(190),                           -- ������ʹ�õ��豸��Ϣ
    visits INT(30) NOT NULL,                       -- �����IP���豸�ķ��ʴ���
    date DATE NOT NULL,                            -- ��¼������
    total_visits INT(11) NOT NULL DEFAULT 1,       -- ��IP���豸���ܷ��ʴ�����Ĭ��Ϊ1
    first_visit_time DATETIME NULL,                -- ��IP���豸���״η���ʱ��
    last_visit_time DATETIME NULL,                 -- ��IP���豸��������ʱ��
    UNIQUE KEY unique_visit (ip, device, date)     -- ��ϼ�����֤ͬһIP���豸��ͬһ��ļ�¼��Ψһ��
)";
if ($conn->query($table) === FALSE) {
    die("Error creating table: " . $conn->error);
}

// ��鲢���daily_order��
$checkColumn = "SHOW COLUMNS FROM visitor_count LIKE 'daily_order'";
$columnResult = $conn->query($checkColumn);

if ($columnResult->num_rows == 0) {
    // ���daily_order�в����ڣ��������
    $alterTable = "ALTER TABLE visitor_count ADD daily_order INT(6) UNSIGNED AFTER date";
    $conn->query($alterTable);
}

$conn->query($table);

// ��ȡ�û�IP���豸��Ϣ
$user_IpAddress = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'];

// IP�����ز�ѯ
function getIpLocation($ip) {
    // ������ѯ��URL
    $apiUrl = 'http://ip.plyz.net/ip.ashx?ip=' . $ip;    
    // ����GET����
    $response = file_get_contents($apiUrl);    
    // ������Ӧ
    if ($response) {
        // ������Ӧ�ĸ�ʽ�� "IP|������"�������߷ָ�
        $parts = explode('|', $response);            
        if (count($parts) >= 2) {
            // ��һ��������������Ϣ�������ʽ�� "���� ʡ�� ���� ��Ӫ��"
            $locationParts = explode(' ', $parts[1]);
            if (count($locationParts) >= 2) {
                return $locationParts[1]; // ����ʡ�ݲ���
            } else {
                return 'δ֪������'; // ����޷�����ʡ�ݣ��򷵻�Ĭ��ֵ
            }
        } else {
            return 'δ֪������'; // ����޷�������Ӧ���򷵻�Ĭ��ֵ
        }
    } else {
        return 'δ֪������'; // ����޷���ȡ��Ӧ���򷵻�Ĭ��ֵ
    }
}


$userLocation = getIpLocation($user_IpAddress);

// ���㵱��ķÿ�˳��
function calculateDailyOrder($date) {
    global $conn;
    $query = "SELECT MAX(daily_order) as maxOrder FROM visitor_count WHERE date='$date'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    return ($row["maxOrder"] + 1);
}

// �������IP���豸�ķ��ʼ�¼
$date = date("Y-m-d");
$checkQuery = "SELECT daily_order FROM visitor_count WHERE ip='$user_IpAddress' AND device='$userAgent' AND date='$date'";
$checkResult = $conn->query($checkQuery);

if ($checkResult->num_rows == 0) {
    // ���û���ҵ���¼����Ϊ�·ÿͣ������µ�daily_order
    $dailyOrder = calculateDailyOrder($date);
    $insert = "INSERT INTO visitor_count (ip, location, device, visits, date, daily_order) VALUES ('$user_IpAddress', '$userLocation', '$userAgent', 1, '$date', '$dailyOrder')";
    $conn->query($insert);
} else {
    // ����ҵ���¼��ʹ�����е�daily_order
    $row = $checkResult->fetch_assoc();
    $dailyOrder = $row["daily_order"];
}



// ����IP���ܷ��ʼ�¼���״η���ʱ��
$totalCheckQuery = "SELECT id, total_visits, first_visit_time FROM visitor_count WHERE ip='$user_IpAddress' AND device='$userAgent'";
$totalCheckResult = $conn->query($totalCheckQuery);

if ($totalCheckResult->num_rows > 0) {
    // ����ҵ���¼�����ӷ��ʴ���������������ʱ��
    $row = $totalCheckResult->fetch_assoc();
    $newTotalVisits = $row["total_visits"] + 1;

    // ��� first_visit_time �Ƿ�Ϊ null������Ӧ����
    $firstVisitTimeUpdate = "";
    if (is_null($row["first_visit_time"])) {
        $firstVisitTimeUpdate = ", first_visit_time='$currentDateTime'";
    }

    $updateTotalVisits = "UPDATE visitor_count SET total_visits=$newTotalVisits, last_visit_time='$currentDateTime'$firstVisitTimeUpdate WHERE id=" . $row["id"];
    $conn->query($updateTotalVisits);
} else {
    // ���û���ҵ���¼�������¼�¼�������״κ�������ʱ��
    $dailyOrder = calculateDailyOrder($date);
    $insertNewRecord = "INSERT INTO visitor_count (ip, location, device, visits, date, daily_order, total_visits, first_visit_time, last_visit_time) VALUES ('$user_IpAddress', '$userLocation', '$userAgent', 1, '$date', '$dailyOrder', 1, '$currentDateTime', '$currentDateTime')";
    $conn->query($insertNewRecord);
}

// ��ȡͳ������
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

// ��ȡ���ա����պ��ܷ�����
$todayVisits = getVisits(date("Y-m-d"));
$yesterdayVisits = getVisits(date("Y-m-d", strtotime("-1 day")));
$totalResult = $conn->query("SELECT SUM(visits) AS totalVisits FROM visitor_count");
$totalRow = $totalResult->fetch_assoc();
$totalVisits = $totalRow["totalVisits"];

// ����JSON��ʽ������
echo json_encode(array(
    "daily_order" => $dailyOrder, 
    "today" => $todayVisits, 
    "yesterday" => $yesterdayVisits, 
    "total" => $totalVisits,
    "location" => $userLocation
));


$conn->close();
?>
