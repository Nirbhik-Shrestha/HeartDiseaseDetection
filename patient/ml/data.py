"""CSV loading and train/test splitting, using only the standard library.

Both the from-scratch trainer and the sklearn benchmark import these helpers,
so the two models are always compared on exactly the same rows.
"""

import csv
import os
import random

FEATURES = ["age", "sex", "cp", "trestbps", "chol", "fbs", "restecg",
            "thalach", "exang", "oldpeak", "slope", "ca", "thal"]
TARGET = "target"

DATASET_NAME = "heart_cleveland_upload.csv"


def find_dataset(filename=DATASET_NAME):
    """Look for the CSV next to this package, then in the project root."""
    here = os.path.dirname(os.path.abspath(__file__))
    candidates = [
        os.path.join(here, filename),
        os.path.join(here, os.pardir, filename),
        os.path.join(here, os.pardir, os.pardir, filename),
        filename,
    ]
    for path in candidates:
        if os.path.isfile(path):
            return os.path.abspath(path)
    raise FileNotFoundError("Could not locate %s" % filename)


def load_csv(path=None):
    """Return (X, y, feature_names). Targets above 1 collapse to 1."""
    path = path or find_dataset()
    X, y = [], []
    with open(path, "r", encoding="utf-8-sig", newline="") as fh:
        for row in csv.DictReader(fh):
            X.append([float(row[name]) for name in FEATURES])
            y.append(1 if float(row[TARGET]) > 0 else 0)
    return X, y, list(FEATURES)


def train_test_split(X, y, test_size=0.2, random_state=42):
    """Shuffle-and-slice split with a seeded RNG, so runs are reproducible."""
    indices = list(range(len(X)))
    random.Random(random_state).shuffle(indices)

    n_test = int(round(len(X) * test_size))
    test_idx = indices[:n_test]
    train_idx = indices[n_test:]

    return (
        [X[i] for i in train_idx], [X[i] for i in test_idx],
        [y[i] for i in train_idx], [y[i] for i in test_idx],
    )
