from __future__ import annotations

import json
import os
from numbers import Real
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from flask import Flask, jsonify, request

app = Flask(__name__)

BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = Path(os.getenv("MODEL_PATH", BASE_DIR / "model.pkl"))
FEATURES_PATH = Path(os.getenv("FEATURES_PATH", BASE_DIR / "feature_columns.json"))
METRICS_PATH = Path(os.getenv("METRICS_PATH", BASE_DIR / "model_metrics.json"))

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
BINARY_COLUMNS = {"hypertension", "heart_disease"}
MISSING_VALUES = {"", "N/A", "na", "NA", "null", "None"}


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


def load_model_metrics():
	if not METRICS_PATH.exists():
		return {"model_name": "Decision Tree", "accuracy": None}

	with open(METRICS_PATH, "r", encoding="utf-8") as file:
		payload = json.load(file)

	if not isinstance(payload, dict):
		raise ValueError("model_metrics.json must be a JSON object.")

	return payload


def file_mtime(path):
	return path.stat().st_mtime if path.exists() else None


def is_missing(value):
	if value is None:
		return True
	if isinstance(value, str):
		return value.strip() in MISSING_VALUES
	return False


def encode_categorical(column, value):
	mapping = CATEGORY_MAPS[column]
	if isinstance(value, str):
		value = value.strip()
		if value in mapping:
			return float(mapping[value])
	elif isinstance(value, Real):
		numeric_value = float(value)
		if numeric_value in set(mapping.values()):
			return numeric_value

	allowed_values = ", ".join(mapping.keys())
	raise ValueError(f"{column} harus salah satu dari: {allowed_values}")


def encode_numeric(column, value):
	if isinstance(value, str):
		value = value.strip()

	try:
		numeric_value = float(value)
	except (TypeError, ValueError) as exc:
		raise ValueError(f"{column} harus berupa angka") from exc

	if column in BINARY_COLUMNS and numeric_value not in {0.0, 1.0}:
		raise ValueError(f"{column} harus bernilai 0 atau 1")

	return numeric_value


def validate_and_normalize_input(raw_input):
	missing_columns = [
		column for column in feature_columns if column not in raw_input or is_missing(raw_input[column])
	]
	if missing_columns:
		raise ValueError(f"Kolom yang hilang: {', '.join(missing_columns)}")

	normalized = {}
	for column in feature_columns:
		value = raw_input[column]
		if column in CATEGORY_MAPS:
			if isinstance(value, str):
				value = value.strip()
			if value not in CATEGORY_MAPS[column]:
				allowed_values = ", ".join(CATEGORY_MAPS[column].keys())
				raise ValueError(f"{column} harus salah satu dari: {allowed_values}")
			normalized[column] = value
		else:
			normalized[column] = encode_numeric(column, value)

	return normalized


def model_uses_pipeline():
	return hasattr(model, "named_steps")


def prepare_features(raw_input):
	normalized = validate_and_normalize_input(raw_input)

	if model_uses_pipeline():
		return pd.DataFrame([normalized], columns=feature_columns)

	ordered_values = []
	for column in feature_columns:
		value = normalized[column]
		if column in CATEGORY_MAPS:
			ordered_values.append(encode_categorical(column, value))
		else:
			ordered_values.append(value)

	return np.array(ordered_values, dtype=float).reshape(1, -1)


model = load_model()
feature_columns = load_feature_columns()
model_metrics = load_model_metrics()
model_mtime = file_mtime(MODEL_PATH)
features_mtime = file_mtime(FEATURES_PATH)
metrics_mtime = file_mtime(METRICS_PATH)


def refresh_runtime_state():
	global model, feature_columns, model_metrics
	global model_mtime, features_mtime, metrics_mtime

	current_model_mtime = file_mtime(MODEL_PATH)
	if current_model_mtime != model_mtime:
		model = load_model()
		model_mtime = current_model_mtime

	current_features_mtime = file_mtime(FEATURES_PATH)
	if current_features_mtime != features_mtime:
		feature_columns = load_feature_columns()
		features_mtime = current_features_mtime

	current_metrics_mtime = file_mtime(METRICS_PATH)
	if current_metrics_mtime != metrics_mtime:
		model_metrics = load_model_metrics()
		metrics_mtime = current_metrics_mtime


def predict_high_risk_probability(features):
	if not hasattr(model, "predict_proba") or not hasattr(model, "classes_"):
		return None

	classes = list(model.classes_)
	if 1 not in classes:
		return None

	probabilities = model.predict_proba(features)[0]
	return float(probabilities[classes.index(1)])


@app.route("/health", methods=["GET"])
def health():
	return jsonify({"status": "ok"})


@app.route("/metadata", methods=["GET"])
def metadata():
	refresh_runtime_state()

	return jsonify(
		{
			"status": "success",
			"model_name": model_metrics.get("model_name", "Decision Tree"),
			"model_type": type(model).__name__,
			"accuracy": model_metrics.get("accuracy"),
			"best_params": model_metrics.get("best_params"),
			"hpo_method": model_metrics.get("hpo_method"),
		}
	)


@app.route("/predict", methods=["POST"])
def predict():
	try:
		refresh_runtime_state()
		data = request.get_json(silent=True) or {}

		if "input" not in data:
			return jsonify({"status": "error", "message": "Missing 'input' in JSON."}), 400

		raw_input = data["input"]
		if not isinstance(raw_input, dict):
			return (
				jsonify({"status": "error", "message": "Input must be a JSON object."}),
				400,
			)

		features = prepare_features(raw_input)
		prediction = model.predict(features)
		high_risk_probability = predict_high_risk_probability(features)

		response = {
			"status": "success",
			"prediction": int(prediction[0]),
			"high_risk_probability": high_risk_probability,
			"model_name": model_metrics.get("model_name", "Decision Tree"),
			"accuracy": model_metrics.get("accuracy"),
		}
		if data.get("debug"):
			if isinstance(features, pd.DataFrame):
				response["model_input"] = features.iloc[0].to_dict()
			else:
				response["encoded_features"] = dict(zip(feature_columns, features[0].tolist()))

		return jsonify(response)
	except (TypeError, ValueError) as exc:
		return jsonify({"status": "error", "message": str(exc)}), 400
	except Exception as exc:  # pylint: disable=broad-except
		return jsonify({"status": "error", "message": str(exc)}), 500


if __name__ == "__main__":
	debug_flag = os.getenv("FLASK_DEBUG", "0") == "1"
	app.run(debug=debug_flag, use_reloader=False, port=5001)
