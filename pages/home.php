<?php
session_start();
include './db.php';

// ฟังก์ชันสำหรับการเข้ารหัส Base32
function base32_encode($data)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $padding = strlen($data) % 5;
    $data .= str_repeat("\0", 5 - $padding);
    $base32 = '';

    for ($i = 0; $i < strlen($data); $i += 5) {
        $chunk = substr($data, $i, 5);
        $bits = '';
        for ($j = 0; $j < 5; $j++) {
            $bits .= str_pad(decbin(ord($chunk[$j])), 8, '0', STR_PAD_LEFT);
        }
        for ($j = 0; $j < 8; $j++) {
            $index = bindec(substr($bits, $j * 5, 5));
            $base32 .= $alphabet[$index];
        }
    }

    return rtrim($base32, '=');
}

// ฟังก์ชันสำหรับการถอดรหัส Base32
function base32_decode($data) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = str_pad($data, ceil(strlen($data) / 8) * 8, '=', STR_PAD_RIGHT);
    $base64 = '';

    for ($i = 0; $i < strlen($data); $i += 8) {
        $chunk = substr($data, $i, 8);
        $bits = '';
        for ($j = 0; $j < 8; $j++) {
            $bits .= str_pad(decbin(strpos($alphabet, $chunk[$j])), 5, '0', STR_PAD_LEFT);
        }
        for ($j = 0; $j < strlen($bits) / 8; $j++) {
            $base64 .= chr(bindec(substr($bits, $j * 8, 8)));
        }
    }

    return rtrim($base64, "\0");
}

// ฟังก์ชันสำหรับการเข้ารหัสชื่อไฟล์
function encode_file_name($file_name) {
    return base32_encode($file_name);
}

// ดึงปีทั้งหมดและจัดกลุ่มข้อมูล
$years = $pdo->query('SELECT id, year FROM years ORDER BY year DESC')->fetchAll();

// สร้าง yearMap สำหรับแปลง id เป็น year
$yearMap = [];
foreach ($years as $y) {
    $yearMap[$y['id']] = $y['year'];
}

// ดึงค่าตั้งค่าการแสดงผลหน้าแรก
$homeConfigs = $pdo->query('SELECT * FROM home_display_config ORDER BY year DESC, quarter ASC')->fetchAll();

// สร้าง configMap สำหรับการค้นหาที่รวดเร็ว
$configMap = [];
foreach ($homeConfigs as $config) {
    $configMap[$config['year']][$config['quarter']] = $config;
}

// หาการตั้งค่าเริ่มต้น
$defaultConfig = null;
foreach ($homeConfigs as $config) {
    if (isset($config['is_default']) && $config['is_default']) {
        $defaultConfig = $config;
        break;
    }
}

// กำหนดค่าเริ่มต้น
$defaultYear = 2568; // ค่าเริ่มต้นของปี
$defaultQuarter = 3; // ค่าเริ่มต้นของไตรมาส

// ใช้ค่าจากการตั้งค่าเริ่มต้นถ้ามี
if ($defaultConfig && isset($defaultConfig['default_year']) && isset($defaultConfig['default_quarter'])) {
    $defaultYear = $defaultConfig['default_year'];
    $defaultQuarter = $defaultConfig['default_quarter'];
}

// รับค่าจาก URL หรือใช้ค่าเริ่มต้น
$selectedYearValue = intval($_GET['year'] ?? 0);
$selectedQuarter = intval($_GET['quarter'] ?? 0);

// ถ้าไม่มีค่าจาก URL ให้ใช้ค่าเริ่มต้น
if (!$selectedYearValue) {
    $selectedYearValue = $defaultYear;
}
if (!$selectedQuarter) {
    $selectedQuarter = $defaultQuarter;
}

// หา year_id จาก year value
$selectedYearId = null;
foreach ($years as $y) {
    if ($y['year'] == $selectedYearValue) {
        $selectedYearId = $y['id'];
        break;
    }
}

// หาค่าตั้งค่าที่ตรงกับปี/ไตรมาสที่เลือก
$activeConfig = $configMap[$selectedYearId][$selectedQuarter] ?? null;

// ถ้ามี config ให้ใช้ข้อมูลตาม config, ถ้าไม่มีให้ใช้ข้อมูลจริง
if ($activeConfig) {
    $sourceYearId = $activeConfig['source_year'];
    $sourceQuarter = $activeConfig['source_quarter'];
    $sourceYearValue = $yearMap[$sourceYearId] ?? $selectedYearValue;
    $isConfigured = true;
} else {
    $sourceYearId = $selectedYearId;
    $sourceQuarter = $selectedQuarter;
    $sourceYearValue = $selectedYearValue;
    $isConfigured = false;
}

