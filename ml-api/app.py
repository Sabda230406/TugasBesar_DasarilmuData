from __future__ import annotations

import json
import os
import shutil
from dataclasses import dataclass
from datetime import datetime
from numbers import Real
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from imblearn.over_sampling import SMOTENC
from imblearn.pipeline import Pipeline as ImbPipeline
from flask import Flask, jsonify, request
from sklearn.compose import ColumnTransformer
from sklearn.impute import SimpleImputer
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import GridSearchCV, train_test_split
from sklearn.neighbors import KNeighborsClassifier
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OrdinalEncoder, StandardScaler
from sklearn.svm import SVC
from sklearn.tree import DecisionTreeClassifier

app = Flask(__name__)

BASE_DIR = Path(__file__).resolve().parent

MODEL_DEFINITIONS = {
	"decision_tree": {
		"display_name": "Decision Tree",
		"aliases": {"decision_tree", "decision-tree", "dt", "tree", "Decision Tree"},
		"model_files": ["active_models/decision_tree_model.pkl", "DT_model.pkl", "dt_model.pkl", "model.pkl"],
		"feature_files": ["active_models/decision_tree_feature_columns.json", "DT_feature_columns.json", "dt_feature_columns.json", "feature_columns.json"],
		"metric_files": ["active_models/decision_tree_metrics.json", "DT_model_metrics.json", "dt_model_metrics.json", "model_metrics.json"],
	},
	"knn": {
		"display_name": "KNN",
		"aliases": {"knn", "KNN"},
		"model_files": ["active_models/knn_model.pkl", "knn_model.pkl", "KNN_model.pkl"],
		"feature_files": ["active_models/knn_feature_columns.json", "knn_feature_columns.json", "KNN_feature_columns.json"],
		"metric_files": ["active_models/knn_metrics.json", "knn_model_metrics.json", "KNN_model_metrics.json"],
	},
	"svm": {
		"display_name": "SVM",
		"aliases": {"svm", "SVM"},
		"model_files": ["active_models/svm_model.pkl", "svm_model.pkl", "SVM_model.pkl"],
		"feature_files": ["active_models/svm_feature_columns.json", "svm_feature_columns.json", "SVM_feature_columns.json"],
		"metric_files": ["active_models/svm_metrics.json", "svm_model_metrics.json", "SVM_model_metrics.json"],
	},
}

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
TARGET_COLUMN = "stroke"
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
RETRAIN_COLUMNS = FEATURE_COLUMNS + [TARGET_COLUMN]
NUMERIC_COLUMNS = ["age", "hypertension", "heart_disease", "avg_glucose_level", "bmi"]
CATEGORICAL_COLUMNS = ["gender", "ever_married", "work_type", "Residence_type", "smoking_status"]
BASE_DATA_PATH = BASE_DIR.parent / "data" / "healthcare-dataset-stroke-data.csv"


@dataclass
class ModelBundle:
	key: str
	display_name: str
	model_path: Path
	feature_path: Path
	metric_path: Path | None
	model: object
	feature_columns: list[str]
	metrics: dict
	model_mtime: float | None
	feature_mtime: float | None
	metric_mtime: float | None


runtime_models: dict[str, ModelBundle] = {}


def normalize_model_key(value):
	if value is None or str(value).strip() == "":
		return None

	needle = str(value).strip()
	needle_lower = needle.lower().replace(" ", "_")
	for key, definition in MODEL_DEFINITIONS.items():
		aliases = {alias.lower().replace(" ", "_") for alias in definition["aliases"]}
		if needle_lower == key or needle_lower in aliases:
			return key

	raise ValueError(f"Model '{value}' tidak dikenal.")


def path_from_env(env_name, fallback):
	return Path(os.getenv(env_name, fallback))


