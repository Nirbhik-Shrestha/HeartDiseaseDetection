<?php
include("../connection.php");
include_once("../auth.php");
include_once("../prediction.php");

$userfetch = requireRole($con, 'patient');
$useremail = $userfetch["pemail"];
$userid = $userfetch["pid"];

// Fetch user prediction history (newest first, for the table)
$query = $con->prepare("SELECT * FROM patient_data WHERE pid = ? ORDER BY `timestamp` DESC, pdid DESC");
$query->bind_param("i", $userid);
$query->execute();
$rows = $query->get_result()->fetch_all(MYSQLI_ASSOC);
$query->close();

// Readings saved before scores were stored, or edited since, get scored now.
ensureRiskScores($con, $rows);

// The chart runs oldest to newest and skips anything the model could not score.
$trend = [];
foreach (array_reverse($rows) as $row) {
    if ($row['risk_score'] !== null) {
        $trend[] = [
            'id'    => (int)$row['pdid'],
            'time'  => strtotime($row['timestamp']) * 1000,
            'date'  => date('j M Y', strtotime($row['timestamp'])),
            'score' => riskPercent($row['risk_score']),
        ];
    }
}

$latest   = $trend ? $trend[count($trend) - 1] : null;
$previous = count($trend) > 1 ? $trend[count($trend) - 2] : null;

