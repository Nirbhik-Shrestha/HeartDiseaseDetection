<?php
    include("../connection.php");
    include_once("../auth.php");
    include_once("../assessment.php");

    $userfetch = requireRole($con, 'patient');
    $useremail = $userfetch["pemail"];
    $username  = $userfetch["pname"]; // shown in the header's account menu

    // Set by submit.php when the last attempt failed validation.
    $errors = isset($_SESSION['assessment_errors']) ? $_SESSION['assessment_errors'] : [];
    $old    = isset($_SESSION['assessment_old']) ? $_SESSION['assessment_old'] : [];
    unset($_SESSION['assessment_errors'], $_SESSION['assessment_old']);

    // Questions per section, for the progress rail's "0/3" counts.
    $groupSizes = array_count_values(array_column(ASSESSMENT_FIELDS, 'group'));
    $totalQuestions = count(ASSESSMENT_FIELDS);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Heart Disease Prediction - DaaktarSahab</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="../css/site.css" />
    <style>
/*
 * Heart check form. The banner and the question fields come from
 * css/site.css (shared with the other pages); this block holds
 * what only this page has: the progress rail, the "Before you start" box and
 * the sticky submit bar. It keeps its own sans-serif font, as designed.
 */

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
}

body {
  background: #f5f8fa;
  color: #3d566b;
}

/* ---------- Two columns: progress rail + form ---------- */
.container {
  position: relative;
  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);
  gap: 28px;
  align-items: start;
  max-width: 1120px;
  margin: -56px auto 64px;
  padding: 0 32px;
  background: none;
  box-shadow: none;
  border-radius: 0;
}

.form-main {
  min-width: 0;
}

.form-progress {
  position: sticky;
  top: 96px;
  padding: 20px;
  background: #fff;
  border: 1px solid #e3ebf0;
  border-radius: 16px;
  box-shadow: 0 1px 2px rgba(18, 48, 74, 0.06), 0 12px 32px -12px rgba(18, 48, 74, 0.16);
}

.form-progress__title {
  margin: 0 0 4px;
  color: #00786f;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.form-progress__count {
  margin: 0 0 12px;
  color: #6b8193;
  font-size: 14px;
}

.form-progress__bar {
  height: 6px;
  margin-bottom: 16px;
  border-radius: 99px;
  background: #e6f6f4;
  overflow: hidden;
}

.form-progress__bar i {
  display: block;
  width: 0;
  height: 100%;
  border-radius: inherit;
  background: #00a99d;
  transition: width 0.3s;
}

.form-progress ol {
  display: grid;
  gap: 4px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.form-progress a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 9px 10px;
  border-radius: 10px;
  color: #3d566b;
  font-size: 14.5px;
  text-decoration: none;
}

.form-progress a:hover {
  background: #f5f8fa;
}

.form-progress a b {
  flex: none;
  display: grid;
  place-items: center;
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #f5f8fa;
  border: 1px solid #e3ebf0;
  color: #3d566b;
  font-size: 12.5px;
}

.form-progress a small {
  margin-left: auto;
  color: #6b8193;
  font-size: 12.5px;
}

.form-progress a.is-active {
  background: #e6f6f4;
  color: #12304a;
  font-weight: 600;
}

.form-progress a.is-active b {
  border-color: #00a99d;
  color: #00786f;
}

.form-progress a.is-done b {
  background: #00a99d;
  border-color: #00a99d;
  color: #fff;
  font-size: 0;
}

.form-progress a.is-done b::after {
  content: "\2713";
  font-size: 13px;
}

.form-progress__privacy {
  margin: 16px 0 0;
  padding-top: 14px;
  border-top: 1px solid #e3ebf0;
  color: #6b8193;
  font-size: 13px;
  line-height: 1.5;
}

/* ---------- "Before you start" box ---------- */
.info-box {
  margin: 0 0 20px;
  overflow: hidden;
  background: #fff;
  border: 1px solid #e3ebf0;
  border-radius: 16px;
  box-shadow: 0 1px 2px rgba(18, 48, 74, 0.06), 0 12px 32px -12px rgba(18, 48, 74, 0.16);
  color: #3d566b;
  line-height: 1.55;
}

.info-box > summary {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 22px;
  list-style: none;
  cursor: pointer;
  color: #12304a;
  font-weight: 700;
}

.info-box > summary::-webkit-details-marker {
  display: none;
}

.info-box > summary::before {
  content: "i";
  flex: none;
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #fff4e0;
  color: #b7791f;
  font-weight: 700;
  font-style: italic;
}

.info-box > summary::after {
  content: "";
  width: 9px;
  height: 9px;
  margin-left: auto;
  border-right: 2px solid #6b8193;
  border-bottom: 2px solid #6b8193;
  transform: rotate(45deg);
  transition: transform 0.2s;
}

.info-box[open] > summary::after {
  transform: rotate(-135deg);
}