def artifact_candidates(key):
	definition = MODEL_DEFINITIONS[key]

	for model_file, feature_file, metric_file in zip(
		definition["model_files"],
		definition["feature_files"],
		definition["metric_files"],
	):
		yield {
			"model": BASE_DIR / model_file,
			"features": BASE_DIR / feature_file,
			"metrics": BASE_DIR / metric_file,
		}

	if key == "decision_tree":
		yield {
			"model": path_from_env("MODEL_PATH", BASE_DIR / "model.pkl"),
			"features": path_from_env("FEATURES_PATH", BASE_DIR / "feature_columns.json"),
			"metrics": path_from_env("METRICS_PATH", BASE_DIR / "model_metrics.json"),
		}


def resolve_artifacts(key):
	for candidate in artifact_candidates(key):
		if candidate["model"].exists() and candidate["features"].exists():
			return candidate
	return None


def available_model_keys():
	return [key for key in MODEL_DEFINITIONS if resolve_artifacts(key) is not None]


def default_model_key():
	try:
		env_key = normalize_model_key(os.getenv("ACTIVE_MODEL", "decision_tree"))
	except ValueError:
		env_key = "decision_tree"

	if env_key and resolve_artifacts(env_key):
		return env_key

	keys = available_model_keys()
	if keys:
		return keys[0]

	return env_key or "decision_tree"


def file_mtime(path):
	return path.stat().st_mtime if path and path.exists() else None


def load_feature_columns(path):
	with open(path, "r", encoding="utf-8") as file:
		payload = json.load(file)

	if isinstance(payload, dict) and "feature_columns" in payload:
		columns = payload["feature_columns"]
	else:
		columns = payload

	if not isinstance(columns, list):
		raise ValueError(f"{path.name} must be a list or {{feature_columns: [...]}}.")
	return columns


def load_model_metrics(path, display_name):
	if path is None or not path.exists():
		return {"model_name": display_name, "accuracy": None}

	with open(path, "r", encoding="utf-8") as file:
		payload = json.load(file)

	if not isinstance(payload, dict):
		raise ValueError(f"{path.name} must be a JSON object.")

	payload["model_name"] = payload.get("model_name") or display_name
	return payload


def load_bundle(key):
	artifacts = resolve_artifacts(key)
	display_name = MODEL_DEFINITIONS[key]["display_name"]
	if artifacts is None:
		raise FileNotFoundError(
			f"Artefak model {display_name} belum tersedia di folder ml-api."
		)

	model = joblib.load(artifacts["model"])
	feature_columns = load_feature_columns(artifacts["features"])
	metric_path = artifacts["metrics"] if artifacts["metrics"].exists() else None
	metrics = load_model_metrics(metric_path, display_name)
	metrics["model_key"] = key

	return ModelBundle(
		key=key,
		display_name=metrics.get("model_name", display_name),
		model_path=artifacts["model"],
		feature_path=artifacts["features"],
		metric_path=metric_path,
		model=model,
		feature_columns=feature_columns,
		metrics=metrics,
		model_mtime=file_mtime(artifacts["model"]),
		feature_mtime=file_mtime(artifacts["features"]),
		metric_mtime=file_mtime(metric_path),
	)


def get_model_bundle(key=None):
	model_key = normalize_model_key(key) if key else default_model_key()
	if model_key not in MODEL_DEFINITIONS:
		raise ValueError(f"Model '{model_key}' tidak dikenal.")

	bundle = runtime_models.get(model_key)
	if bundle is None:
		bundle = load_bundle(model_key)
		runtime_models[model_key] = bundle
		return bundle

	artifacts = resolve_artifacts(model_key)
	if artifacts is None:
		runtime_models.pop(model_key, None)
		raise FileNotFoundError(
			f"Artefak model {MODEL_DEFINITIONS[model_key]['display_name']} belum tersedia di folder ml-api."
		)

	metric_path = artifacts["metrics"] if artifacts["metrics"].exists() else None
	if (
		file_mtime(artifacts["model"]) != bundle.model_mtime
		or file_mtime(artifacts["features"]) != bundle.feature_mtime
		or file_mtime(metric_path) != bundle.metric_mtime
	):
		bundle = load_bundle(model_key)
		runtime_models[model_key] = bundle

	return bundle


