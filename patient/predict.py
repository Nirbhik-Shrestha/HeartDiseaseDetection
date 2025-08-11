import sys
import json
import joblib
import pandas as pd

def get_reasoning(data):
    reasons = []

    if data['trestbps'] > 130:
        reasons.append("High resting blood pressure")

    if data['chol'] > 240:
        reasons.append("High cholesterol")

    if data['fbs'] == 1:
        reasons.append("High fasting blood sugar")

    if data['thalach'] < 120:
        reasons.append("Low maximum heart rate achieved")

    if data['oldpeak'] > 2:
        reasons.append("Significant ST depression induced by exercise")

    if data['cp'] in [2, 3]:
        reasons.append("Type of chest pain suggests possible heart condition")

    if data['exang'] == 1:
        reasons.append("Exercise-induced angina")

    if data['slope'] == 2:
        reasons.append("Flat ST slope")

    if data['ca'] > 0:
        reasons.append("Presence of major vessels colored by fluoroscopy")

    if data['thal'] == 2:
        reasons.append("Presence of fixed defect in thalassemia test")

    return reasons


def main():
    # The first argument is the path to the JSON file
    json_file = sys.argv[1]

    # Load JSON data from file
    with open(json_file, 'r') as f:
        data = json.load(f)

    # Just print to verify it loads correctly (replace with your prediction logic)
    # print("Received data:")
    # print(data)

    model = joblib.load('heart_disease_model.pkl')
    features = pd.DataFrame([data], columns=[
    'age', 'sex', 'cp', 'trestbps', 'chol', 'fbs', 'restecg',
    'thalach', 'exang', 'oldpeak', 'slope', 'ca', 'thal'
])

    prediction = model.predict(features)

    if prediction[0] == 1:
        reasons = get_reasoning(data)
        print("<p style='color:red;text-align:center;font-size:20px;'>High Risk of Heart Disease.\nPlease consult to the doctor as soon as possible.</p>")
        if reasons:
            print("Reason(s): \n<ul><li>" + "\n<li>".join(reasons) + "</ul>")
    else:
        reasons = get_reasoning(data)
        print("<p style='color:green;text-align:center;font-size:20px;'>Low Risk of Heart Disease.</p>")
        if reasons:
            print("However, Warning!: \n<ul><li>" + "\n<li>".join(reasons) + "</ul>")

        
if __name__ == "__main__":
    main()
