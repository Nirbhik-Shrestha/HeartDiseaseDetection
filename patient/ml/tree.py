"""CART decision tree built from scratch.

The tree splits on the Gini impurity criterion. Every split only looks at a
random subset of the features, which is what turns a plain bagged ensemble of
trees into a *Random* Forest.
"""

import math
import random


def gini(counts, n):
    """Gini impurity of a node, given per-class counts and the node size."""
    if n == 0:
        return 0.0
    impurity = 1.0
    for c in counts:
        if c:
            p = c / n
            impurity -= p * p
    return impurity


class DecisionTree:
    def __init__(self, n_classes, max_depth=None, min_samples_split=2,
                 min_samples_leaf=1, max_features=None, rng=None):
        self.n_classes = n_classes
        self.max_depth = max_depth if max_depth is not None else 2 ** 31
        self.min_samples_split = min_samples_split
        self.min_samples_leaf = min_samples_leaf
        self.max_features = max_features
        self.rng = rng or random.Random(0)
        self.root = None
        # Raw (unnormalised) Gini importance accumulated while growing.
        self.importances = None

    # ------------------------------------------------------------------ fit

    def fit(self, X, y):
        n_features = len(X[0])
        self.importances = [0.0] * n_features
        if self.max_features is None:
            self.k = n_features
        else:
            self.k = max(1, min(n_features, self.max_features))
        self._n_root = len(X)
        self.root = self._build(X, y, list(range(len(X))), depth=0)

        # Normalise: sklearn divides by the root sample count, then rescales
        # the vector so the importances sum to 1.
        total = sum(self.importances)
        if total > 0:
            self.importances = [v / total for v in self.importances]
        return self

    def _leaf(self, y, indices):
        counts = [0] * self.n_classes
        for i in indices:
            counts[y[i]] += 1
        n = len(indices)
        return {"p": [c / n for c in counts]}

    def _build(self, X, y, indices, depth):
        n = len(indices)
        counts = [0] * self.n_classes
        for i in indices:
            counts[y[i]] += 1

        # Stop: pure node, too deep, or too few samples to split.
        if (depth >= self.max_depth
                or n < self.min_samples_split
                or max(counts) == n):
            return self._leaf(y, indices)

        n_features = len(X[0])
        # A fresh random subset of features at *every* node, not once per tree.
        feature_ids = self.rng.sample(range(n_features), self.k)

        split = self._best_split(X, y, indices, counts, n, feature_ids)
        if split is None:
            return self._leaf(y, indices)

        f, threshold, gain = split
        left_idx, right_idx = [], []
        for i in indices:
            (left_idx if X[i][f] <= threshold else right_idx).append(i)

        # Gini importance: how much impurity this split removed, weighted by
        # how many samples passed through the node.
        self.importances[f] += n * gain / self._n_root

        return {
            "f": f,
            "t": threshold,
            "l": self._build(X, y, left_idx, depth + 1),
            "r": self._build(X, y, right_idx, depth + 1),
        }

    def _best_split(self, X, y, indices, parent_counts, n, feature_ids):
        """Exhaustive threshold search over the sampled features.

        For each feature the samples are sorted once and swept left-to-right,
        moving one sample at a time across the boundary, so the class counts on
        both sides update in O(1) per candidate threshold.
        """
        parent_imp = gini(parent_counts, n)
        best_gain = 0.0
        best = None

        for f in feature_ids:
            pairs = sorted((X[i][f], y[i]) for i in indices)
            left = [0] * self.n_classes
            right = list(parent_counts)

            for k in range(n - 1):
                value, label = pairs[k]
                left[label] += 1
                right[label] -= 1

                # Cannot split between two identical values.
                next_value = pairs[k + 1][0]
                if next_value == value:
                    continue

                n_left = k + 1
                n_right = n - n_left
                if n_left < self.min_samples_leaf or n_right < self.min_samples_leaf:
                    continue

                weighted = (n_left * gini(left, n_left)
                            + n_right * gini(right, n_right)) / n
                gain = parent_imp - weighted
                if gain > best_gain:
                    best_gain = gain
                    best = (f, (value + next_value) / 2.0, gain)

        return best

    # -------------------------------------------------------------- predict

    @staticmethod
    def predict_proba_node(node, row):
        """Walk a serialised tree iteratively (no recursion depth limit)."""
        while "p" not in node:
            node = node["l"] if row[node["f"]] <= node["t"] else node["r"]
        return node["p"]

    def predict_proba(self, row):
        return self.predict_proba_node(self.root, row)