def public_model_metadata(key):
	definition = MODEL_DEFINITIONS[key]
	artifacts = resolve_artifacts(key)
	is_available = artifacts is not None
	metrics = {"model_name": definition["display_name"], "accuracy": None}

	if is_available:
		metric_path = artifacts["metrics"] if artifacts["metrics"].exists() else None
		metrics = load_model_metrics(metric_path, definition["display_name"])

	return {
		"key": key,
		"model_name": metrics.get("model_name", definition["display_name"]),
		"available": is_available,
		"accuracy": metrics.get("accuracy"),
		"best_params": metrics.get("best_params"),
		"hpo_method": metrics.get("hpo_method"),
		"classification_report": metrics.get("classification_report"),
		"confusion_matrix": metrics.get("confusion_matrix"),
		"scoring": metrics.get("scoring"),
	}


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


def validate_and_normalize_input(raw_input, feature_columns):
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


def model_uses_pipeline(selected_model):
	return hasattr(selected_model, "named_steps")


def prepare_features(raw_input, bundle):
	normalized = validate_and_normalize_input(raw_input, bundle.feature_columns)

	if model_uses_pipeline(bundle.model):
		return pd.DataFrame([normalized], columns=bundle.feature_columns)

	ordered_values = []
	for column in bundle.feature_columns:
		value = normalized[column]
		if column in CATEGORY_MAPS:
			ordered_values.append(encode_categorical(column, value))
		else:
			ordered_values.append(value)

	return np.array(ordered_values, dtype=float).reshape(1, -1)


def model_classes(selected_model):
	if hasattr(selected_model, "classes_"):
		return list(selected_model.classes_)

	if hasattr(selected_model, "named_steps") and "model" in selected_model.named_steps:
		final_model = selected_model.named_steps["model"]
		if hasattr(final_model, "classes_"):
			return list(final_model.classes_)

	return []


def predict_high_risk_probability(selected_model, features):
	if not hasattr(selected_model, "predict_proba"):
		return None

	classes = model_classes(selected_model)
	if 1 not in classes:
		return None

	probabilities = selected_model.predict_proba(features)[0]
	return float(probabilities[classes.index(1)])


def create_preprocessor():
	numeric_transformer = Pipeline(
		steps=[
			("imputer", SimpleImputer(strategy="mean")),
			("scaler", StandardScaler()),
		]
	)
	categorical_transformer = Pipeline(
		steps=[
			("imputer", SimpleImputer(strategy="most_frequent")),
			(
				"encoder",
				OrdinalEncoder(handle_unknown="use_encoded_value", unknown_value=-1),
			),
		]
	)

	return ColumnTransformer(
		transformers=[
			("num", numeric_transformer, NUMERIC_COLUMNS),
			("cat", categorical_transformer, CATEGORICAL_COLUMNS),
		]
	)


def normalize_training_dataframe(df):
	missing = [column for column in RETRAIN_COLUMNS if column not in df.columns]
	if missing:
		raise ValueError(f"Kolom dataset retraining belum lengkap: {', '.join(missing)}")

	df = df[RETRAIN_COLUMNS].copy()
	df.replace({"N/A": np.nan, "NA": np.nan, "": np.nan, "null": np.nan}, inplace=True)

	for column in NUMERIC_COLUMNS:
		df[column] = pd.to_numeric(df[column], errors="coerce")

	df[TARGET_COLUMN] = pd.to_numeric(df[TARGET_COLUMN], errors="coerce")
	df = df.dropna(subset=[TARGET_COLUMN])
	df[TARGET_COLUMN] = df[TARGET_COLUMN].astype(int)

	invalid_target = sorted(set(df[TARGET_COLUMN].dropna()) - {0, 1})
	if invalid_target:
		raise ValueError("Kolom stroke hanya boleh bernilai 0 atau 1.")

	for column, allowed_values in CATEGORY_MAPS.items():
		invalid_values = sorted(set(df[column].dropna()) - set(allowed_values.keys()))
		if invalid_values:
			raise ValueError(
				f"Kolom {column} memiliki kategori tidak valid: {', '.join(map(str, invalid_values[:5]))}"
			)

	if df[TARGET_COLUMN].nunique() < 2:
		raise ValueError("Dataset retraining harus memiliki stroke=0 dan stroke=1.")

	return df


