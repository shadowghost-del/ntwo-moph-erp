<!-- ========================================================================= -->
<!-- COMPONENT: อธิบายสถานะและสัญลักษณ์สีของการเชื่อมต่อ API -->
<!-- ========================================================================= -->
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 my-6">
    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3 flex items-center">
        <i class="fa-solid fa-circle-info text-blue-600 mr-2 text-sm"></i> อธิบายสถานะและสัญลักษณ์สีของการเชื่อมต่อ API
    </h4>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
        <div class="flex items-start p-3 bg-emerald-50/60 border border-emerald-200 rounded-lg">
            <span class="status-dot green mt-1 flex-shrink-0"></span>
            <div>
                <span class="font-bold text-emerald-900 block mb-0.5">เขียว (NORMAL)</span>
                <p class="text-emerald-700 leading-relaxed">มีการรับข้อมูล ภายใน 1 ชม.</p>
            </div>
        </div>

        <div class="flex items-start p-3 bg-blue-50/60 border border-blue-200 rounded-lg">
            <span class="status-dot blue mt-1 flex-shrink-0"></span>
            <div>
                <span class="font-bold text-blue-900 block mb-0.5">สีน้ำเงิน (NORMAL-LATE)</span>
                <p class="text-blue-700 leading-relaxed">มีการส่ง แต่รับข้อมูลช้ากว่า 1 ชม.</p>
            </div>
        </div>

        <div class="flex items-start p-3 bg-red-50/60 border border-red-200 rounded-lg">
            <span class="status-dot red mt-1 flex-shrink-0"></span>
            <div>
                <span class="font-bold text-red-900 block mb-0.5">สีแดง (DOWN)</span>
                <p class="text-red-700 leading-relaxed">ไม่ได้เชื่อมต่อ / ระบบขัดข้อง</p>
            </div>
        </div>
    </div>
    
    <!-- หมายเหตุแหล่งที่มาข้อมูล -->
    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center text-xs text-slate-600 gap-2">
        <span class="font-bold text-slate-700 flex items-center">
            <i class="fa-solid fa-link text-moph-600 mr-1.5"></i> หมายเหตุ:
        </span>
        <span>แหล่งที่มาข้อมูลจากระบบ</span>
        <a href="https://referlink.moph.go.th/mopherp" target="_blank" rel="noopener noreferrer" class="font-bold text-moph-700 hover:text-moph-800 underline hover:no-underline inline-flex items-center">
            MOPH ERP System
        </a>
        <a href="https://referlink.moph.go.th/mopherp" target="_blank" rel="noopener noreferrer" class="inline-block">
            <img src="img/moph-erp.png" alt="MOPH ERP System" class="h-5 w-auto object-contain rounded shadow-sm hover:opacity-90 transition">
        </a>
    </div>
</div>

<!-- ========================================================================= -->
<!-- FOOTER COPYRIGHT & BRANDING -->
<!-- ========================================================================= -->
<footer class="mt-8 mb-6 text-center text-xs text-slate-500 font-medium border-t border-slate-200 pt-4 space-y-3">
    <p>© 2026 MOPH ERP Narathiwat Provincial Public Health Office  • สสจ. นราธิวาส</p>
    
    <div class="flex flex-col items-center justify-center pt-1 space-y-1">
        <img src="img/it-team.png" alt="IT Team Narathiwat" class="h-10 w-auto object-contain transition-transform hover:scale-105">
        <span class="text-[11px] font-semibold text-slate-600 tracking-wider uppercase">Digital Health</span>
    </div>
</footer>