.info-box > :not(summary) {
  margin: 10px 22px 0;
}

.info-box > :last-child {
  margin-bottom: 20px;
}

.info-box ul {
  list-style: none;
}

.info-box li {
  position: relative;
  margin: 8px 0;
  padding-left: 26px;
}

.info-box li::before {
  content: "";
  position: absolute;
  left: 2px;
  top: 0.45em;
  width: 12px;
  height: 7px;
  border-left: 2px solid #00a99d;
  border-bottom: 2px solid #00a99d;
  transform: rotate(-45deg);
}

.info-box a {
  color: #00786f;
  font-weight: 600;
}

/* ---------- Validation errors from submit.php ---------- */
.form-errors {
  margin: 0 0 20px;
  padding: 16px 22px;
  background: #fff5f5;
  border: 1px solid #feb2b2;
  border-radius: 16px;
  color: #742a2a;
}

.form-errors ul {
  margin: 8px 0 0 20px;
}

/* ---------- Sticky submit bar ---------- */
.form-submit {
  position: sticky;
  bottom: 16px;
  z-index: 5;
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 14px 14px 22px;
  border-radius: 16px;
  background: #12304a;
  color: #d6e0e8;
  box-shadow: 0 18px 40px -16px rgba(18, 48, 74, 0.55);
}

.form-submit__count {
  font-size: 14.5px;
}

.form-submit__count b {
  color: #fff;
}

.form-submit button[type="submit"] {
  width: auto;
  margin: 0 0 0 auto;
  padding: 13px 26px;
  border: 0;
  border-radius: 10px;
  background: #00a99d;
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, transform 0.15s;
}

.form-submit button[type="submit"]:hover {
  background: #00c2b4;
  transform: translateY(-1px);
}

/* ---------- Narrow screens ---------- */
@media (max-width: 900px) {
  .container {
    grid-template-columns: 1fr;
    margin-top: -48px;
    padding: 0 16px;
  }

  .form-progress {
    position: static;
    padding: 16px;
  }

  /* Keep the count and bar; the section list is too long for a phone. */
  .form-progress ol {
    display: none;
  }

  .form-submit {
    bottom: 8px;
    padding: 12px 12px 12px 16px;
  }
}
    </style>
</head>
<body class="site-page">

<?php include('../patientHeader.html'); ?>

<section class="page-hero">
    <div class="page-hero__inner">
        <span class="page-hero__eyebrow">Heart check</span>
        <h1>Heart Disease Prediction</h1>
        <p>Answer <?= $totalQuestions ?> questions from your health reports to estimate your heart disease risk.</p>
        <div class="page-hero__meta">
            <span><?= $totalQuestions ?> questions</span>
            <span>About 5 minutes</span>
            <span>Keep your test reports nearby</span>
        </div>
    </div>
</section>

<div class="container">

    <aside class="form-progress" aria-label="Form progress">
        <p class="form-progress__title">Your progress</p>
        <p class="form-progress__count"><span data-answered>0</span> of <?= $totalQuestions ?> answered</p>
        <div class="form-progress__bar"><i></i></div>
        <ol>
            <?php $n = 0; foreach (ASSESSMENT_GROUPS as $key => $group): $n++; ?>
                <li>
                    <a href="#group-<?= $key ?>" data-group="group-<?= $key ?>">
                        <b><?= $n ?></b>
                        <span><?= htmlspecialchars($group['title']) ?></span>
                        <small data-count>0/<?= $groupSizes[$key] ?></small>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
        <p class="form-progress__privacy">🔐 Your answers are private. You choose whether to share a result with a doctor.</p>
    </aside>

    <div class="form-main">
        <details class="info-box" open>
            <summary>Before you start: what you will need</summary>
            <p>This assessment uses the same 13 measurements cardiologists record. Some you know already; others come from test reports:</p>
            <ul>
                <li><strong>Blood tests</strong>: a lipid profile (cholesterol) and a fasting blood sugar test.</li>
                <li><strong>ECG and exercise stress test</strong> (treadmill test / TMT).</li>
                <li><strong>Coronary angiogram</strong> and a <strong>thallium stress scan</strong>, if you have had them.</li>
            </ul>
            <p>Each question below says where to find the value. Enter exactly what the report says. Guessing makes the result less reliable.</p>
            <p>Haven't had some of these tests? <a href="doctors.php?search=Cardiologist">Book a cardiologist</a>. They can order the tests and go through the values with you.</p>
        </details>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <strong>Please check your answers:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="submit.php">
            <?php renderAssessmentFields($old); ?>

            <div class="form-submit">
                <span class="form-submit__count"><b data-answered>0</b> of <?= $totalQuestions ?> answered</span>
                <button type="submit">See my heart risk</button>
            </div>
        </form>
    </div>

</div>

<?php include('../footer.html'); ?>

<script src="form-progress.js" defer></script>
</body>
</html>