def load_retraining_dataframe(dataset_path):
	path = Path(dataset_path)
	if not path.exists():
		raise FileNotFoundError(f"Dataset retraining tidak ditemukan: {path}")

	uploaded_df = normalize_training_dataframe(pd.read_csv(path))
	frames = []

	if BASE_DATA_PATH.exists():
		frames.append(normalize_training_dataframe(pd.read_csv(BASE_DATA_PATH)))

	frames.append(uploaded_df)
	combined_df = pd.concat(frames, ignore_index=True)
	combined_df = combined_df.drop_duplicates(subset=RETRAIN_COLUMNS).reset_index(drop=True)

	return uploaded_df, combined_df


def selected_retrain_models(model_keys):
	if not isinstance(model_keys, list) or not model_keys:
		raise ValueError("Pilih minimal satu model untuk retraining.")

	selected = []
	for model_key in model_keys:
		normalized = normalize_model_key(model_key)
		if normalized not in {"decision_tree", "knn", "svm"}:
			raise ValueError(f"Model {model_key} belum tersedia untuk retraining.")
		if normalized not in selected:
			selected.append(normalized)

	return selected


def model_training_config(model_key):
	if model_key == "decision_tree":
		return {
			"name": "Decision Tree",
			"estimator": DecisionTreeClassifier(random_state=42),
			"param_grid": {
				"model__criterion": ["gini", "entropy"],
				"model__max_depth": [5, 10, None],
				"model__min_samples_split": [2, 5],
				"model__min_samples_leaf": [1, 4],
				"model__class_weight": [None, "balanced"],
			},
		}

	if model_key == "knn":
		return {
			"name": "KNN",
			"estimator": KNeighborsClassifier(),
			"param_grid": {
				"model__n_neighbors": [5, 11, 15],
				"model__weights": ["uniform", "distance"],
				"model__metric": ["euclidean", "manhattan"],
			},
		}

	if model_key == "svm":
		return {
			"name": "SVM",
			"estimator": SVC(probability=True, random_state=42),
			"param_grid": [
				{
					"model__C": [0.5, 1, 2],
					"model__kernel": ["linear"],
					"model__class_weight": [None, "balanced"],
				},
				{
					"model__C": [0.5, 1, 2],
					"model__kernel": ["rbf"],
					"model__gamma": ["scale", "auto"],
					"model__class_weight": [None, "balanced"],
				},
			],
		}

	raise ValueError(f"Trainer {model_key} belum tersedia.")


def train_one_model(model_key, x_train, x_test, y_train, y_test, metadata):
	config = model_training_config(model_key)
	minority_count = int(y_train.value_counts().min())
	if minority_count < 2:
		raise ValueError("Data training minoritas terlalu sedikit untuk SMOTE.")

	smote_k_neighbors = max(1, min(5, minority_count - 1))
	pipeline = ImbPipeline(
		steps=[
			("preprocess", create_preprocessor()),
			(
				"smote",
				SMOTENC(
					categorical_features=[5, 6, 7, 8, 9],
					random_state=100,
					k_neighbors=smote_k_neighbors,
				),
			),
			("model", config["estimator"]),
		]
	)

	grid = GridSearchCV(
		pipeline,
		config["param_grid"],
		cv=3,
		scoring="f1",
		n_jobs=1,
		verbose=0,
	)
	grid.fit(x_train, y_train)

	best_model = grid.best_estimator_
	y_pred = best_model.predict(x_test)
	report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
	cm = confusion_matrix(y_test, y_pred, labels=[0, 1]).tolist()

	metrics = {
		"model_name": config["name"],
		"model_key": model_key,
		"feature_columns": FEATURE_COLUMNS,
		"accuracy": accuracy_score(y_test, y_pred),
		"classification_report": report,
		"confusion_matrix": cm,
		"best_params": grid.best_params_,
		"hpo_method": "GridSearchCV + Pipeline + SMOTENC",
		"scoring": "f1",
		"random_state": 42,
		**metadata,
	}

	return best_model, metrics


