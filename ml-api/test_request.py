from __future__ import annotations

import json
import urllib.request

API_URL = "http://127.0.0.1:5001/predict"

payload = {
	"input": {
		"gender": "Male",
		"age": 67,
		"hypertension": 0,
		"heart_disease": 1,
		"ever_married": "Yes",
		"work_type": "Private",
		"Residence_type": "Urban",
		"avg_glucose_level": 228.69,
		"bmi": 36.6,
		"smoking_status": "formerly smoked",
	}
}

request = urllib.request.Request(
	API_URL,
	data=json.dumps(payload).encode("utf-8"),
	headers={"Content-Type": "application/json"},
	method="POST",
)

try:
	with urllib.request.urlopen(request) as response:
		body = response.read().decode("utf-8")
		print(body)
except urllib.error.HTTPError as exc:
	body = exc.read().decode("utf-8")
	print(body)
