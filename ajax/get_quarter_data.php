<?php
session_start();
include "../db.php";

function base32_encode($data) {
    $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
    $padding = strlen($data) % 5;
    $data .= str_repeat("\0", 5 - $padding);
    $base32 = "";
    for ($i = 0; $i < strlen($data); $i += 5) {
        $chunk = substr($data, $i, 5);
        $bits = "";
        for ($j = 0; $j < 5; $j++) {
            $bits .= str_pad(decbin(ord($chunk[$j])), 8, "0", STR_PAD_LEFT);
        }
        for ($j = 0; $j < 8; $j++) {
            $index = bindec(substr($bits, $j * 5, 5));
            $base32 .= $alphabet[$index];
        }
    }
    return rtrim($base32, "=");
}

function encode_file_name($file_name) {
    return base32_encode($file_name);
}

$yearValue = intval($_GET["year"] ?? 0); // ปี เช่น 2567
$quarter = intval($_GET["quarter"] ?? 0);

if (!$yearValue || !$quarter) {
    http_response_code(400);
    echo "Invalid parameters";
    exit;
}

// หา year_id จากปี
$yearStmt = $pdo->prepare("SELECT id FROM years WHERE year = ? LIMIT 1");
$yearStmt->execute([$yearValue]);
$yearRow = $yearStmt->fetch();
if (!$yearRow) {
    http_response_code(404);
    echo "ปีไม่ถูกต้อง";
    exit;
}
$year_id = $yearRow["id"];

// ตรวจ config
$configStmt = $pdo->prepare("SELECT * FROM home_display_config WHERE year = ? AND quarter = ?");
$configStmt->execute([$year_id, $quarter]);
$config = $configStmt->fetch();

// หา year map สำหรับการแปลง id เป็น year
$years = $pdo->query("SELECT id, year FROM years")->fetchAll();
$yearMap = array();
foreach ($years as $y) {
    $yearMap[$y["id"]] = $y["year"];
}

if ($config) {
    // ใช้ข้อมูลตาม config
    $sourceYearId = $config["source_year"];
    $sourceQuarter = $config["source_quarter"];
    $sourceYearValue = $yearMap[$sourceYearId] ?? $yearValue;
    $isConfigured = true;
} else {
    // ใช้ข้อมูลจริง
    $sourceYearId = $year_id;
    $sourceQuarter = $quarter;
    $sourceYearValue = $yearValue;
    $isConfigured = false;
}