def retrain_artifact_paths(model_key):
	if model_key == "decision_tree":
		return {
			"root_model": BASE_DIR / "DT_model.pkl",
			"root_features": BASE_DIR / "DT_feature_columns.json",
			"root_metrics": BASE_DIR / "DT_model_metrics.json",
			"active_model": BASE_DIR / "active_models" / "decision_tree_model.pkl",
			"active_features": BASE_DIR / "active_models" / "decision_tree_feature_columns.json",
			"active_metrics": BASE_DIR / "active_models" / "decision_tree_metrics.json",
		}

	if model_key == "knn":
		return {
			"root_model": BASE_DIR / "knn_model.pkl",
			"root_features": BASE_DIR / "knn_feature_columns.json",
			"root_metrics": BASE_DIR / "knn_model_metrics.json",
			"active_model": BASE_DIR / "active_models" / "knn_model.pkl",
			"active_features": BASE_DIR / "active_models" / "knn_feature_columns.json",
			"active_metrics": BASE_DIR / "active_models" / "knn_metrics.json",
		}

	if model_key == "svm":
		return {
			"root_model": BASE_DIR / "svm_model.pkl",
			"root_features": BASE_DIR / "svm_feature_columns.json",
			"root_metrics": BASE_DIR / "svm_model_metrics.json",
			"active_model": BASE_DIR / "active_models" / "svm_model.pkl",
			"active_features": BASE_DIR / "active_models" / "svm_feature_columns.json",
			"active_metrics": BASE_DIR / "active_models" / "svm_metrics.json",
		}

	raise ValueError(f"Artefak {model_key} belum tersedia.")


def backup_existing_models(model_keys, timestamp):
	backup_dir = BASE_DIR / "backup_models" / timestamp
	backup_dir.mkdir(parents=True, exist_ok=True)

	for model_key in model_keys:
		paths = retrain_artifact_paths(model_key)
		for source in paths.values():
			if source.exists() and source.is_file():
				shutil.copy2(source, backup_dir / f"{model_key}_{source.name}")

	return backup_dir


def save_retrained_model(model_key, trained_model, metrics):
	paths = retrain_artifact_paths(model_key)
	paths["active_model"].parent.mkdir(parents=True, exist_ok=True)

	feature_payload = {"feature_columns": FEATURE_COLUMNS}
	metrics = to_jsonable(metrics)

	joblib.dump(trained_model, paths["active_model"])
	with open(paths["active_features"], "w", encoding="utf-8") as file:
		json.dump(feature_payload, file, indent=4)
	with open(paths["active_metrics"], "w", encoding="utf-8") as file:
		json.dump(metrics, file, indent=4)

	shutil.copy2(paths["active_model"], paths["root_model"])
	shutil.copy2(paths["active_features"], paths["root_features"])
	shutil.copy2(paths["active_metrics"], paths["root_metrics"])
	runtime_models.pop(model_key, None)


def nested_metric(metrics, *keys):
	value = metrics
	for key in keys:
		if not isinstance(value, dict) or key not in value:
			return None
		value = value[key]
	return value


def metric_number(value):
	try:
		if value is None:
			return None
		return float(value)
	except (TypeError, ValueError):
		return None


def false_negative(metrics):
	matrix = metrics.get("confusion_matrix") if isinstance(metrics, dict) else None
	if not isinstance(matrix, list) or len(matrix) < 2:
		return None
	try:
		return int(matrix[1][0])
	except (TypeError, ValueError, IndexError):
		return None


def active_model_metrics(model_key):
	artifacts = resolve_artifacts(model_key)
	if artifacts is None:
		return {}

	metric_path = artifacts["metrics"] if artifacts["metrics"].exists() else None
	if metric_path is None:
		return {}

	return load_model_metrics(metric_path, MODEL_DEFINITIONS[model_key]["display_name"])