// ฟังก์ชันดึงข้อมูลหมวดหมู่สำหรับแต่ละไตรมาส
function getCategoriesForQuarter($pdo, $yearValue, $quarter) {
    $stmt = $pdo->prepare("
        SELECT
            categories.id AS category_id,
            categories.name AS category_name,
            categories.year,
            categories.quarter,
            subcategories.id AS subcategory_id,
            subcategories.name AS subcategory_name,
            COUNT(documents.id) AS document_count
        FROM
            categories
        LEFT JOIN
            subcategories ON categories.id = subcategories.category_id
        LEFT JOIN
            documents ON subcategories.id = documents.subcategory_id AND documents.approved = 1
        WHERE
            categories.year = ? AND categories.quarter = ?
        GROUP BY
            categories.id, categories.name, categories.year, categories.quarter, subcategories.id, subcategories.name
        ORDER BY
            categories.id ASC, subcategories.name ASC
    ");
    $stmt->execute([$yearValue, $quarter]);
    return $stmt->fetchAll();
}

// ข้อมูลสรุป
$totalUploads = $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalApproved = $pdo->query("SELECT COUNT(*) FROM documents WHERE approved = 1")->fetchColumn();
$totalPending = $pdo->query("SELECT COUNT(*) FROM documents WHERE approved = 0")->fetchColumn();

// ข้อมูลสรุปสำหรับปี/ไตรมาสที่เลือก
$currentYearUploads = $pdo->prepare("
    SELECT COUNT(*) FROM documents d
    JOIN subcategories s ON d.subcategory_id = s.id
    JOIN categories c ON s.category_id = c.id
    WHERE c.year = ? AND c.quarter = ? AND d.approved = 1
");
$currentYearUploads->execute([$sourceYearValue, $sourceQuarter]);
$currentYearTotal = $currentYearUploads->fetchColumn();

// ตรวจสอบว่ามี config ที่ใช้งานอยู่หรือไม่
$hasActiveConfigs = count($homeConfigs) > 0;
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $(document.body),
            placeholder: 'เลือก...',
            allowClear: false
        });
    });
</script>