// ดึงข้อมูลหมวดหมู่
$stmt = $pdo->prepare("
    SELECT
        categories.id AS category_id,
        categories.name AS category_name,
        categories.year,
        categories.quarter,
        subcategories.id AS subcategory_id,
        subcategories.name AS subcategory_name,
        COUNT(documents.id) AS document_count
    FROM categories
    LEFT JOIN subcategories ON categories.id = subcategories.category_id
    LEFT JOIN documents ON subcategories.id = documents.subcategory_id AND documents.approved = 1
    WHERE categories.year = ? AND categories.quarter = ?
    GROUP BY categories.id, categories.name, categories.year, categories.quarter, subcategories.id, subcategories.name
    ORDER BY categories.id ASC, subcategories.name ASC
");
$stmt->execute([$sourceYearId, $sourceQuarter]);
$categories = $stmt->fetchAll();

$current_category = null;
$hasData = false;
$output = "";

foreach ($categories as $category) {
    $hasData = true;
    if ($current_category != $category["category_name"]) {
        if ($current_category != null) {
            $output .= "</ul></div></div>";
        }
        $current_category = $category["category_name"];
        // กรอบเขียวหมวดหมู่หลัก
        $output .= "<div class=\"border-2 border-green-500 rounded-lg mb-6\">";
        $output .= "<div class=\"bg-white px-4 py-3 border-b-2 border-green-500 rounded-t-lg\">";
        $output .= "<span class=\"font-bold text-green-700 text-lg\">".htmlspecialchars($category["category_name"])."";
        $output .= "</span> ";
        $output .= "<span class=\"text-gray-600 text-base\">(ปี ".$category["year"]." ไตรมาส ".$category["quarter"].")</span>";
        $output .= "</div><div class=\"p-4\"><ul class=\"space-y-4\">";
    }
    if ($category["subcategory_id"]) {
        // หาจำนวนเอกสารใน subcategory
        $docStmt = $pdo->prepare("SELECT id, title, file_name, created_at FROM documents WHERE subcategory_id = ? AND approved = 1 ORDER BY created_at DESC");
        $docStmt->execute([$category["subcategory_id"]]);
        $docs = $docStmt->fetchAll();
        $docCount = count($docs);
        // หัวข้อหมวดหมู่ย่อย + badge
        $output .= "<li class=\"mb-2\">";
        $output .= "<div class=\"flex flex-wrap items-center gap-2 mb-1\">";
        $output .= "<span class=\"font-semibold text-blue-800\">".htmlspecialchars($category["subcategory_name"])."</span>";
        $output .= "<span class=\"bg-green-500 text-white text-xs rounded px-2 py-0.5 ml-1\"><i class=\"fas fa-file-alt mr-1\"></i>".$docCount." เอกสาร</span>";
        $output .= "</div>";
        if ($docCount > 0) {
            $output .= "<ul class=\"ml-6 list-disc space-y-1\">";
            foreach ($docs as $doc) {
                $filePath = '../uploads/' . $doc['file_name'];
                $fileSize = file_exists($filePath) ? round(filesize($filePath)/1024, 2) : 0;
                $encodedFileName = encode_file_name($doc['file_name']);
                $output .= "<li class=\"flex flex-wrap items-center gap-2\">";
                $output .= "<span class=\"text-gray-800\">".htmlspecialchars($doc['title'])."</span>";
                $output .= "<a href=\"download.php?file=".urlencode($encodedFileName)."\" class=\"inline-flex items-center bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700 transition ml-1\">";
                $output .= "<i class=\"fas fa-download mr-1\"></i>ดาวน์โหลด";
                $output .= "</a>";
                $output .= "<span class=\"bg-gray-200 text-gray-700 text-xs rounded px-2 py-0.5 ml-1\">1 เอกสาร</span>";
                $output .= "</li>";
            }
            $output .= "</ul>";
        } else {
            $output .= "<span class=\"inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-500 bg-gray-100 cursor-not-allowed mt-1\">";
            $output .= "<i class=\"fas fa-ban mr-2\"></i>ไม่มีเอกสาร";
            $output .= "</span>";
        }
        $output .= "</li>";
    }
}
if ($current_category != null) {
    $output .= "</ul></div></div>";
}
if (!$hasData) {
    $output .= "<div class=\"text-center py-12\">";
    $output .= "<div class=\"mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4\">";
    $output .= "<i class=\"fas fa-folder-open text-gray-400 text-3xl\"></i>";
    $output .= "</div>";
    $output .= "<h3 class=\"text-lg font-medium text-gray-900 mb-2\">ไม่พบข้อมูลในไตรมาสนี้</h3>";
    $output .= "<p class=\"text-gray-500 mb-4\">ยังไม่มีการเพิ่มหมวดหมู่หรือเอกสารในไตรมาสที่ ".$quarter." ของปี ".$yearValue."</p>";
    // ไม่ต้องแสดงปุ่มเพิ่มหมวดหมู่ในหน้า home
    $output .= "</div>";
}

// เพิ่มข้อมูลสถิติสำหรับไตรมาสนี้
$statsStmt = $pdo->prepare("SELECT 
    COUNT(DISTINCT categories.id) as category_count,
    COUNT(DISTINCT subcategories.id) as subcategory_count,
    COUNT(documents.id) as document_count
FROM categories
LEFT JOIN subcategories ON categories.id = subcategories.category_id
LEFT JOIN documents ON subcategories.id = documents.subcategory_id AND documents.approved = 1
WHERE categories.year = ? AND categories.quarter = ?");
$statsStmt->execute([$sourceYearId, $sourceQuarter]);
$stats = $statsStmt->fetch();

$statsHtml = "<div class=\"mb-6\">";
$statsHtml .= "<div class=\"grid grid-cols-1 md:grid-cols-3 gap-4 mb-6\">";
$statsHtml .= "<div class=\"bg-blue-50 border border-blue-200 rounded-lg p-4\">";
$statsHtml .= "<div class=\"flex items-center\">";
$statsHtml .= "<div class=\"flex-shrink-0\">";
$statsHtml .= "<i class=\"fas fa-layer-group text-blue-600 text-2xl\"></i>";
$statsHtml .= "</div>";
$statsHtml .= "<div class=\"ml-4\">";
$statsHtml .= "<p class=\"text-sm font-medium text-blue-600\">หมวดหมู่</p>";
$statsHtml .= "<p class=\"text-2xl font-bold text-blue-900\">".number_format($stats["category_count"])."</p>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "<div class=\"bg-green-50 border border-green-200 rounded-lg p-4\">";
$statsHtml .= "<div class=\"flex items-center\">";
$statsHtml .= "<div class=\"flex-shrink-0\">";
$statsHtml .= "<i class=\"fas fa-folder text-green-600 text-2xl\"></i>";
$statsHtml .= "</div>";
$statsHtml .= "<div class=\"ml-4\">";
$statsHtml .= "<p class=\"text-sm font-medium text-green-600\">หมวดหมู่ย่อย</p>";
$statsHtml .= "<p class=\"text-2xl font-bold text-green-900\">".number_format($stats["subcategory_count"])."</p>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "<div class=\"bg-yellow-50 border border-yellow-200 rounded-lg p-4\">";
$statsHtml .= "<div class=\"flex items-center\">";
$statsHtml .= "<div class=\"flex-shrink-0\">";
$statsHtml .= "<i class=\"fas fa-file-alt text-yellow-600 text-2xl\"></i>";
$statsHtml .= "</div>";
$statsHtml .= "<div class=\"ml-4\">";
$statsHtml .= "<p class=\"text-sm font-medium text-yellow-600\">เอกสาร</p>";
$statsHtml .= "<p class=\"text-2xl font-bold text-yellow-900\">".number_format($stats["document_count"])."</p>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";
$statsHtml .= "</div>";

// เพิ่มข้อมูลสถิติรายเดือนถ้ามีข้อมูล
if ($stats["document_count"] > 0) {
    $monthlyStmt = $pdo->prepare("SELECT 
        MONTH(documents.created_at) as month,
        COUNT(documents.id) as count
    FROM documents
    JOIN subcategories ON documents.subcategory_id = subcategories.id
    JOIN categories ON subcategories.category_id = categories.id
    WHERE categories.year = ? AND categories.quarter = ? AND documents.approved = 1
    GROUP BY MONTH(documents.created_at)
    ORDER BY month");
    $monthlyStmt->execute([$sourceYearId, $sourceQuarter]);
    $monthlyData = $monthlyStmt->fetchAll();
    
    if (!empty($monthlyData)) {
        $statsHtml .= "<div class=\"bg-white border border-gray-200 rounded-lg p-4 mb-6\">";
        $statsHtml .= "<h4 class=\"text-lg font-semibold text-gray-900 mb-4\">การอัปโหลดรายเดือน</h4>";
        $statsHtml .= "<div class=\"grid grid-cols-1 md:grid-cols-".min(count($monthlyData), 4)." gap-4\">";
        
        $monthNames = array(
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม', 
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        );
        
        foreach ($monthlyData as $month) {
            $statsHtml .= "<div class=\"text-center\">";
            $statsHtml .= "<div class=\"bg-blue-100 rounded-lg p-3\">";
            $statsHtml .= "<p class=\"text-sm font-medium text-blue-600\">".$monthNames[$month["month"]]."</p>";
            $statsHtml .= "<p class=\"text-xl font-bold text-blue-900\">".number_format($month["count"])."</p>";
            $statsHtml .= "</div>";
            $statsHtml .= "</div>";
        }
        $statsHtml .= "</div>";
        $statsHtml .= "</div>";
    }
}

$statsHtml .= "</div>";

// รวมผลลัพธ์: แสดงสถิติก่อน แล้วตามด้วยหมวดหมู่/เอกสาร
// เพิ่ม indicator ถ้าข้อมูลถูก configure
$configIndicator = "";
if ($isConfigured) {
    $configIndicator = "<div class=\"mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg\">";
    $configIndicator .= "<div class=\"flex items-center\">";
    $configIndicator .= "<i class=\"fas fa-info-circle text-amber-500 mr-2\"></i>";
    $configIndicator .= "<span class=\"text-amber-800 font-medium\">การแสดงผลถูกปรับแต่ง:</span>";
    $configIndicator .= "<span class=\"text-amber-700 ml-2\">";
    $configIndicator .= "ข้อมูลที่แสดงในปี $yearValue ไตรมาส $quarter มาจากข้อมูลจริงของปี $sourceYearValue ไตรมาส $sourceQuarter";
    $configIndicator .= "</span>";
    $configIndicator .= "</div>";
    $configIndicator .= "</div>";
}

echo $configIndicator . $statsHtml . $output;
?>
