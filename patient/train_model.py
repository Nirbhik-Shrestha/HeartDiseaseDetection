import os
import sys
import time

from ml.data import load_csv, train_test_split
from ml.forest import RandomForest
from ml.metrics import accuracy_score, classification_report, confusion_matrix

MODEL_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                          "heart_model.json")

LABELS = ["No disease", "Disease"]


def main():
    print("Loading dataset...")
    X, y, feature_names = load_csv()
    print("  %d rows, %d features" % (len(X), len(feature_names)))

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42)
    print("  %d training rows, %d test rows" % (len(X_train), len(X_test)))

    print("\nTraining Random Forest (100 trees, from scratch)...")
    started = time.time()
    model = RandomForest(
        n_estimators=100,
        max_depth=None,
        min_samples_split=2,
        min_samples_leaf=1,
        max_features="sqrt",
        random_state=42,
    ).fit(X_train, y_train, feature_names=feature_names)
    print("  done in %.1f seconds" % (time.time() - started))

    predictions = model.predict_many(X_test)

    print("\nTest accuracy : %.4f" % accuracy_score(y_test, predictions))
    if model.oob_score_ is not None:
        print("Out-of-bag    : %.4f" % model.oob_score_)

    print("\nClassification report:")
    print(classification_report(y_test, predictions, n_classes=2, labels=LABELS))

    print("\nConfusion matrix (rows = actual, columns = predicted):")
    matrix = confusion_matrix(y_test, predictions, n_classes=2)
    print("%14s %12s %12s" % ("", "pred " + LABELS[0], "pred " + LABELS[1]))
    for i, row in enumerate(matrix):
        print("%14s %12d %12d" % ("actual " + LABELS[i], row[0], row[1]))

    print("\nFeature importances (Gini, averaged over all trees):")
    ranked = sorted(zip(feature_names, model.feature_importances_),
                    key=lambda pair: pair[1], reverse=True)
    for name, importance in ranked:
        bar = "#" * int(round(importance * 100))
        print("  %-10s %.4f  %s" % (name, importance, bar))

    size = model.save(MODEL_FILE)
    print("\nModel saved to %s (%.0f KB)" % (MODEL_FILE, size / 1024.0))


if __name__ == "__main__":
    sys.exit(main())
