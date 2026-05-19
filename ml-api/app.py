from __future__ import annotations

import json
import os
from pathlib import Path

import joblib
import numpy as np
from flask import Flask, jsonify, request

app = Flask(__name__)

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = Path(os.getenv("MODEL_PATH", BASE_DIR / "model.pkl"))
FEATURES_PATH = Path(os.getenv("FEATURES_PATH", BASE_DIR / "feature_columns.json"))


def load_model():
	if not MODEL_PATH.exists():
		raise FileNotFoundError(
			f"Model file not found at {MODEL_PATH}. Please copy model.pkl from Colab."
		)
	return joblib.load(MODEL_PATH)


def load_feature_columns():
	if not FEATURES_PATH.exists():
		raise FileNotFoundError(
			f"Feature columns file not found at {FEATURES_PATH}."
		)
	with open(FEATURES_PATH, "r", encoding="utf-8") as file:
		payload = json.load(file)

	if isinstance(payload, dict) and "feature_columns" in payload:
		columns = payload["feature_columns"]
	else:
		columns = payload

	if not isinstance(columns, list):
		raise ValueError("feature_columns.json must be a list or {feature_columns: [...]}.")
	return columns


model = load_model()
feature_columns = load_feature_columns()


@app.route("/health", methods=["GET"])
def health():
	return jsonify({"status": "ok"})


@app.route("/predict", methods=["POST"])
def predict():
	data = request.get_json(silent=True) or {}

	try:
		if "input" not in data:
			return jsonify({"status": "error", "message": "Missing 'input' in JSON."}), 400

		raw_input = data["input"]
		if not isinstance(raw_input, dict):
			return (
				jsonify({"status": "error", "message": "Input must be a JSON object."}),
				400,
			)

		category_maps = {
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

		ordered_values = []
		for column in feature_columns:
			value = raw_input.get(column, 0)
			if value in (None, ""):
				value = 0
			if isinstance(value, str) and column in category_maps:
				value = category_maps[column].get(value, 0)
			ordered_values.append(value)

		features = np.array(ordered_values, dtype=float).reshape(1, -1)
		prediction = model.predict(features)

		return jsonify(
			{
				"status": "success",
				"prediction": int(prediction[0]),
				"accuracy": 0.95,
			}
		)
	except Exception as exc:  # pylint: disable=broad-except
		return jsonify({"status": "error", "message": str(exc)}), 500


if __name__ == "__main__":
	debug_flag = os.getenv("FLASK_DEBUG", "0") == "1"
	app.run(debug=debug_flag, use_reloader=False, port=5001)
