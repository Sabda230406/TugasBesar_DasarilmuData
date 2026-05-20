from __future__ import annotations

import csv
from pathlib import Path

import joblib
import numpy as np
from sklearn.tree import DecisionTreeClassifier


BASE_DIR = Path(__file__).resolve().parent
DATA_PATH = BASE_DIR.parent / "data" / "healthcare-dataset-stroke-data.csv"
MODEL_PATH = BASE_DIR / "model.pkl"

FEATURE_COLUMNS = [
	"gender",
	"age",
	"hypertension",
	"heart_disease",
	"ever_married",
	"work_type",
	"Residence_type",
	"avg_glucose_level",
	"bmi",
	"smoking_status",
]

CATEGORY_MAPS = {
	"gender": {"Male": 1, "Female": 0, "Other": 2},
	"ever_married": {"Yes": 1, "No": 0},
	"Residence_type": {"Urban": 1, "Rural": 0},
	"work_type": {
		"Private": 0,
		"Self-employed": 1,
		"Govt_job": 2,
		"children": 3,
		"Never_worked": 4,
	},
	"smoking_status": {
		"formerly smoked": 0,
		"never smoked": 1,
		"smokes": 2,
		"Unknown": 3,
	},
}


def encode_value(column: str, value: str) -> float:
	if value in (None, "", "N/A", "na", "NA", "null", "None"):
		return 0.0

	if column in CATEGORY_MAPS:
		return float(CATEGORY_MAPS[column].get(value, 0))

	return float(value)


def load_dataset() -> tuple[np.ndarray, np.ndarray]:
	if not DATA_PATH.exists():
		raise FileNotFoundError(f"Dataset not found at {DATA_PATH}")

	features: list[list[float]] = []
	targets: list[int] = []

	with open(DATA_PATH, "r", encoding="utf-8", newline="") as file:
		reader = csv.DictReader(file)
		for row in reader:
			feature_row = []
			for column in FEATURE_COLUMNS:
				value = row.get(column, "")
				feature_row.append(encode_value(column, value))

			target_value = row.get("stroke", "0")
			targets.append(int(float(target_value)))
			features.append(feature_row)

	return np.asarray(features, dtype=float), np.asarray(targets, dtype=int)


def main() -> None:
	x, y = load_dataset()

	model = DecisionTreeClassifier(
		criterion="entropy",
		random_state=42,
	)
	model.fit(x, y)

	joblib.dump(model, MODEL_PATH)
	print(f"Saved Decision Tree model to {MODEL_PATH}")
	print(f"Samples: {len(x)} | Features: {x.shape[1]}")
	print(f"Training accuracy: {model.score(x, y):.4f}")


if __name__ == "__main__":
	main()
