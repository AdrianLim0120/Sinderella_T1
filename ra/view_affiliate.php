<?php
session_start();
if (!isset($_SESSION['adm_id'])) { header('Location: ../login_adm.php'); exit(); }
require_once '../db_connect.php';

// Fetch all Sinderellas
$sinderellas = [];
$result = $conn->query("SELECT sind_id, sind_name, sind_upline_id, sind_status, acc_approved FROM sinderellas");
while ($row = $result->fetch_assoc()) {
    $sinderellas[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Affiliate Tree</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <style>
        .affiliate-node {
            margin: 4px 0 4px 24px;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-block;
            font-weight: bold;
            min-width: 120px;
        }
        .affiliate-head { background: #007bff; color: #fff; }
        .affiliate-green { background: #28a745; color: #fff; }
        .affiliate-red { background: #dc3545; color: #fff; }
        .affiliate-orange { background: #fd7e14; color: #fff; }
        .children { margin-left: 32px; }
        .toggle-arrow { font-size: 14px; margin-right: 6px; }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Affiliate Tree</h2>
                <div id="affiliate-tree"></div>
                <button onclick="window.location.href='view_sinderellas.php'">Back</button>
            </div>
        </div>
    </div>
    <script>
    // Prepare affiliate data
    const sinderellas = <?php echo json_encode($sinderellas); ?>;
    // Build a map: upline_id => [children...]
    const tree = {};
    sinderellas.forEach(s => {
        let up = s.sind_upline_id || "0";
        if (!tree[up]) tree[up] = [];
        tree[up].push(s);
    });

    // Track expanded nodes
    const expanded = {};

    // Render node
    function renderNode(sind_id, name, status, approved, isHead = false) {
        let colorClass = '';
        if (isHead) colorClass = 'affiliate-head';
        if (isHead) colorClass = 'affiliate-green';
        else if (status === 'active' && approved === 'approve') colorClass = 'affiliate-green';
        else if (status === 'inactive' || approved === 'reject') colorClass = 'affiliate-red';
        else if (status === 'pending' || approved === 'pending') colorClass = 'affiliate-orange';

        let hasChildren = tree[sind_id] && tree[sind_id].length > 0;
        let arrow = hasChildren ? (expanded[sind_id] ? '▼' : '▶') : '';
        return `<div class="affiliate-node ${colorClass}" data-id="${sind_id}" onclick="toggleNode(event, '${sind_id}')">
            <span class="toggle-arrow">${arrow}</span>${name}
        </div>`;
    }

    // Render tree recursively
    function renderTree(parentId) {
        let html = '';
        if (parentId === "0") {
            html += renderNode("0", "Sinderella", "active", "approve", true);
            if (expanded["0"]) {
                html += `<div class="children">${renderChildren("0")}</div>`;
            }
        } else {
            html += renderChildren(parentId);
        }
        return html;
    }

    function renderChildren(parentId) {
        let html = '';
        (tree[parentId] || []).forEach(child => {
            html += renderNode(child.sind_id, child.sind_name, child.sind_status, child.acc_approved);
            if (expanded[child.sind_id]) {
                html += `<div class="children">${renderChildren(child.sind_id)}</div>`;
            }
        });
        return html;
    }

    // Toggle expand/collapse
    function toggleNode(e, sind_id) {
        e.stopPropagation();
        expanded[sind_id] = !expanded[sind_id];
        document.getElementById('affiliate-tree').innerHTML = renderTree("0");
    }

    // Initial render: only show head
    document.getElementById('affiliate-tree').innerHTML = renderTree("0");
    </script>
</body>
</html>