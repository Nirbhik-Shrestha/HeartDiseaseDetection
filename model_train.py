import pandas as pd
import numpy as np
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.preprocessing import StandardScaler
import joblib
import json

# Load the Cleveland Heart Disease dataset
def load_data(filename='heart_cleveland_upload.csv'):
    data = pd.read_csv(filename)
    
    # If your data doesn't have headers, add them
    if 'age' not in data.columns:
        column_names = ['age', 'sex', 'cp', 'trestbps', 'chol', 'fbs', 'restecg', 
                        'thalach', 'exang', 'oldpeak', 'slope', 'ca', 'thal', 'target']
        data.columns = column_names
    
    # In some datasets, the target is multi-class (0, 1, 2, 3, 4)
    # Convert to binary (0 = no disease, 1 = disease)
    if data['target'].max() > 1:
        data['target'] = data['target'].apply(lambda x: 1 if x > 0 else 0)
    
    return data

def preprocess_data(data):
    # Handle missing values
    # Some datasets use ? for missing values
    for col in data.columns:
        if data[col].dtype == object:
            data[col] = pd.to_numeric(data[col], errors='coerce')
    
    # Fill missing values
    data = data.fillna(data.median())
    
    # Split features and target
    X = data.drop('target', axis=1)
    y = data['target']
    
    # Split data into training and testing sets
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    
    # Scale features
    scaler = StandardScaler()
    X_train = scaler.fit_transform(X_train)
    X_test = scaler.transform(X_test)
    
    return X_train, X_test, y_train, y_test, scaler

def train_model(X_train, y_train):
    # Initialize and train Random Forest classifier
    rf_model = RandomForestClassifier(
        n_estimators=100,
        max_depth=10,
        min_samples_split=5,
        min_samples_leaf=2,
        random_state=42
    )
    
    rf_model.fit(X_train, y_train)
    return rf_model

def evaluate_model(model, X_test, y_test):
    # Make predictions
    y_pred = model.predict(X_test)
    
    # Evaluate the model
    accuracy = accuracy_score(y_test, y_pred)
    print(f"Accuracy: {accuracy:.4f}")
    
    # Print detailed classification report
    print("\nClassification Report:")
    print(classification_report(y_test, y_pred))
    
    # Print confusion matrix
    print("\nConfusion Matrix:")
    print(confusion_matrix(y_test, y_pred))
    
    return accuracy

def save_model_for_web(model, scaler, feature_names):
    # Save the scaler
    joblib.dump(scaler, 'heart_disease_scaler.pkl')
    
    # Save the full model
    joblib.dump(model, 'heart_disease_model.pkl')
    
    # Extract feature importances
    feature_importance = dict(zip(feature_names, model.feature_importances_))
    
    # Sort features by importance
    sorted_importance = {k: v for k, v in sorted(
        feature_importance.items(), 
        key=lambda item: item[1], 
        reverse=True
    )}
    
    # Save feature importances to JSON for the web app
    with open('feature_importance.json', 'w') as f:
        json.dump(sorted_importance, f)
    
    print("Model and related files saved successfully")
    
    # Output JavaScript code for model implementation
    print("\nJavaScript model implementation:")
    print("const MODEL = {")
    print("  featureImportance: {")
    for feature, importance in sorted_importance.items():
        print(f"    '{feature}': {importance:.4f},")
    print("  },")
    print("  // More implementation details would go here")
    print("};")

def convert_model_to_js_rules(model, feature_names):
    """
    Convert a trained Random Forest model to a simplified JavaScript representation.
    This is just a demonstration and not a complete conversion.
    """
    # Extract a few trees from the model
    trees = model.estimators_[:5]  # Take first 5 trees for example
    
    print("\nExample JavaScript rules (simplified):")
    for i, tree in enumerate(trees[:3]):  # Show 3 examples
        print(f"// Tree {i+1} example")
        tree_rules = extract_tree_rules(tree, feature_names)
        print(tree_rules)
        print()

def extract_tree_rules(tree, feature_names, node_id=0, depth=0):
    """
    Recursively extract rules from a decision tree.
    This is a simplified version for demonstration.
    """
    # Get tree structure
    left = tree.tree_.children_left
    right = tree.tree_.children_right
    threshold = tree.tree_.threshold
    features = tree.tree_.feature
    values = tree.tree_.value
    
    # If leaf node
    if left[node_id] == -1:
        class_idx = np.argmax(values[node_id])
        return f"return {class_idx};"
    
    # Get feature name
    feature = feature_names[features[node_id]]
    
    # Recursive rule building
    indent = "  " * depth
    rule = f"{indent}if (data.{feature} <= {threshold[node_id]:.2f}) {{\n"
    rule += f"{indent}{extract_tree_rules(tree, feature_names, left[node_id], depth+1)}\n"
    rule += f"{indent}}} else {{\n"
    rule += f"{indent}{extract_tree_rules(tree, feature_names, right[node_id], depth+1)}\n" 
    rule += f"{indent}}}"
    
    return rule

def main():
    # Load and preprocess data
    print("Loading and preprocessing data...")
    data = load_data()
    X_train, X_test, y_train, y_test, scaler = preprocess_data(data)
    
    # Train the model
    print("\nTraining Random Forest model...")
    model = train_model(X_train, y_train)
    
    # Evaluate the model
    print("\nEvaluating model...")
    evaluate_model(model, X_test, y_test)
    
    # Save model for web use
    print("\nSaving model for web application...")
    feature_names = list(data.drop('target', axis=1).columns)
    save_model_for_web(model, scaler, feature_names)
    
    # Convert model to JavaScript rules
    print("\nGenerating simplified JavaScript rules...")
    convert_model_to_js_rules(model, feature_names)

if __name__ == "__main__":
    main()