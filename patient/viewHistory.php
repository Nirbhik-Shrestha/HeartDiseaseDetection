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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Prediction History</title>
    <link rel="stylesheet" href="style.css" />
    <style>
        .container{
            max-width: fit-content !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .actions a {
            margin: 0 5px;
            text-decoration: none;
        }

        .btn-delete {
            color: red;
        }

        .btn-edit {
            color: orange;
        }

        .btn-view {
            color: green;
        }

        .btn-pdf {
            color: #00897b;
        }

        .risk-pill {
            display: inline-block;
            min-width: 64px;
            padding: 2px 10px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9em;
        }
        .risk-pill.high { background: #fff5f5; color: #c53030; border: 1px solid #feb2b2; }
        .risk-pill.low  { background: #f0fff4; color: #276749; border: 1px solid #9ae6b4; }
        .risk-pill.none { color: #888; }

        /* ---- Risk score trend ---- */
        .trend-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px 20px 12px;
            margin: 10px 0 24px;
            min-width: 320px;
        }
        .trend-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 12px;
        }
        .trend-head h2 {
            margin: 0;
            font-size: 1.15rem;
            color: #2d3748;
        }
        .trend-sub {
            margin: 4px 0 0;
            font-size: 0.85rem;
            color: #718096;
        }
        .trend-stat {
            text-align: right;
        }
        .trend-stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1a202c;
        }
        .trend-stat-value small {
            font-size: 0.85rem;
            font-weight: 500;
            color: #718096;
        }
        .trend-stat-delta {
            font-size: 0.85rem;
            color: #4a5568;
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
        .trend-chart .axis text { fill: #718096; font-size: 11px; }
        .trend-chart .threshold line { stroke: #a0aec0; stroke-width: 1; }
        .trend-chart .threshold text { fill: #4a5568; font-size: 11px; }
        .trend-chart .series-line { fill: none; stroke: #00897b; stroke-width: 2; stroke-linejoin: round; stroke-linecap: round; }
        .trend-chart .series-dot { fill: #00897b; stroke: #fff; stroke-width: 2; }
        .trend-chart .series-dot.active { r: 6; }
        .trend-chart .end-label { fill: #1a202c; font-size: 12px; font-weight: 600; }
        .trend-chart .crosshair { stroke: #a0aec0; stroke-width: 1; }
        .trend-tooltip {
            position: absolute;
            pointer-events: none;
            background: #1a202c;
            color: #fff;
            font-size: 12px;
            line-height: 1.4;
            padding: 6px 10px;
            border-radius: 6px;
            white-space: nowrap;
            transform: translate(-50%, calc(-100% - 12px));
            display: none;
        }
        .trend-tooltip .tt-key {
            display: inline-block;
            width: 12px;
            height: 2px;
            background: #4fd1c5;
            vertical-align: middle;
            margin-right: 6px;
        }
        .trend-empty {
            color: #718096;
            font-size: 0.95rem;
            padding: 16px 0 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>My Prediction History</h1>

    <?php if ($trend): ?>
        <section class="trend-card" aria-labelledby="trendTitle">
            <div class="trend-head">
                <div>
                    <h2 id="trendTitle">Risk score over time</h2>
                    <p class="trend-sub">0 to 100. Readings above 50 are classed as high risk.</p>
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
                <p class="trend-empty">Take another assessment later to see how your score changes over time.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Risk score</th>
                <th>Age</th>
                <th>BP</th>
                <th>Cholesterol</th>
                <th>Max Heart Rate</th>
                <th>ST Depression</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows): ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['timestamp'] ?? 'N/A') ?></td>
                        <td>
                            <?php if ($row['risk_score'] === null): ?>
                                <span class="risk-pill none">Not scored</span>
                            <?php else: ?>
                                <?php $high = isHighRisk($row['risk_score']); ?>
                                <span class="risk-pill <?= $high ? 'high' : 'low' ?>">
                                    <?= riskPercent($row['risk_score']) ?> &middot; <?= $high ? 'High' : 'Low' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$row['age'] ?></td>
                        <td><?= (int)$row['trestbps'] ?></td>
                        <td><?= (int)$row['chol'] ?></td>
                        <td><?= (int)$row['thalach'] ?></td>
                        <td><?= htmlspecialchars($row['oldpeak']) ?></td>
                        <td class="actions">
                            <a class="btn-view" href="viewResult.php?id=<?= $row['pdid'] ?>">View</a>
                            <a class="btn-pdf" href="downloadAssessment.php?id=<?= $row['pdid'] ?>">PDF</a>
                            <a class="btn-edit" href="editData.php?id=<?= $row['pdid'] ?>">Edit</a>
                            <a class="btn-delete" href="deleteData.php?id=<?= $row['pdid'] ?>" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No prediction records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="form.php" class="button">+ New Prediction</a>
    <a href="index.php" class="button">Back</a>
</div>

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
