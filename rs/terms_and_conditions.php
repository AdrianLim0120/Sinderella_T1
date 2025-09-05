<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8f8f8;
        }
        .container {
            margin-top: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px #0001;
            padding: 32px 24px 24px 24px;
            max-width: 700px;
            width: 100%;
        }
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 24px;
        }
        .tab {
            padding: 10px 28px;
            cursor: pointer;
            border: none;
            background: #e0e0e0;
            color: #333;
            font-size: 16px;
            border-radius: 8px 8px 0 0;
            margin-right: 2px;
            outline: none;
            transition: background 0.2s;
        }
        .tab.active {
            background: #1976d2;
            color: #fff;
            font-weight: bold;
        }
        .policy-content {
            text-align: left;
            font-size: 15px;
            line-height: 1.7;
            min-height: 400px;
        }
        @media (max-width: 800px) {
            .container { padding: 12px; }
            .policy-content { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="tabs">
            <button class="tab active" id="tab-en" onclick="showPolicy('en')">English</button>
            <button class="tab" id="tab-cn" onclick="showPolicy('cn')">中文</button>
            <button class="tab" id="tab-bm" onclick="showPolicy('bm')">Bahasa Melayu</button>
        </div>
        <div class="policy-content" id="policy-content">
            <!-- English content will be loaded here by default -->
        </div>
    </div>
    <script>
        const policies = {
            en: `<b>Sinderella Kleen – Independent Contractor Service Agreement (Trilingual Version)</b><br>
This Agreement is prepared in three languages (Chinese / Bahasa Melayu / English). All versions are consistent and shall have equal legal effect.<br>

<br>
<b>English Version</b><br>
Party A (Platform): Sinderella Kleen Sdn. Bhd.<br>
Party B (Independent Contractor): Sinderella<br>

<br>
1. <b>Scope of Services:</b> <br>
&bull; Party B agrees to provide residential and office cleaning services, including but not limited to basic cleaning, deep cleaning, move-in/move-out cleaning, and additional services.<br>

<br>
2. <b>Contract Status:</b> <br>
&bull; Party B is an Independent Contractor and not an employee. Party B is responsible for their own taxes and insurance.<br>

<br>
3. <b>Term & Termination:</b> <br>
&bull; Effective from the signing date; <br>
&bull; Either party may terminate with 14 days’ written notice; <br>
&bull; Party A may terminate immediately in case of serious misconduct or unsatisfactory service.<br>

<br>
4. <b>Service Time & Code of Conduct:</b> <br>
&bull; Party B must arrive punctually; <br>
&bull; Must comply with Party A’s checklist & service standards.<br>

<br>
5. <b>Compensation & Payment:</b> <br>
&bull; Each service paid according to Party A’s rates; <br>
&bull; Individual income paid every Friday; <br>
&bull; Group income calculated monthly & paid once per month.<br>

<br>
6. <b>Tools & Materials:</b> <br>
&bull; Clients normally provide basic cleaning supplies; <br>
&bull; If provided by Party A/Party B, must be properly maintained.<br>

<br>
7. <b>Insurance & Liability:</b> <br>
&bull; Party B is responsible for own safety; <br>
&bull; If client property is damaged, Party B must repair or compensate.<br>

<br>
8. <b>Confidentiality & Non-Compete:</b> <br>
&bull; Party B shall not disclose client information; <br>
&bull; During the contract, Party B shall not provide similar services directly to Party A’s clients.<br>

<br>
9. <b>Governing Law:</b> <br>
&bull; This Agreement is governed by the laws of Malaysia. Disputes shall first be settled amicably, failing which may be referred to court.<br>
`,
            cn: `<b>Sinderella Kleen – 独立合约人服务协议 (三语版)</b><br>
本协议为三语版本（中文 / Bahasa Melayu / English），内容一致，三种语言版本具有同等法律效力。<br>

<br>
<b>中文版</b><br>
甲方（平台方）：Sinderella Kleen Sdn. Bhd.<br>
乙方（独立合约人）：Sinderella<br>

<br>
1. <b>服务内容：</b><br>
&bull; 乙方同意依照甲方安排，为客户提供住宅与办公室清洁服务，包括但不限于基础清洁、深度清洁、搬家清洁及其他附加项目。<br>

<br>
2. <b>合约性质：</b><br>
&bull; 乙方为独立合约人，并非甲方员工。乙方需自行负责税务及保险。<br>

<br>
3. <b>协议期限与终止：</b><br>
&bull; 协议自签署日起生效；<br>
&bull; 任一方可提前14天书面通知终止；<br>
&bull; 若乙方严重违规或服务质量不达标，甲方可立即终止协议。<br>

<br>
4. <b>服务时间与守则：</b><br>
&bull; 乙方须准时到达客户地点；<br>
&bull; 须遵守甲方的服务规范与标准清单。<br>

<br>
5. <b>酬劳与支付：</b><br>
&bull; 每次服务报酬依甲方规定支付；<br>
&bull; 个人收入每星期五发放；<br>
&bull; 团队收入每月结算并支付。<br>

<br>
6. <b>工具与材料：</b><br>
&bull; 客户一般需提供基本清洁用品；<br>
&bull; 若甲方或乙方提供额外工具，须妥善保管。<br>

<br>
7. <b>保险与责任：</b><br>
&bull; 乙方须注意自身安全；<br>
&bull; 若造成客户财产损坏，乙方须负责修复或赔偿。<br>

<br>
8. <b>保密与非竞业：</b><br>
&bull; 乙方不得泄露客户资料；<br>
&bull; 合同期内，不得私自接触甲方客户提供同类服务。<br>

<br>
9. <b>法律适用：</b><br>
&bull; 本协议受马来西亚法律管辖，争议应先友好协商，不成可提交法院。<br>
`,
            bm: `<b>Sinderella Kleen – Perjanjian Perkhidmatan Kontraktor Bebas (Versi Tiga Bahasa)</b><br>
Perjanjian ini disediakan dalam tiga bahasa (Bahasa Cina / Bahasa Melayu / Inggeris). Semua versi adalah konsisten dan mempunyai kesan undang-undang yang sama.<br>

<br>
<b>Versi Bahasa Melayu</b><br>
Pihak Pertama (Platform): Sinderella Kleen Sdn. Bhd.<br>
Pihak Kedua (Kontraktor Bebas): Sinderella<br>

<br>
1. <b>Skop Perkhidmatan:</b> <br>
&bull; Pihak Kedua bersetuju melaksanakan kerja pembersihan rumah & pejabat termasuk pembersihan asas, mendalam, pindah masuk/keluar dan perkhidmatan tambahan.<br>

<br>
2. <b>Status Kontrak:</b> <br>
&bull; Pihak Kedua ialah Kontraktor Bebas, bukan pekerja tetap. Pihak Kedua bertanggungjawab ke atas cukai & insurans sendiri.<br>

<br>
3. <b>Tempoh & Penamatan:</b> <br>
&bull; Perjanjian berkuat kuasa dari tarikh ditandatangani; <br>
&bull; Mana-mana pihak boleh menamatkan dengan notis bertulis 14 hari; <br>
&bull; Pihak Pertama berhak menamatkan serta-merta jika berlaku pelanggaran serius atau mutu kerja tidak memuaskan.<br>

<br>
4. <b>Masa & Tatakelakuan:</b> <br>
&bull; Pihak Kedua mesti hadir tepat waktu; <br>
&bull; Mesti ikut piawaian & senarai semak perkhidmatan Pihak Pertama.<br>

<br>
5. <b>Bayaran & Ganjaran:</b> <br>
&bull; Upah setiap kerja dibayar ikut kadar ditetapkan; <br>
&bull; Pendapatan individu dibayar setiap hari Jumaat; <br>
&bull; Pendapatan kumpulan dikira bulanan & dibayar sebulan sekali.<br>

<br>
6. <b>Peralatan & Bahan:</b> <br>
&bull; Pelanggan biasanya sediakan peralatan asas; <br>
&bull; Jika disediakan oleh Pihak Pertama/Pihak Kedua, mesti dijaga dengan baik.<br>

<br>
7. <b>Insurans & Tanggungjawab:</b> <br>
&bull; Pihak Kedua mesti jaga keselamatan diri; <br>
&bull; Jika berlaku kerosakan harta pelanggan, Pihak Kedua bertanggungjawab membaiki/ganti rugi.<br>

<br>
8. <b>Sulit & Larangan Persaingan:</b> <br>
&bull; Pihak Kedua dilarang dedahkan maklumat pelanggan; <br>
&bull; Semasa kontrak, tidak boleh beri perkhidmatan serupa terus kepada pelanggan Pihak Pertama.<br>

<br>
9. <b>Undang-undang Terpakai:</b> <br>
&bull; Perjanjian ini tertakluk kepada undang-undang Malaysia. Pertikaian diselesaikan melalui rundingan, jika gagal dibawa ke mahkamah.<br>
`
        };

        function showPolicy(lang) {
            document.getElementById('policy-content').innerHTML = policies[lang];
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById('tab-' + lang).classList.add('active');
        }

        // Show English by default
        showPolicy('en');
    </script>
</body>
</html>