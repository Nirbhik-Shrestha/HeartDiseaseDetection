"""Random Forest built from scratch on top of DecisionTree.

Three ingredients on top of a plain decision tree:
  1. bagging            - every tree sees a bootstrap sample (drawn with
                          replacement) of the training rows
  2. feature subsetting - every split considers only sqrt(n_features) features
                          (handled inside DecisionTree)
  3. aggregation        - the per-tree class probabilities are averaged
"""

import json
import math
import os
import random

from .tree import DecisionTree


class RandomForest:
    def __init__(self, n_estimators=100, max_depth=None, min_samples_split=2,
                 min_samples_leaf=1, max_features="sqrt", random_state=42):
        self.n_estimators = n_estimators
        self.max_depth = max_depth
        self.min_samples_split = min_samples_split
        self.min_samples_leaf = min_samples_leaf
        self.max_features = max_features
        self.random_state = random_state

        self.trees = []
        self.n_classes = 0
        self.feature_names = []
        self.feature_importances_ = []
        self.oob_score_ = None

    def _resolve_max_features(self, n_features):
        if self.max_features == "sqrt":
            return max(1, int(math.sqrt(n_features)))
        if self.max_features == "log2":
            return max(1, int(math.log2(n_features)))
        if self.max_features is None:
            return n_features
        return max(1, min(n_features, int(self.max_features)))

    # ------------------------------------------------------------------ fit

    def fit(self, X, y, feature_names=None):
        n = len(X)
        n_features = len(X[0])
        self.n_classes = max(y) + 1
        self.feature_names = list(feature_names) if feature_names else [
            "f%d" % i for i in range(n_features)
        ]
        k = self._resolve_max_features(n_features)

        self.trees = []
        importances = [0.0] * n_features
        # Accumulated out-of-bag votes, one probability vector per sample.
        oob_votes = [[0.0] * self.n_classes for _ in range(n)]
        oob_counts = [0] * n

        for i in range(self.n_estimators):
            # A separate seeded RNG per tree keeps training reproducible.
            rng = random.Random(self.random_state * 100003 + i)

            # Bootstrap: n draws WITH replacement out of n rows.
            sample_idx = [rng.randrange(n) for _ in range(n)]
            X_boot = [X[j] for j in sample_idx]
            y_boot = [y[j] for j in sample_idx]

            tree = DecisionTree(
                n_classes=self.n_classes,
                max_depth=self.max_depth,
                min_samples_split=self.min_samples_split,
                min_samples_leaf=self.min_samples_leaf,
                max_features=k,
                rng=rng,
            ).fit(X_boot, y_boot)
            self.trees.append(tree.root)

            for f, v in enumerate(tree.importances):
                importances[f] += v

            # The ~37% of rows this tree never saw give a free validation set.
            out_of_bag = set(range(n)) - set(sample_idx)
            for j in out_of_bag:
                proba = tree.predict_proba(X[j])
                oob_counts[j] += 1
                for c in range(self.n_classes):
                    oob_votes[j][c] += proba[c]

        self.feature_importances_ = [v / self.n_estimators for v in importances]

        scored = correct = 0
        for j in range(n):
            if oob_counts[j] == 0:
                continue
            scored += 1
            predicted = max(range(self.n_classes), key=lambda c: oob_votes[j][c])
            if predicted == y[j]:
                correct += 1
        self.oob_score_ = correct / scored if scored else None

        return self

    # -------------------------------------------------------------- predict

    def predict_proba(self, row):
        totals = [0.0] * self.n_classes
        for root in self.trees:
            proba = DecisionTree.predict_proba_node(root, row)
            for c in range(self.n_classes):
                totals[c] += proba[c]
        return [t / len(self.trees) for t in totals]

    def predict(self, row):
        proba = self.predict_proba(row)
        return max(range(self.n_classes), key=lambda c: proba[c])

    def predict_many(self, rows):
        return [self.predict(r) for r in rows]

    # ------------------------------------------------------------ save/load

    def to_dict(self):
        return {
            "format": "random-forest/1",
            "n_estimators": self.n_estimators,
            "n_classes": self.n_classes,
            "max_features": self.max_features,
            "random_state": self.random_state,
            "feature_names": self.feature_names,
            "feature_importances": self.feature_importances_,
            "oob_score": self.oob_score_,
            "trees": self.trees,
        }

    def save(self, path):
        with open(path, "w", encoding="utf-8") as fh:
            json.dump(self.to_dict(), fh, separators=(",", ":"))
        return os.path.getsize(path)

    @classmethod
    def from_dict(cls, payload):
        model = cls(
            n_estimators=payload["n_estimators"],
            max_features=payload.get("max_features", "sqrt"),
            random_state=payload.get("random_state", 42),
        )
        model.n_classes = payload["n_classes"]
        model.feature_names = payload["feature_names"]
        model.feature_importances_ = payload.get("feature_importances", [])
        model.oob_score_ = payload.get("oob_score")
        model.trees = payload["trees"]
        return model

    @classmethod
    def load(cls, path):
        with open(path, "r", encoding="utf-8") as fh:
            return cls.from_dict(json.load(fh))
