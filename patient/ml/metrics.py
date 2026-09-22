"""Evaluation metrics written from scratch (replaces sklearn.metrics)."""


def accuracy_score(y_true, y_pred):
    if not y_true:
        return 0.0
    hits = sum(1 for a, b in zip(y_true, y_pred) if a == b)
    return hits / len(y_true)


def confusion_matrix(y_true, y_pred, n_classes=2):
    matrix = [[0] * n_classes for _ in range(n_classes)]
    for actual, predicted in zip(y_true, y_pred):
        matrix[actual][predicted] += 1
    return matrix


def precision_recall_f1(y_true, y_pred, label):
    tp = sum(1 for a, b in zip(y_true, y_pred) if a == label and b == label)
    fp = sum(1 for a, b in zip(y_true, y_pred) if a != label and b == label)
    fn = sum(1 for a, b in zip(y_true, y_pred) if a == label and b != label)

    precision = tp / (tp + fp) if (tp + fp) else 0.0
    recall = tp / (tp + fn) if (tp + fn) else 0.0
    f1 = (2 * precision * recall / (precision + recall)
          if (precision + recall) else 0.0)
    return precision, recall, f1, tp + fn


def classification_report(y_true, y_pred, n_classes=2, labels=None):
    """Formatted per-class report, laid out like sklearn's for comparability."""
    labels = labels or [str(c) for c in range(n_classes)]
    lines = ["%12s %9s %9s %9s %9s" % ("", "precision", "recall", "f1-score", "support"), ""]

    macro = [0.0, 0.0, 0.0]
    weighted = [0.0, 0.0, 0.0]
    total = 0

    for c in range(n_classes):
        precision, recall, f1, support = precision_recall_f1(y_true, y_pred, c)
        lines.append("%12s %9.2f %9.2f %9.2f %9d"
                     % (labels[c], precision, recall, f1, support))
        for i, v in enumerate((precision, recall, f1)):
            macro[i] += v
            weighted[i] += v * support
        total += support

    lines.append("")
    lines.append("%12s %9s %9s %9.2f %9d"
                 % ("accuracy", "", "", accuracy_score(y_true, y_pred), total))
    lines.append("%12s %9.2f %9.2f %9.2f %9d"
                 % ("macro avg", macro[0] / n_classes, macro[1] / n_classes,
                    macro[2] / n_classes, total))
    if total:
        lines.append("%12s %9.2f %9.2f %9.2f %9d"
                     % ("weighted avg", weighted[0] / total, weighted[1] / total,
                        weighted[2] / total, total))
    return "\n".join(lines)
