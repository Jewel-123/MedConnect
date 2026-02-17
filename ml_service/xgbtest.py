import numpy as np
import joblib
import re

disease_model = joblib.load("disease_model.pkl")
doctor_model = joblib.load("doctor_model.pkl")

disease_encoder = joblib.load("disease_encoder.pkl")
doctor_encoder = joblib.load("doctor_encoder.pkl")
symptom_columns = joblib.load("symptom_columns.pkl")

symptom_map = {
    "fever": "fever",
    "temperature": "fever",
    "cold": "cold",
    "runny nose": "cold",
    "cough": "cough",
    "breath": "breathing_problem",
    "breathing": "breathing_problem",
    "breathless": "breathing_problem",
    "chest pain": "chest_pain",
    "tired": "tiredness",
    "weak": "tiredness",
    "fatigue": "tiredness",
    "weight loss": "weight_loss",
    "no appetite": "no_appetite",
    "loss of appetite": "no_appetite",
    "stomach pain": "stomach_pain",
    "abdominal pain": "stomach_pain",
    "nausea": "nausea",
    "vomit": "vomiting",
    "vomiting": "vomiting",
    "loose motion": "loose_stool",
    "diarrhea": "loose_stool",
    "constipation": "constipation",
    "gas": "gas",
    "joint pain": "joint_pain",
    "muscle pain": "muscle_pain",
    "back pain": "back_pain",
    "headache": "headache",
    "night sweat": "night_sweat",
    "rash": "skin_rash",
    "itch": "itching",
    "frequent urination": "frequent_urination",
    "thirst": "excess_thirst",
    "burning urine": "burning_urine",
    "less urine": "less_urine",
    "leg swelling": "leg_swelling",
    "face swelling": "face_swelling",
    "yellow eyes": "yellow_eyes",
    "pale": "pale_skin",
    "fits": "fits",
    "seizure": "fits",
    "dizzy": "dizziness",
    "sad": "sadness",
    "sadness": "sadness",
    "anxiety": "fear",
    "fear": "fear",
    "irregular periods": "irregular_periods",
    "infertility": "unable_to_conceive",
    "poor growth": "poor_growth_child"
}

print("\nSymptom-Based Disease Prediction System")
print("Type 'exit' to quit\n")

while True:
    user_text = input("Enter symptoms: ").strip().lower()

    if user_text in ["exit", "quit"]:
        break

    X_new = np.zeros(len(symptom_columns))

    for phrase, mapped_symptom in symptom_map.items():
        if re.search(rf"\b{re.escape(phrase)}\b", user_text):
            if mapped_symptom in symptom_columns:
                idx = symptom_columns.index(mapped_symptom)
                X_new[idx] = 1

    if X_new.sum() == 0:
        print("\nNo recognizable symptoms detected.\n")
        continue

    X_new = X_new.reshape(1, -1)

    disease_probs = disease_model.predict_proba(X_new)[0]
    best_idx = np.argmax(disease_probs)
    best_disease = disease_encoder.inverse_transform([best_idx])[0]
    best_prob = disease_probs[best_idx]

    doctor_pred = doctor_model.predict(X_new)
    doctor_name = doctor_encoder.inverse_transform(doctor_pred)[0]

    print("\n========================================")
    print("PREDICTION RESULT")
    print("========================================\n")

    print("Most Likely Disease:")
    print("----------------------------------------")
    print(f"{best_disease}  : {best_prob * 100:.2f}%")

    print("\nRecommended Doctor:")
    print("----------------------------------------")
    print(doctor_name)

    print("\n----------------------------------------")
    print("This result is based on symptom patterns only.")
    print("It is not a medical diagnosis.")
    print("Please consult a qualified doctor for confirmation.")
    print("========================================\n")
