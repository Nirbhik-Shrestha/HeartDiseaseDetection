"""Score heart-disease readings with the trained random forest.

Usage: python predict.py <input.json>

The input file holds one reading (a JSON object) or several (a JSON list),
each keyed by the patient_data column names. The output, on stdout, is JSON
in the same shape (object in -> object out, list in -> list out), one result
per reading:

    {"prediction": 0 or 1, "risk_score": 0.0-1.0, "reasons": ["...", ...]}

risk_score is the forest's probability of "heart disease" (each tree's vote,
averaged); prediction is 1 exactly when risk_score > 0.5. The web pages
render the result themselves (see prediction.php in the project root).
"""

import sys
import json
import os

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ml.forest import RandomForest

if hasattr(sys.stdout, 'reconfigure'):
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

MODEL_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                          'heart_model.json')


def get_reasoning(data):
    reasons = []

    if data['trestbps'] > 130:
        reasons.append("High resting blood pressure (above 130 mm Hg)")

    if data['chol'] > 240:
        reasons.append("High serum cholesterol (above 240 mg/dl)")

    if data['fbs'] == 1:
        reasons.append("High fasting blood sugar (above 120 mg/dl)")

    if data['thalach'] < 120:
        reasons.append("Low maximum heart rate achieved (below 120 bpm)")

    if data['oldpeak'] > 2:
        reasons.append("Significant ST depression induced by exercise (> 2.0)")

    if data['cp'] in [2, 3]:
        reasons.append("Type of chest pain suggests potential heart condition")

    if data['exang'] == 1:
        reasons.append("Exercise-induced angina (chest pain during exertion)")

    if data['slope'] == 2:
        reasons.append("Flat or downsloping ST segment during peak exercise")

    if data['ca'] > 0:
        reasons.append("Presence of major blood vessels colored by fluoroscopy")

    if data['thal'] == 2:
        reasons.append("Presence of fixed defect in thalassemia test")

    return reasons


def model_features(model, data):
    """The reading as the model's feature vector.

    The form and patient_data code thal as 1 = normal, 2 = fixed defect,
    3 = reversible defect, but the training set (heart_cleveland_upload.csv)
    codes it 0 / 1 / 2. Shift it here so "normal" is not read as a defect.
    """
    row = dict(data)
    row['thal'] = float(data['thal']) - 1
    return [float(row[name]) for name in model.feature_names]


def score(model, data):
    proba = model.predict_proba(model_features(model, data))
    return {
        # Same tie-break as RandomForest.predict(): class 0 wins a 50/50 vote.
        "prediction": 1 if proba[1] > proba[0] else 0,
        "risk_score": round(proba[1], 4),
        "reasons": get_reasoning(data),
    }


def main():
    if '--help' in sys.argv[1:] or '-h' in sys.argv[1:]:
        print(__doc__.strip())
        return 0

    if len(sys.argv) < 2:
        print(json.dumps({"error": "No input data file provided."}))
        return 1

    if not os.path.isfile(MODEL_FILE):
        print(json.dumps({"error": "Model file not found. Run train_model.py first."}))
        return 1

    with open(sys.argv[1], 'r', encoding='utf-8') as f:
        payload = json.load(f)

    model = RandomForest.load(MODEL_FILE)

    if isinstance(payload, list):
        result = [score(model, reading) for reading in payload]
    else:
        result = score(model, payload)

    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    sys.exit(main())