def evaluate_model_eligibility(model_key, new_metrics):
	previous_metrics = active_model_metrics(model_key)
	if not previous_metrics:
		return {
			"accepted": True,
			"reasons": ["Model lama belum memiliki metrik pembanding."],
			"previous_metrics": {},
		}

	previous_recall = metric_number(nested_metric(previous_metrics, "classification_report", "1", "recall"))
	new_recall = metric_number(nested_metric(new_metrics, "classification_report", "1", "recall"))
	previous_f1 = metric_number(nested_metric(previous_metrics, "classification_report", "1", "f1-score"))
	new_f1 = metric_number(nested_metric(new_metrics, "classification_report", "1", "f1-score"))
	previous_accuracy = metric_number(previous_metrics.get("accuracy"))
	new_accuracy = metric_number(new_metrics.get("accuracy"))
	previous_fn = false_negative(previous_metrics)
	new_fn = false_negative(new_metrics)

	reasons = []
	if previous_recall is not None and new_recall is not None and new_recall < previous_recall - 0.05:
		reasons.append("Recall stroke turun lebih dari 5%.")

	if previous_f1 is not None and new_f1 is not None and new_f1 < previous_f1 - 0.10:
		reasons.append("F1-score stroke turun terlalu besar.")

	if previous_accuracy is not None and new_accuracy is not None and new_accuracy < previous_accuracy - 0.10:
		reasons.append("Accuracy turun terlalu jauh.")

	if previous_fn is not None and new_fn is not None:
		allowed_fn = max(previous_fn + 2, int(round(previous_fn * 1.25)))
		if new_fn > allowed_fn:
			reasons.append("False negative meningkat signifikan.")

	return {
		"accepted": len(reasons) == 0,
		"reasons": reasons or ["Metrik model baru masih dalam batas layak."],
		"previous_metrics": previous_metrics,
	}


def to_jsonable(value):
	if isinstance(value, dict):
		return {key: to_jsonable(item) for key, item in value.items()}
	if isinstance(value, list):
		return [to_jsonable(item) for item in value]
	if isinstance(value, tuple):
		return [to_jsonable(item) for item in value]
	if isinstance(value, np.integer):
		return int(value)
	if isinstance(value, np.floating):
		return float(value)
	if isinstance(value, np.ndarray):
		return value.tolist()
	return value


@app.route("/health", methods=["GET"])
def health():
	keys = available_model_keys()
	return jsonify(
		{
			"status": "ok",
			"available_models": keys,
			"default_model": default_model_key() if keys else None,
		}
	)


@app.route("/models", methods=["GET"])
def models():
	return jsonify(
		{
			"status": "success",
			"default_model": default_model_key(),
			"models": [public_model_metadata(key) for key in MODEL_DEFINITIONS],
		}
	)


@app.route("/retrain", methods=["POST"])
def retrain():
	try:
		data = request.get_json(silent=True) or {}
		dataset_path = data.get("dataset_path")
		model_keys = selected_retrain_models(data.get("models"))
		uploaded_by = data.get("uploaded_by") or "system"

		if not dataset_path:
			return jsonify({"status": "error", "message": "Missing dataset_path."}), 400

		uploaded_df, combined_df = load_retraining_dataframe(dataset_path)
		x = combined_df[FEATURE_COLUMNS]
		y = combined_df[TARGET_COLUMN]

		x_train, x_test, y_train, y_test = train_test_split(
			x,
			y,
			test_size=0.25,
			random_state=42,
			stratify=y,
		)

		timestamp = datetime.now().strftime("%Y%m%d-%H%M%S")
		label_counts = y.value_counts().to_dict()
		metadata = {
			"retrained_at": timestamp,
			"trained_by": uploaded_by,
			"uploaded_rows": int(len(uploaded_df)),
			"combined_rows": int(len(combined_df)),
			"training_rows": int(len(x_train)),
			"test_rows": int(len(x_test)),
			"stroke_0_count": int(label_counts.get(0, 0)),
			"stroke_1_count": int(label_counts.get(1, 0)),
		}

		trained_results = {}
		for model_key in model_keys:
			trained_model, metrics = train_one_model(
				model_key,
				x_train,
				x_test,
				y_train,
				y_test,
				metadata,
			)
			trained_results[model_key] = {
				"model": trained_model,
				"metrics": metrics,
			}

		eligibility_results = {
			model_key: evaluate_model_eligibility(model_key, result["metrics"])
			for model_key, result in trained_results.items()
		}
		activated = all(result["accepted"] for result in eligibility_results.values())
		backup_dir = None
		if activated:
			backup_dir = backup_existing_models(model_keys, timestamp)

		response_models = {}
		for model_key, result in trained_results.items():
			if activated:
				save_retrained_model(model_key, result["model"], result["metrics"])

			metrics = to_jsonable(result["metrics"])
			eligibility = to_jsonable(eligibility_results[model_key])
			response_models[model_key] = {
				"model_name": metrics["model_name"],
				"metrics": metrics,
				"eligibility": {
					"accepted": eligibility["accepted"],
					"reasons": eligibility["reasons"],
				},
				"previous_metrics": eligibility["previous_metrics"],
			}

		return jsonify(
			{
				"status": "success",
				"activated": activated,
				"message": "Retraining selesai dan model baru diaktifkan."
				if activated
				else "Retraining selesai, tetapi model baru belum diaktifkan karena metrik belum layak.",
				"backup_dir": str(backup_dir) if backup_dir else None,
				"models": response_models,
			}
		)
	except (FileNotFoundError, TypeError, ValueError) as exc:
		return jsonify({"status": "error", "message": str(exc)}), 400
	except Exception as exc:  # pylint: disable=broad-except
		return jsonify({"status": "error", "message": str(exc)}), 500


