"""Benchmark only - NOT used by the web application.

Trains scikit-learn's RandomForestClassifier on exactly the same split as
train_model.py, so the from-scratch implementation can be verified against a
reference. Nothing here is imported by predict.py.

Usage:  python train_model_sklearn.py
"""

from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report

from ml.data import load_csv, train_test_split


def main():
    X, y, feature_names = load_csv()
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42)

    model = RandomForestClassifier(n_estimators=100, random_state=42, oob_score=True)
    model.fit(X_train, y_train)

    predictions = model.predict(X_test)
    print("[sklearn reference] test accuracy : %.4f"
          % accuracy_score(y_test, predictions))
    print("[sklearn reference] out-of-bag    : %.4f" % model.oob_score_)
    print()
    print(classification_report(y_test, predictions,
                                target_names=["No disease", "Disease"]))

    print("Feature importances:")
    for name, importance in sorted(zip(feature_names, model.feature_importances_),
                                   key=lambda pair: pair[1], reverse=True):
        print("  %-10s %.4f" % (name, importance))


if __name__ == "__main__":
    main()
