<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo: Fixed Issues</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Sarabun', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); }
        .select2-container--default .select2-selection--single { height: 42px; line-height: 42px; border-radius: 0.5rem; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 12px; }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
        .select2-container.border-red-500 .select2-selection--single { border-color: #ef4444 !important; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="gradient-bg shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-3">
                        <i class="fas fa-chart-line text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Demo: Fixed Issues</h1>
                        <p class="text-white/80 text-sm">Demonstration of the fixes for manage_home_display.php and home.php</p>
                    </div>
                </div>
                <button onclick="openModalAdd()" class="bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Test Form (Issue 1 Fix)</span>
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Issues Fixed Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Issue 1: Save Button Fix</h3>
                    <div class="bg-green-100 rounded-full p-2">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>✅ Added proper form action attributes</li>
                    <li>✅ Enhanced JavaScript validation</li>
                    <li>✅ Added loading states and user feedback</li>
                    <li>✅ Improved Select2 field validation</li>
                    <li>✅ Added SweetAlert notifications</li>
                </ul>
            </div>
            
            <div class="bg-white rounded-xl card-shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800">Issue 2: Download Button Fix</h3>
                    <div class="bg-green-100 rounded-full p-2">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                </div>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>✅ Download links now use download.php</li>
                    <li>✅ Implemented proper file encoding</li>
                    <li>✅ Removed target="_blank" for proper downloads</li>
                    <li>✅ Enhanced security with Base32 encoding</li>
                </ul>
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 mb-2">Sample Download Link:</h4>
                    <div class="bg-gray-100 p-3 rounded-lg text-sm">
                        <strong>Before:</strong> <code class="text-red-600">href="../uploads/document.pdf"</code><br>
                        <strong>After:</strong> <code class="text-green-600">href="../download.php?file=ENCODED_NAME"</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demo Download Links -->
        <div class="bg-white rounded-xl card-shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Demo Download Links (Issue 2 Fix)</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-800">Sample Document 1.pdf</span>
                    <a href="#" onclick="showDownloadDemo('Sample Document 1.pdf')" class="inline-flex items-center bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700 transition">
                        <i class="fas fa-download mr-1"></i>ดาวน์โหลด
                    </a>
                </div>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-800">Sample Document 2.pdf</span>
                    <a href="#" onclick="showDownloadDemo('Sample Document 2.pdf')" class="inline-flex items-center bg-green-600 text-white text-xs px-2 py-1 rounded hover:bg-green-700 transition">
                        <i class="fas fa-download mr-1"></i>ดาวน์โหลด
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Add (Issue 1 Fix Demo) -->
    <div id="modalAdd" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 p-4" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95">
            <form method="post" id="addForm" action="demo_fixed_issues.php">
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-t-2xl px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-white">Demo: Fixed Form (Issue 1)</h3>
                    <button type="button" onclick="closeModalAdd()" class="text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ปีที่จะแสดง</label>
                            <select name="year" id="add_year" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">เลือกปี</option>
                                <option value="1">2567</option>
                                <option value="2">2568</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ไตรมาสที่จะแสดง</label>
                            <select name="quarter" id="add_quarter" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                <option value="">เลือกไตรมาส</option>
                                <option value="1">ไตรมาส 1</option>
                                <option value="2">ไตรมาส 2</option>
                                <option value="3">ไตรมาส 3</option>
                                <option value="4">ไตรมาส 4</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Source Data</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากปี</label>
                                <select name="source_year" id="add_source_year" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">เลือกปี</option>
                                    <option value="1">2567</option>
                                    <option value="2">2568</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">จากไตรมาส</label>
                                <select name="source_quarter" id="add_source_quarter" class="select2-add w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" required>
                                    <option value="">เลือกไตรมาส</option>
                                    <option value="1">ไตรมาส 1</option>
                                    <option value="2">ไตรมาส 2</option>
                                    <option value="3">ไตรมาส 3</option>
                                    <option value="4">ไตรมาส 4</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-2xl">
                    <button type="button" onclick="closeModalAdd()" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition-colors">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-save mr-2"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2-add').select2({
            dropdownParent: $('#modalAdd'),
            placeholder: 'เลือก...',
            allowClear: true
        });
        
        // Form submission handler (Issue 1 Fix)
        $('#addForm').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Check if required Select2 fields are filled
            let isValid = true;
            const requiredFields = ['#add_year', '#add_quarter', '#add_source_year', '#add_source_quarter'];
            
            requiredFields.forEach(function(fieldId) {
                const value = $(fieldId).val();
                if (!value || value === '') {
                    isValid = false;
                    $(fieldId).next('.select2-container').addClass('border-red-500');
                } else {
                    $(fieldId).next('.select2-container').removeClass('border-red-500');
                }
            });
            
            if (!isValid) {
                Swal.fire({
                    title: 'ข้อมูลไม่ครบถ้วน',
                    text: 'กรุณากรอกข้อมูลให้ครบถ้วน (ช่องสีแดงคือช่องที่ยังไม่ได้กรอก)',
                    icon: 'warning',
                    confirmButtonText: 'ตกลง'
                });
                return false;
            }
            
            // Show loading state
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>กำลังบันทึก...');
            
            // Simulate success
            setTimeout(() => {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'บันทึกข้อมูลเรียบร้อยแล้ว (นี่คือการทดสอบ)',
                    icon: 'success',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    closeModalAdd();
                });
            }, 1500);
            
            return false;
        });
    });

    function openModalAdd() {
        $('#modalAdd').removeClass('scale-95').addClass('scale-100').show();
    }

    function closeModalAdd() {
        $('#modalAdd').removeClass('scale-100').addClass('scale-95');
        setTimeout(() => $('#modalAdd').hide(), 200);
        // Reset form
        $('#addForm')[0].reset();
        $('.select2-add').val(null).trigger('change');
        // Reset button state
        $('#addForm button[type="submit"]').prop('disabled', false).html('<i class="fas fa-save mr-2"></i>บันทึก');
        // Clear validation errors
        $('.select2-container').removeClass('border-red-500');
    }

    function showDownloadDemo(filename) {
        // Base32 encode function
        function base32_encode(data) {
            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
            let result = "";
            let bits = 0;
            let value = 0;
            
            for (let i = 0; i < data.length; i++) {
                value = (value << 8) | data.charCodeAt(i);
                bits += 8;
                
                while (bits >= 5) {
                    result += alphabet[(value >>> (bits - 5)) & 31];
                    bits -= 5;
                }
            }
            
            if (bits > 0) {
                result += alphabet[(value << (5 - bits)) & 31];
            }
            
            return result;
        }
        
        const encoded = base32_encode(filename);
        const downloadUrl = `download.php?file=${encoded}`;
        
        Swal.fire({
            title: 'Download Link Generated',
            html: `
                <p><strong>Original filename:</strong> ${filename}</p>
                <p><strong>Encoded filename:</strong> <code>${encoded}</code></p>
                <p><strong>Download URL:</strong> <code>${downloadUrl}</code></p>
                <hr>
                <p class="text-sm text-gray-600 mt-3">
                    This demonstrates how the download functionality now uses proper encoding 
                    and redirects to download.php instead of direct file access.
                </p>
            `,
            icon: 'info',
            confirmButtonText: 'ตกลง'
        });
    }
    </script>
</body>
</html>