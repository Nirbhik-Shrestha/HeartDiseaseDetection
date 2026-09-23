<?php
/**
 * Scoring readings with the heart-disease model, and showing the result.
 *
 * patient/predict.py does the maths and returns JSON; everything visual is
 * rendered here, so the patient's result page, the doctor's view and the PDF
 * all present a reading the same way.
 *
 * Each reading's score is stored in patient_data.risk_score the first time it
 * is computed. A NULL score (older readings, edited readings) is filled in
 * the next time the reading is shown. After retraining the model, run
 *   UPDATE patient_data SET risk_score = NULL;
 * so every reading is re-scored with the new model.
 */

require_once __DIR__ . '/assessment.php';

// The Python interpreter that runs predict.py. Set the PYTHON_BIN environment
// variable to override it on another machine.
define('PYTHON_BIN', getenv('PYTHON_BIN')
    ?: 'C:\\Users\\nirbh\\AppData\\Local\\Programs\\Python\\Python313\\python.exe');

/** True when a score means "high risk"; the forest's majority vote. */
function isHighRisk($score)
{
    return (float)$score > 0.5;
}

/** The score as a whole percentage, for display. */
function riskPercent($score)
{
    return (int)round((float)$score * 100);
}

/**
 * Run the model on several readings in one Python call.
 *
 * @param array[] $rows patient_data rows (only the 13 feature columns are sent)
 * @return array[]|null One ['prediction', 'risk_score', 'reasons'] per row,
 *                      in order, or null when the model could not be run.
 */
function runPredictions(array $rows)
{
    if (!$rows) {
        return [];
    }

    $payload = [];
    foreach ($rows as $row) {
        $features = [];
        foreach (array_keys(ASSESSMENT_FIELDS) as $name) {
            $features[$name] = $name === 'oldpeak' ? (float)$row[$name] : (int)$row[$name];
        }
        $payload[] = $features;
    }

    $tmpfile = tempnam(sys_get_temp_dir(), 'json_');
    file_put_contents($tmpfile, json_encode($payload));

    $script = __DIR__ . DIRECTORY_SEPARATOR . 'patient' . DIRECTORY_SEPARATOR . 'predict.py';
    // stderr is left out of the capture so a Python warning cannot corrupt
    // the JSON; it ends up in the web server's error log instead.
    $output = shell_exec('"' . PYTHON_BIN . '" "' . $script . '" "' . $tmpfile . '"');
    unlink($tmpfile);

    $results = json_decode((string)$output, true);
    if (!is_array($results) || isset($results['error']) || count($results) !== count($payload)) {
        error_log('predict.py failed: ' . substr((string)$output, 0, 500));
        return null;
    }
    return $results;
}

/**
 * Make sure every row has a risk_score, scoring and saving any that do not.
 *
 * Rows are updated in place. If the model cannot be run, unscored rows keep
 * a NULL score and the caller shows them as "not scored".
 */
function ensureRiskScores($con, array &$rows)
{
    $missing = [];
    foreach ($rows as $i => $row) {
        if ($row['risk_score'] === null) {
            $missing[$i] = $row;
        }
    }
    if (!$missing) {
        return;
    }

    $results = runPredictions(array_values($missing));
    if ($results === null) {
        return;
    }

    $stmt = $con->prepare("UPDATE patient_data SET risk_score = ? WHERE pdid = ?");
    foreach (array_keys($missing) as $n => $i) {
        $score = (float)$results[$n]['risk_score'];
        $pdid = (int)$rows[$i]['pdid'];
        $stmt->bind_param("di", $score, $pdid);
        $stmt->execute();
        $rows[$i]['risk_score'] = $score;
    }
    $stmt->close();
}

/**
 * Everything needed to present one reading.
 *
 * @return array|null ['risk_score' => float, 'high_risk' => bool, 'reasons' => string[]],
 *                    or null when the model could not be run.
 */
function assessReading($con, array $row)
{
    $results = runPredictions([$row]);
    if ($results === null) {
        return null;
    }

    // The stored score wins, so this page agrees with the history chart;
    // it is only missing for readings not scored yet.
    if ($row['risk_score'] === null) {
        $rows = [$row];
        ensureRiskScores($con, $rows);
        $score = $rows[0]['risk_score'] !== null ? (float)$rows[0]['risk_score'] : (float)$results[0]['risk_score'];
    } else {
        $score = (float)$row['risk_score'];
    }

    return [
        'risk_score' => $score,
        'high_risk'  => isHighRisk($score),
        'reasons'    => $results[0]['reasons'],
    ];
}

/** The coloured result card with the score meter and contributing factors. */
function renderRiskCard(array $assessment)
{
    $high = $assessment['high_risk'];
    $pct = riskPercent($assessment['risk_score']);
    ?>
    <div class="risk-card <?= $high ? 'high-risk' : 'low-risk' ?>">
        <div class="risk-badge-header">
            <span class="risk-icon"><?= $high ? '&#128680;' : '&#9989;' ?></span>
            <div class="risk-title-group">
                <h2><?= $high ? 'High Risk of Heart Disease' : 'Low Risk of Heart Disease' ?></h2>
                <p class="risk-subtitle">
                    <?= $high
                        ? 'Please consult a doctor or cardiologist as soon as possible for a comprehensive evaluation.'
                        : 'Your assessment results indicate a low overall heart disease risk.' ?>
                </p>
            </div>
        </div>

        <div class="risk-score">
            <div class="risk-score-row">
                <span class="risk-score-label">Risk score</span>
                <span class="risk-score-value"><?= $pct ?><small>/100</small></span>
            </div>
            <div class="risk-meter" role="img" aria-label="Risk score <?= $pct ?> out of 100">
                <div class="risk-meter-fill" style="width: <?= max($pct, 2) ?>%"></div>
                <div class="risk-meter-threshold" title="Scores above 50 are classed as high risk"></div>
            </div>
            <p class="risk-score-note">
                The score is the average vote of the model's decision trees, from 0 (none point to heart disease)
                to 100 (all do). Scores above 50 are classed as high risk.
            </p>
        </div>

        <?php if ($assessment['reasons']): ?>
            <div class="reasons-block">
                <h3><?= $high ? 'Key Contributing Risk Factors:' : 'Health Factors to Keep in Mind:' ?></h3>
                <ul class="reasons-list">
                    <?php foreach ($assessment['reasons'] as $reason): ?>
                        <li><?= htmlspecialchars($reason) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
