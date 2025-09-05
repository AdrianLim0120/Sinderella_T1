<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Policy - Sinderella</title>
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
            min-height: 200px;
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
            en: `<b>Cancellation Policy (English Version)</b><br>
<br>1. If the customer notifies the admin to cancel the service <b>more than 48 hours</b> before the service, no fees will be charged.</br>
<br>2. If the customer notifies the admin to cancel the service <b>between 24 hours and 48 hours</b> before the service, <b>30% of the total paid amount</b> will be charged.<br>
<br>3. If the customer notifies the admin to cancel the service <b>less than 24 hours</b> before the service, the <b>full amount will be charged (no refund)</b>.<br>
<br>4. Sinderella Kleen Sdn. Bhd. reserves the right to amend the above policy at any time. The final right of interpretation belongs to Sinderella Kleen Sdn. Bhd.<br>`,
            cn: `<b>取消政策 (中文版)</b><br>
<br>1. 若客户在服务开始前 <b>48小时以上</b> 通知管理员取消服务，将不收取任何费用。<br>
<br>2. 若客户在服务开始前 <b>24小时至48小时之间</b> 通知管理员取消服务，将收取 <b>已支付总金额的 30%</b>。<br>
<br>3. 若客户在服务开始前 <b>少于24小时</b> 通知管理员取消服务，将收取 <b>全额费用（不退款）</b>。<br>
<br>4. Sinderella Kleen Sdn. Bhd. 保留随时更改以上政策的权利，最终解释权归 Sinderella Kleen Sdn. Bhd. 所有。<br>`,
            bm: `<b>Polisi Pembatalan (Versi Bahasa Melayu)</b><br>
<br>1. Jika pelanggan memaklumkan kepada admin untuk membatalkan perkhidmatan <b>lebih daripada 48 jam</b> sebelum perkhidmatan, tiada caj akan dikenakan.<br>
<br>2. Jika pelanggan memaklumkan kepada admin untuk membatalkan perkhidmatan <b>antara 24 jam hingga 48 jam</b> sebelum perkhidmatan, sebanyak <b>30% daripada jumlah yang telah dibayar</b> akan dikenakan.<br>
<br>3. Jika pelanggan memaklumkan kepada admin untuk membatalkan perkhidmatan <b>kurang daripada 24 jam</b> sebelum perkhidmatan, <b>jumlah penuh akan dikenakan (tiada bayaran balik)</b>.<br>
<br>4. Sinderella Kleen Sdn. Bhd. berhak untuk mengubah mana-mana polisi di atas pada bila-bila masa. Hak tafsiran muktamad adalah milik Sinderella Kleen Sdn. Bhd.<br>`
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