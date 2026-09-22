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


def main():
    if len(sys.argv) < 2:
        print("<div class='risk-card high-risk'><h2>Error</h2><p>No input data file provided.</p></div>")
        return

    json_file = sys.argv[1]

    with open(json_file, 'r', encoding='utf-8') as f:
        data = json.load(f)

    if not os.path.isfile(MODEL_FILE):
        print("<div class='risk-card high-risk'><h2>Error</h2><p>Model file not found. Run train_model.py first.</p></div>")
        return

    model = RandomForest.load(MODEL_FILE)
    features = [float(data[name]) for name in model.feature_names]

    prediction = model.predict(features)

    if prediction == 1:
        reasons = get_reasoning(data)
        print("<div class='risk-card high-risk'>")
        print("  <div class='risk-badge-header'>")
        print("    <span class='risk-icon'>&#128680;</span>")
        print("    <div class='risk-title-group'>")
        print("      <h2>High Risk of Heart Disease</h2>")
        print("      <p class='risk-subtitle'>Please consult a doctor or cardiologist as soon as possible for a comprehensive evaluation.</p>")
        print("    </div>")
        print("  </div>")
        if reasons:
            print("  <div class='reasons-block'>")
            print("    <h3>Key Contributing Risk Factors:</h3>")
            print("    <ul class='reasons-list'>")
            for r in reasons:
                print(f"      <li>{r}</li>")
            print("    </ul>")
            print("  </div>")
        print("</div>")
    else:
        reasons = get_reasoning(data)
        print("<div class='risk-card low-risk'>")
        print("  <div class='risk-badge-header'>")
        print("    <span class='risk-icon'>&#9989;</span>")
        print("    <div class='risk-title-group'>")
        print("      <h2>Low Risk of Heart Disease</h2>")
        print("      <p class='risk-subtitle'>Your assessment results indicate a low overall heart disease risk.</p>")
        print("    </div>")
        print("  </div>")
        if reasons:
            print("  <div class='reasons-block'>")
            print("    <h3>Health Factors to Keep in Mind:</h3>")
            print("    <ul class='reasons-list'>")
            for r in reasons:
                print(f"      <li>{r}</li>")
            print("    </ul>")
            print("  </div>")
        print("</div>")

        
if __name__ == "__main__":
    main()
