from flask import Flask, request, jsonify, render_template
import numpy as np
import joblib
import os

app = Flask(__name__, static_folder='static', template_folder='templates')

# Load the trained model and scaler
model_path = 'models/heart_disease_model.pkl'
scaler_path = 'models/heart_disease_scaler.pkl'

# Create directory if it doesn't exist
os.makedirs('models', exist_ok=True)

# Check if model exists, if not create a dummy model
if not os.path.exists(model_path) or not os.path.exists(scaler_path):
    print("Warning: Model files not found. Please train the model first.")
    print("Loading fallback model for demonstration.")
    
    # Import necessary libraries for fallback model
    from sklearn.ensemble import RandomForestClassifier
    from sklearn.preprocessing import StandardScaler
    
    # Create a dummy model with reasonable defaults
    model = RandomForestClassifier(n_estimators=100, random_state=42)
    scaler = StandardScaler()
    
    # Create some dummy data to fit the model
    X_dummy = np.random.rand(100, 13)
    y_dummy = np.random.randint(0, 2, 100)
    
    # Fit scaler and model
    X_dummy_scaled = scaler.fit_transform(X_dummy)
    model.fit(X_dummy_scaled, y_dummy)
    
    # Save the dummy model and scaler
    joblib.dump(model, model_path)
    joblib.dump(scaler, scaler_path)
    print("Fallback model created and saved.")
else:
    # Load the existing model and scaler
    model = joblib.load(model_path)
    scaler = joblib.load(scaler_path)
    print("Model and scaler loaded successfully.")

# Feature names in the correct order
feature_names = ['age', 'sex', 'cp', 'trestbps', 'chol', 'fbs', 'restecg', 
                'thalach', 'exang', 'oldpeak', 'slope', 'ca', 'thal']

@app.route('/')
def home():
    """Render the home page"""
    return render_template('index.html')

@app.route('/predict', methods=['POST'])
def predict():
    """API endpoint for making predictions"""
    try:
        # Get data from request
        data = request.get_json()
        
        # Create input array in the correct order
        input_data = []
        for feature in feature_names:
            input_data.append(float(data[feature]))
        
        # Convert to numpy array and reshape
        input_array = np.array(input_data).reshape(1, -1)
        
        # Scale the input
        scaled_input = scaler.transform(input_array)
        
        # Make prediction
        prediction = model.predict(scaled_input)[0]
        
        # Get probability scores
        probabilities = model.predict_proba(scaled_input)[0]
        
        # Get feature importances for this prediction
        if hasattr(model, 'feature_importances_'):
            importances = dict(zip(feature_names, model.feature_importances_))
            # Sort by importance
            importances = {k: float(v) for k, v in sorted(
                importances.items(), 
                key=lambda item: item[1], 
                reverse=True
            )}
        else:
            importances = {}
        
        # Return prediction and explanations
        return jsonify({
            'prediction': int(prediction),
            'probability': float(probabilities[1]),  # Probability of having heart disease
            'feature_importance': importances
        })
    except Exception as e:
        return jsonify({
            'error': str(e),
            'message': 'Error processing prediction request'
        }), 400

@app.route('/feature-importance')
def feature_importance():
    """Return the overall feature importance from the model"""
    if hasattr(model, 'feature_importances_'):
        importances = dict(zip(feature_names, model.feature_importances_))
        # Sort by importance
        importances = {k: float(v) for k, v in sorted(
            importances.items(), 
            key=lambda item: item[1], 
            reverse=True
        )}
        return jsonify(importances)
    else:
        return jsonify({'error': 'Feature importance not available'})

if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5000)