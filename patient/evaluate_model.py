"""Check how accurate the saved heart-disease model is, without retraining it.

Usage (run from the patient/ folder):
    python evaluate_model.py            # score heart_model.json on the test split
    python evaluate_model.py --cv 5     # also run 5-fold cross-validation

The test split is rebuilt with the same seed train_model.py used
(test_size=0.2, random_state=42), so these are the 59 records the saved model
never saw during training. Cross-validation trains fresh forests in memory
only; heart_model.json is never overwritten.
"""

import os
import random
import statistics
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from ml.data import load_csv, train_test_split
from ml.forest import RandomForest
from ml.metrics import accuracy_score, classification_report, confusion_matrix

MODEL_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), "heart_model.json")
LABELS = ["No disease", "Disease"]


def evaluate_saved_model():
    X, y, _ = load_csv()
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)
    model = RandomForest.load(MODEL_FILE)

    train_pred = model.predict_many(X_train)
    test_pred = model.predict_many(X_test)

    print("Saved model: %s" % MODEL_FILE)
    print("  trees: %d, stored out-of-bag score: %.4f" % (len(model.trees), model.oob_score_ or 0))
    print("\nTraining accuracy : %.4f  (%d rows, the model has seen these)" % (accuracy_score(y_train, train_pred), len(y_train)))
    print("Test accuracy     : %.4f  (%d rows, never seen)" % (accuracy_score(y_test, test_pred), len(y_test)))

    print("\nClassification report (test set):")
    print(classification_report(y_test, test_pred, n_classes=2, labels=LABELS))

    m = confusion_matrix(y_test, test_pred, n_classes=2)
    print("\nConfusion matrix (rows = actual, columns = predicted):")
    print("%22s %12s" % ("pred No disease", "pred Disease"))
    for i, row in enumerate(m):
        print("%-14s %8d %12d" % ("actual " + ("No" if i == 0 else "Yes"), row[0], row[1]))


def cross_validate(k, seed=42):
    X, y, names = load_csv()
    idx = list(range(len(X)))
    random.Random(seed).shuffle(idx)
    folds = [idx[i::k] for i in range(k)]
    scores = []
    for f in range(k):
        test_idx = set(folds[f])
        tr = [i for i in idx if i not in test_idx]
        te = folds[f]
        model = RandomForest(n_estimators=100, max_features="sqrt", random_state=42).fit(
            [X[i] for i in tr], [y[i] for i in tr], feature_names=names)
        acc = accuracy_score([y[i] for i in te], model.predict_many([X[i] for i in te]))
        scores.append(acc)
        print("  fold %d: %.4f  (%d test rows)" % (f + 1, acc, len(te)))
    print("\n%d-fold cross-validation accuracy: %.4f  (std %.4f)"
          % (k, statistics.mean(scores), statistics.stdev(scores) if k > 1 else 0.0))


if __name__ == "__main__":
    evaluate_saved_model()
    if "--cv" in sys.argv:
        k = int(sys.argv[sys.argv.index("--cv") + 1]) if len(sys.argv) > sys.argv.index("--cv") + 1 else 5
        print("\nCross-validation (fresh forests, saved model untouched):")
        cross_validate(k)