$notices = [
    'updated' => 'Your reading was updated and its risk score recalculated.',
    'deleted' => 'The reading was deleted.',
];
$notice = isset($_GET['msg'], $notices[$_GET['msg']]) ? $notices[$_GET['msg']] : null;
$username = $userfetch["pname"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Prediction History - DaaktarSahab</title>
    <link rel="stylesheet" href="../css/site.css">
    <style>
        /* ---- Risk score trend ---- */
        .trend-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
            margin: 0 0 8px;
        }
        .trend-stat {
            text-align: right;
        }
        .trend-stat-value {
            color: #12304a;
            font-size: 34px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .trend-stat-value small {
            color: #6b8193;
            font-size: 15px;
            font-weight: 500;
        }
        .trend-stat-delta {
            color: #3d566b;
            font-size: 15px;
        }
        .trend-chart {
            position: relative;
            margin-top: 8px;
        }
        .trend-chart svg {
            display: block;
            width: 100%;
            height: auto;
            overflow: visible;
        }
        .trend-chart .grid line { stroke: #edf2f7; stroke-width: 1; }
        .trend-chart .axis text { fill: #6b8193; font-size: 12px; }
        .trend-chart .threshold line { stroke: #a0aec0; stroke-width: 1; }
        .trend-chart .threshold text { fill: #3d566b; font-size: 12px; }
        .trend-chart .series-line { fill: none; stroke: #00897b; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
        .trend-chart .series-dot { fill: #00897b; stroke: #fff; stroke-width: 2; }
        .trend-chart .series-dot.active { r: 6; }
        .trend-chart .end-label { fill: #12304a; font-size: 13px; font-weight: 600; }
        .trend-chart .crosshair { stroke: #a0aec0; stroke-width: 1; }
        .trend-tooltip {
            position: absolute;
            display: none;
            padding: 6px 10px;
            border-radius: 6px;
            background: #12304a;
            color: #fff;
            font-size: 13px;
            line-height: 1.4;
            white-space: nowrap;
            pointer-events: none;
            transform: translate(-50%, calc(-100% - 12px));
        }
        .trend-tooltip .tt-key {
            display: inline-block;
            width: 12px;
            height: 2px;
            margin-right: 6px;
            background: #4fd1c5;
            vertical-align: middle;
        }

        .row-actions {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
            white-space: nowrap;
        }
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">My history</span>
        <h1>Prediction history</h1>
        <p>Every heart check you have taken, with its risk score. Open one to see the full result or download it as a PDF.</p>
    </div>
</section>

<main class="page-body">
    <?php if ($notice): ?>
        <div class="notice notice-ok"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <?php if ($trend): ?>
        <section class="panel" aria-labelledby="trendTitle">
            <div class="trend-head">
                <div>
                    <h2 class="panel__title" id="trendTitle">Risk score over time</h2>
                    <p class="panel__sub">0 to 100. Readings above 50 are classed as high risk.</p>
                </div>
                <div class="trend-stat">
                    <div class="trend-stat-value"><?= $latest['score'] ?><small>/100 latest</small></div>
                    <?php if ($previous): ?>
                        <?php $delta = $latest['score'] - $previous['score']; ?>
                        <div class="trend-stat-delta">
                            <?php if ($delta === 0): ?>
                                No change since the previous reading
                            <?php else: ?>
                                <?= $delta < 0 ? '&#9660; Down' : '&#9650; Up' ?> <?= abs($delta) ?> points since the previous reading
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (count($trend) > 1): ?>
                <div class="trend-chart" id="trendChart"></div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Take another heart check later to see how your score changes over time.</p>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="panel">
        <div class="panel__head">
            <div>
                <h2 class="panel__title">All readings</h2>
                <p class="panel__sub"><?= count($rows) ?> <?= count($rows) === 1 ? 'reading' : 'readings' ?></p>
            </div>
            <a href="form.php" class="btn btn-primary btn-sm">+ New heart check</a>
        </div>

        <?php if ($rows): ?>
            <div class="table-scroll">
                <table class="data-table stack">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Risk score</th>
                            <th>Age</th>
                            <th>BP</th>
                            <th>Cholesterol</th>
                            <th>Max heart rate</th>
                            <th>ST depression</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="cell-strong nowrap" data-label="Date"><div><?= date('j M Y', strtotime($row['timestamp'])) ?><br><span class="cell-muted"><?= date('g:i A', strtotime($row['timestamp'])) ?></span></div></td>
                                <td data-label="Risk score">
                                    <?php if ($row['risk_score'] === null): ?>
                                        <span class="badge badge-muted">Not scored</span>
                                    <?php else: ?>
                                        <?php $high = isHighRisk($row['risk_score']); ?>
                                        <span class="badge <?= $high ? 'badge-danger' : 'badge-success' ?>">
                                            <?= riskPercent($row['risk_score']) ?> &middot; <?= $high ? 'High' : 'Low' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Age"><?= (int)$row['age'] ?></td>
                                <td data-label="BP"><?= (int)$row['trestbps'] ?></td>
                                <td data-label="Cholesterol"><?= (int)$row['chol'] ?></td>
                                <td data-label="Max heart rate"><?= (int)$row['thalach'] ?></td>
                                <td data-label="ST depression"><?= htmlspecialchars($row['oldpeak']) ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-light btn-sm" href="viewResult.php?id=<?= $row['pdid'] ?>">View</a>
                                        <a class="btn btn-light btn-sm" href="downloadAssessment.php?id=<?= $row['pdid'] ?>">PDF</a>
                                        <a class="btn btn-light btn-sm" href="editData.php?id=<?= $row['pdid'] ?>">Edit</a>
                                        <a class="btn btn-danger btn-sm" href="deleteData.php?id=<?= $row['pdid'] ?>" onclick="return confirm('Delete this reading? This cannot be undone.');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>You haven't taken a heart check yet.</p>
                <a href="form.php" class="btn btn-primary">Take your first check</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include('../footer.html'); ?>

<?php if (count($trend) > 1): ?>
<script>
(function () {
    var points = <?= json_encode($trend) ?>;
    var host = document.getElementById('trendChart');
    var NS = 'http://www.w3.org/2000/svg';

    var W = 640, H = 240;
    var pad = { top: 16, right: 44, bottom: 30, left: 34 };
    var innerW = W - pad.left - pad.right;
    var innerH = H - pad.top - pad.bottom;

    // Readings are placed by date, so gaps between assessments show as gaps.
    var t0 = points[0].time, t1 = points[points.length - 1].time;
    var span = t1 - t0;
    function x(p, i) {
        var f = span > 0 ? (p.time - t0) / span : i / (points.length - 1);
        return pad.left + f * innerW;
    }
    function y(score) { return pad.top + (1 - score / 100) * innerH; }

    function el(name, attrs, parent) {
        var node = document.createElementNS(NS, name);
        for (var k in attrs) node.setAttribute(k, attrs[k]);
        if (parent) parent.appendChild(node);
        return node;
    }
    function text(parent, attrs, value) {
        var node = el('text', attrs, parent);
        node.textContent = value;
        return node;
    }

    var svg = el('svg', {
        viewBox: '0 0 ' + W + ' ' + H,
        role: 'img',
        'aria-label': 'Risk score over time, ' + points.length + ' readings. The table below lists every value.'
    }, host);

    // Gridlines and y ticks
    var grid = el('g', { 'class': 'grid' }, svg);
    var axis = el('g', { 'class': 'axis' }, svg);
    [0, 25, 75, 100].forEach(function (v) {
        el('line', { x1: pad.left, x2: pad.left + innerW, y1: y(v), y2: y(v) }, grid);
    });
    [0, 25, 50, 75, 100].forEach(function (v) {
        text(axis, { x: pad.left - 8, y: y(v) + 4, 'text-anchor': 'end' }, v);
    });

    // High-risk threshold
    var th = el('g', { 'class': 'threshold' }, svg);
    el('line', { x1: pad.left, x2: pad.left + innerW, y1: y(50), y2: y(50) }, th);
    text(th, { x: pad.left + 4, y: y(50) - 5 }, 'High risk above 50');

    // X labels: first and last date, plus the middle one when there is room
    var labelIdx = [0, points.length - 1];
    if (points.length > 4) labelIdx.splice(1, 0, Math.floor((points.length - 1) / 2));
    labelIdx.forEach(function (i, n) {
        var anchor = n === 0 ? 'start' : (n === labelIdx.length - 1 ? 'end' : 'middle');
        text(axis, { x: x(points[i], i), y: H - 8, 'text-anchor': anchor }, points[i].date);
    });

    // Series
    var d = points.map(function (p, i) { return (i ? 'L' : 'M') + x(p, i) + ' ' + y(p.score); }).join(' ');
    el('path', { d: d, 'class': 'series-line' }, svg);
    var cross = el('line', { 'class': 'crosshair', y1: pad.top, y2: pad.top + innerH, visibility: 'hidden' }, svg);
    var dots = points.map(function (p, i) {
        return el('circle', { cx: x(p, i), cy: y(p.score), r: 4, 'class': 'series-dot' }, svg);
    });

    // Value at the end of the line
    var last = points[points.length - 1];
    text(svg, { x: x(last, points.length - 1) + 10, y: y(last.score) + 4, 'class': 'end-label' }, last.score);

    // Hover / focus: crosshair snaps to the nearest reading
    var tip = document.createElement('div');
    tip.className = 'trend-tooltip';
    host.appendChild(tip);

    var overlay = el('rect', {
        x: pad.left - 10, y: 0, width: innerW + 20, height: H,
        fill: 'transparent', tabindex: 0, style: 'cursor: pointer; outline: none'
    }, svg);
    var active = -1;

    function show(i) {
        if (active >= 0) dots[active].classList.remove('active');
        active = i;
        var p = points[i], px = x(p, i);
        dots[i].classList.add('active');
        cross.setAttribute('x1', px);
        cross.setAttribute('x2', px);
        cross.setAttribute('visibility', 'visible');

        tip.textContent = '';
        var line1 = document.createElement('div');
        var key = document.createElement('span');
        key.className = 'tt-key';
        line1.appendChild(key);
        line1.appendChild(document.createTextNode(p.score + '/100 · ' + (p.score > 50 ? 'High risk' : 'Low risk')));
        var line2 = document.createElement('div');
        line2.textContent = p.date + ' — click to open';
        tip.appendChild(line1);
        tip.appendChild(line2);

        var scale = host.clientWidth / W;
        tip.style.left = (px * scale) + 'px';
        tip.style.top = (y(p.score) * scale) + 'px';
        tip.style.display = 'block';
    }
    function hide() {
        if (active >= 0) dots[active].classList.remove('active');
        active = -1;
        cross.setAttribute('visibility', 'hidden');
        tip.style.display = 'none';
    }
    function nearest(evt) {
        var box = svg.getBoundingClientRect();
        var sx = (evt.clientX - box.left) * (W / box.width);
        var best = 0;
        points.forEach(function (p, i) {
            if (Math.abs(x(p, i) - sx) < Math.abs(x(points[best], best) - sx)) best = i;
        });
        return best;
    }

    overlay.addEventListener('pointermove', function (e) { show(nearest(e)); });
    overlay.addEventListener('pointerleave', hide);
    overlay.addEventListener('click', function (e) {
        window.location.href = 'viewResult.php?id=' + points[nearest(e)].id;
    });
    overlay.addEventListener('focus', function () { show(points.length - 1); });
    overlay.addEventListener('blur', hide);
    overlay.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft' && active > 0) show(active - 1);
        else if (e.key === 'ArrowRight' && active < points.length - 1) show(active + 1);
        else if (e.key === 'Enter' && active >= 0) window.location.href = 'viewResult.php?id=' + points[active].id;
    });
})();
</script>
<?php endif; ?>
</body>
</html>
