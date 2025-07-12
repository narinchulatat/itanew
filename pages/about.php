 <div class="flex items-center justify-between border-b pb-4 mb-4 px-6 pt-6">
     <h3 class="text-xl font-bold text-blue-700">เกี่ยวกับเว็บไซต์</h3>
     <div class="flex space-x-2">
         <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full p-2" title="ย่อ/ขยาย" onclick="$('.about-content').slideToggle()">
             <i class="fa fa-minus"></i>
         </button>
         <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-full p-2" title="ปิด" onclick="Swal.fire('ปิดข้อมูลเกี่ยวกับเว็บไซต์')">
             <i class="fa fa-times"></i>
         </button>
     </div>
 </div>
 <div class="about-content px-6 pb-6">
     <p class="text-gray-700">เว็บไซต์นี้เป็นระบบจัดเก็บเอกสาร ITA โรงพยาบาลน้ำยืน จังหวัดอุบลราชธานี<br>
         พัฒนาโดย นายนรินทร์ จุลทัศน์ นักวิชาการคอมพิวเตอร์ปฏิบัติการ<br>
         รองรับการใช้งานบนอุปกรณ์ทุกขนาด มีระบบจัดการเอกสาร หมวดหมู่ ปี และผู้ใช้<br>
         <span class="font-semibold">UX/UI สวยงาม Responsive ใช้งานง่าย</span>
     </p>
 </div>

 <script>
     $(function() {
         // รองรับ responsive, SweetAlert2, ปุ่มย่อ/ขยาย
     });
 </script>

 <style>
     /* ลบ .bg-white ออก ไม่ต้องมีกรอบ */
     .shadow-lg {
         box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
     }

     .word-break {
         word-break: break-word;
     }

     @media (max-width: 640px) {
         .about-content {
             padding: 1rem !important;
         }

         h3 {
             font-size: 1.1rem;
         }

         p {
             font-size: 0.95rem;
         }
     }
 </style>