@app.route("/metadata", methods=["GET"])
def metadata():
	try:
		model_key = request.args.get("model") or default_model_key()
		bundle = get_model_bundle(model_key)

		return jsonify(
			{
				"status": "success",
				"model_key": bundle.key,
				"model_name": bundle.metrics.get("model_name", bundle.display_name),
				"model_type": type(bundle.model).__name__,
				"accuracy": bundle.metrics.get("accuracy"),
				"best_params": bundle.metrics.get("best_params"),
				"hpo_method": bundle.metrics.get("hpo_method"),
				"classification_report": bundle.metrics.get("classification_report"),
				"confusion_matrix": bundle.metrics.get("confusion_matrix"),
				"scoring": bundle.metrics.get("scoring"),
				"available_models": available_model_keys(),
			}
		)
	except (FileNotFoundError, TypeError, ValueError) as exc:
		return jsonify({"status": "error", "message": str(exc)}), 400


@app.route("/predict", methods=["POST"])
def predict():
	try:
		data = request.get_json(silent=True) or {}
		model_key = data.get("model") or request.args.get("model") or default_model_key()
		bundle = get_model_bundle(model_key)

		if "input" not in data:
			return jsonify({"status": "error", "message": "Missing 'input' in JSON."}), 400

		raw_input = data["input"]
		if not isinstance(raw_input, dict):
			return (
				jsonify({"status": "error", "message": "Input must be a JSON object."}),
				400,
			)

		features = prepare_features(raw_input, bundle)
		prediction = bundle.model.predict(features)
		high_risk_probability = predict_high_risk_probability(bundle.model, features)

		response = {
			"status": "success",
			"prediction": int(prediction[0]),
			"high_risk_probability": high_risk_probability,
			"model_key": bundle.key,
			"model_name": bundle.metrics.get("model_name", bundle.display_name),
			"accuracy": bundle.metrics.get("accuracy"),
		}
		if data.get("debug"):
			if isinstance(features, pd.DataFrame):
				response["model_input"] = features.iloc[0].to_dict()
			else:
				response["encoded_features"] = dict(zip(bundle.feature_columns, features[0].tolist()))

		return jsonify(response)
	except (FileNotFoundError, TypeError, ValueError) as exc:
		return jsonify({"status": "error", "message": str(exc)}), 400
	except Exception as exc:  # pylint: disable=broad-except
		return jsonify({"status": "error", "message": str(exc)}), 500


if __name__ == "__main__":
	debug_flag = os.getenv("FLASK_DEBUG", "0") == "1"
	app.run(debug=debug_flag, use_reloader=False, port=5001)