<style>
    body, .font-sarabun { font-family: 'Sarabun', 'Prompt', 'Tahoma', 'Arial', sans-serif; }
    .select2-container .select2-selection--single {
        height: 40px;
        border-radius: 0.75rem;
        border: 1px solid #cbd5e1;
        font-size: 1rem;
        padding: 6px 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px;
        color: #334155;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
    }
    .tab-button {
        transition: background 0.2s, color 0.2s;
    }
    .tab-button.active, .tab-button:focus {
        background: #e0f2fe;
        color: #2563eb;
        outline: none;
    }
    .tab-content {
        transition: opacity 0.2s;
    }
    .tab-content.active {
        opacity: 1;
    }
    .tab-content.hidden {
        opacity: 0;
        pointer-events: none;
        position: absolute;
        left: -9999px;
    }
    .stats-card {
        box-shadow: 0 2px 8px 0 rgba(0,0,0,0.07);
        border-radius: 1rem;
        padding: 1.5rem;
        background: linear-gradient(90deg, #f3f4f6 0%, #e0e7ff 100%);
    }
    .stats-card .icon {
        font-size: 2.5rem;
        color: #6366f1;
    }
    .stats-card .label {
        color: #64748b;
        font-size: 1rem;
    }
    .stats-card .value {
        font-size: 2rem;
        font-weight: bold;
        color: #1e293b;
    }
    .quarter-indicator.active {
        box-shadow: 0 0 0 2px #10b981;
    }
    .quarter-indicator.configured {
        box-shadow: 0 0 0 2px #f59e0b;
    }
    .tab-content ul {
        margin-top: 1rem;
    }
    .tab-content li {
        transition: box-shadow 0.2s;
    }
    .tab-content li:hover {
        box-shadow: 0 2px 8px 0 rgba(59,130,246,0.08);
        background: #f0f9ff;
    }
    @media (max-width: 640px) {
        .stats-card { padding: 1rem; font-size: 0.95rem; }
        .tab-button { font-size: 0.95rem; padding: 0.5rem 0.75rem; }
        .tab-content { padding: 0.5rem; }
    }
</style>

<!-- Alert สำหรับแจ้งเตือนการตั้งค่า -->
<?php if ($isConfigured): ?>
<div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-amber-400"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm text-amber-700">
                <strong>การแสดงผลถูกปรับแต่ง:</strong> 
                ข้อมูลที่แสดงในปี <?= htmlspecialchars($selectedYearValue) ?> ไตรมาส <?= $selectedQuarter ?> 
                มาจากข้อมูลจริงของปี <?= htmlspecialchars($sourceYearValue) ?> ไตรมาส <?= $sourceQuarter ?>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg shadow-lg p-6 stats-card">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-files-o text-3xl text-white icon"></i>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-white truncate label">เอกสารทั้งหมด</dt>
                    <dd class="text-2xl font-bold text-white value"><?= number_format($totalUploads) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg shadow-lg p-6 stats-card">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-user-circle text-3xl text-white icon"></i>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-white truncate label">ผู้ใช้งาน</dt>
                    <dd class="text-2xl font-bold text-white value"><?= number_format($totalUsers) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-lg shadow-lg p-6 stats-card">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-3xl text-white icon"></i>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-white truncate label">เอกสารที่อนุมัติ</dt>
                    <dd class="text-2xl font-bold text-white value"><?= number_format($totalApproved) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-r from-purple-400 to-purple-500 rounded-lg shadow-lg p-6 stats-card">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-chart-bar text-3xl text-white icon"></i>
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-white truncate label">เอกสารปีปัจจุบัน</dt>
                    <dd class="text-2xl font-bold text-white value"><?= number_format($currentYearTotal) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- เลือกปี/ไตรมาส -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap gap-4 items-center">
            <form method="get" class="flex gap-2 items-center" id="yearQuarterForm">
                <input type="hidden" name="page" value="home">
                <label class="font-semibold text-gray-700">เลือกปี:</label>
                <select name="year" class="select2 w-32">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year'] ?>" <?= ($selectedYearValue == $y['year']) ? 'selected' : '' ?>><?= $y['year'] ?></option>
                    <?php endforeach; ?>
                </select>
                <label class="font-semibold ml-2 text-gray-700">เลือกไตรมาส:</label>
                <select name="quarter" class="select2 w-32">
                    <?php for ($q = 1; $q <= 4; $q++): ?>
                        <?php 
                        $hasConfig = isset($configMap[$selectedYearId][$q]);
                        $configText = $hasConfig ? ' (ปรับแต่ง)' : '';
                        ?>
                        <option value="<?= $q ?>" <?= ($selectedQuarter == $q) ? 'selected' : '' ?>>
                            ไตรมาส <?= $q ?><?= $configText ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>
    </div>
    
    <!-- Quarter Status Indicators -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="flex items-center gap-2 mb-2 sm:mb-0">
                <span class="text-sm font-medium text-gray-700">สถานะไตรมาส:</span>
                <div class="flex items-center gap-2">
                    <?php for (
                        $q = 1; $q <= 4; $q++): ?>
                        <?php 
                        $hasConfig = isset($configMap[$selectedYearId][$q]);
                        $isActive = ($q == $selectedQuarter);
                        $indicatorClass = $isActive ? 'bg-green-500 text-white border-green-600' : ($hasConfig ? 'bg-yellow-400 text-white border-yellow-500' : 'bg-gray-200 text-gray-600 border-gray-400');
                        $border = 'border-2';
                        ?>
                        <div class="flex flex-col items-center">
                            <span class="w-8 h-8 flex items-center justify-center rounded-full font-bold text-base <?php echo $indicatorClass; ?> <?php echo $border; ?>">
                                Q<?= $q ?>
                            </span>
                            <?php if ($isActive): ?>
                                <span class="text-xs text-green-600 mt-1 font-semibold">กำลังแสดง</span>
                            <?php elseif ($hasConfig): ?>
                                <span class="text-xs text-yellow-600 mt-1 font-semibold">ปรับแต่ง</span>
                            <?php else: ?>
                                <span class="text-xs text-gray-500 mt-1">ต้นฉบับ</span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-500 mt-2 sm:mt-0">
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-green-500 rounded-full border-2 border-green-600"></span>กำลังแสดง</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-yellow-400 rounded-full border-2 border-yellow-500"></span>มีการปรับแต่ง</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 bg-gray-200 rounded-full border-2 border-gray-400"></span>ข้อมูลต้นฉบับ</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex quarter-grid" id="tabNav">
            <?php for ($q = 1; $q <= 4; $q++): ?>
                <?php 
                $hasConfig = isset($configMap[$selectedYearId][$q]);
                $isActive = ($q == $selectedQuarter);
                $tabClass = $isActive ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                ?>
                <button class="tab-button whitespace-nowrap py-4 px-6 border-b-2 <?= $tabClass ?> font-medium text-sm <?= $isActive ? 'active' : '' ?> <?= $hasConfig ? 'config-indicator' : '' ?>" 
                        onclick="showTab('tab_<?= $q ?>', this, <?= $q ?>)">
                    <div class="flex items-center">
                        <span class="quarter-indicator <?= $isActive ? 'active' : ($hasConfig ? 'configured' : '') ?>"></span>
                        แบบประเมินไตรมาสที่ <?= $q ?>
                        <?php if ($hasConfig): ?>
                            <i class="fas fa-cog ml-2 text-xs text-amber-500"></i>
                        <?php endif; ?>
                    </div>
                </button>
            <?php endfor; ?>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
        <?php for ($quarter = 1; $quarter <= 4; $quarter++): ?>
            <div class="tab-content <?= $quarter == $selectedQuarter ? 'active' : 'hidden' ?>" id="tab_<?= $quarter ?>">
                <div id="tab_<?= $quarter ?>_content">
                    <!-- Content will be loaded here -->
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-4"></i>
                            <p class="text-gray-500">กำลังโหลดข้อมูล...</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    </div>
</div>

<script>
// Cache สำหรับเก็บข้อมูลที่โหลดแล้ว
const dataCache = new Map();
const currentYear = <?= $selectedYearValue ?>;

// ฟังก์ชันโหลดข้อมูลสำหรับแต่ละไตรมาส
async function loadQuarterData(quarter) {
    const cacheKey = `${currentYear}_${quarter}`;
    
    if (dataCache.has(cacheKey)) {
        return dataCache.get(cacheKey);
    }
    
    try {
        const response = await fetch(`ajax/get_quarter_data.php?year=${currentYear}&quarter=${quarter}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.text();
        dataCache.set(cacheKey, data);
        return data;
    } catch (error) {
        console.error('Error loading quarter data:', error);
        return `
            <div class="text-center py-8 text-red-500">
                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                <p class="text-lg">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                <button onclick="loadQuarterData(${quarter})" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-redo mr-2"></i>ลองใหม่
                </button>
            </div>
        `;
    }
}

// ฟังก์ชันแสดง tab
async function showTab(tabId, buttonElement, quarter) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('active');
    });
    
    // Show selected tab content
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.classList.remove('hidden');
        selectedTab.classList.add('active');
    }
    
    // Update button styles
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600', 'active');
        button.classList.add('border-transparent', 'text-gray-500');
        
        // Update quarter indicators
        const indicator = button.querySelector('.quarter-indicator');
        if (indicator) {
            indicator.classList.remove('active');
            if (indicator.classList.contains('configured')) {
                // Keep configured style
            } else {
                indicator.classList.remove('configured');
            }
        }
    });
    
    buttonElement.classList.remove('border-transparent', 'text-gray-500');
    buttonElement.classList.add('border-blue-500', 'text-blue-600', 'active');
    
    // Update active indicator
    const activeIndicator = buttonElement.querySelector('.quarter-indicator');
    if (activeIndicator) {
        activeIndicator.classList.add('active');
    }
    
    // Load data for this quarter if not already loaded
    const contentDiv = document.getElementById(`tab_${quarter}_content`);
    if (contentDiv && !contentDiv.dataset.loaded) {
        const data = await loadQuarterData(quarter);
        contentDiv.innerHTML = data;
        contentDiv.dataset.loaded = 'true';
    }
    
    // Update URL without page reload
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('quarter', quarter);
    window.history.replaceState({}, '', url);
}

// โหลดข้อมูลเริ่มต้น
window.addEventListener('DOMContentLoaded', async function() {
    const selectedQuarter = <?= $selectedQuarter ?>;
    const tabToShow = 'tab_' + selectedQuarter;
    const buttonToActivate = document.querySelector(`button[onclick*="showTab('${tabToShow}'"]`);
    
    if (buttonToActivate) {
        await showTab(tabToShow, buttonToActivate, selectedQuarter);
    }
    
    // Preload data for other quarters
    setTimeout(async () => {
        for (let q = 1; q <= 4; q++) {
            if (q !== selectedQuarter) {
                await loadQuarterData(q);
            }
        }
    }, 1000);
});

// Handle form submission
document.querySelector('#yearQuarterForm').addEventListener('submit', function(e) {
    // Clear cache when changing year
    dataCache.clear();
});

// Handle year selection change
document.querySelector('select[name="year"]').addEventListener('change', function(e) {
    const selectedYear = this.value;
    // Clear cache for new year
    dataCache.clear();
    
    // Submit form to update the page data for the new year
    this.form.submit();
});

// Handle quarter selection change
document.querySelector('select[name="quarter"]').addEventListener('change', function(e) {
    const selectedQuarter = parseInt(this.value);
    
    // Switch to the selected quarter tab
    const tabToShow = 'tab_' + selectedQuarter;
    const buttonToActivate = document.querySelector(`button[onclick*="showTab('${tabToShow}'"]`);
    
    if (buttonToActivate) {
        showTab(tabToShow, buttonToActivate, selectedQuarter);
    }
    
    // Update the URL
    const url = new URL(window.location);
    url.searchParams.set('year', currentYear);
    url.searchParams.set('quarter', selectedQuarter);
    window.history.replaceState({}, '', url);
});
</script>