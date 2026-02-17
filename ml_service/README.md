# **Symptom-Based Disease & Doctor Recommendation System**

## Overview

This project is an advanced symptom-to-diagnosis intelligence system engineered to deliver:
- Real-time prediction of the three most probable disease conditions
- Intelligent specialist routing to the appropriate medical practitioner

The system accepts natural language symptom descriptions from users, performs intelligent feature mapping to structured clinical attributes, and leverages state-of-the-art pre-trained machine learning models to generate clinical predictions. This platform is optimized for educational research, clinical prototyping, and exploratory medical AI applications.

---

## Dataset Architecture

The foundation of this project rests on a comprehensively structured medical dataset carefully curated to reflect prevalent health conditions across diverse demographic populations.

### Key Specifications
- Data Format: Single patient record per row with complete symptom profiling
- Feature Encoding: Binary-valued symptom representation (0/1) for optimal model performance
- Class Distribution: Carefully balanced across all disease categories to prevent bias
- Terminology: Accessible lay-language symptom descriptions without requiring medical expertise

### Data Source
```
indian_symptom_dataset_layman_50plus_with_doctor.csv
```

### Data Schema
- Input Features: Complete symptom vector with binary classification  
- Output Labels:
  - `disease` – clinical condition identifier
  - `doctor_type` – recommended specialist discipline

Sample Data Structure:

| fever | cough | stomach_pain | tiredness | ... | disease | doctor_type |
|------|------|--------------|-----------|-----|---------|-------------|
| 1 | 1 | 0 | 1 | ... | pneumonia | pulmonologist |

---

## System Architecture

```
project-root/
│
├── indian_symptom_dataset_layman_50plus_with_doctor.csv
│   └── [Training & inference dataset]
│
├── disease_model.pkl
│   └── [Pre-trained XGBoost disease classifier]
│
├── doctor_model.pkl
│   └── [Pre-trained XGBoost specialist recommender]
│
├── disease_encoder.pkl
├── doctor_encoder.pkl
├── symptom_columns.pkl
│   └── [Model artifacts: encoders & feature mappings]
│
├── xgbtest.py
│   └── [Inference engine & CLI interface]
│
└── README.md
    └── [Project documentation]
```

### Component Specifications

- **indian_symptom_dataset_layman_50plus_with_doctor.csv**  
  Core training dataset: symptom features linked to disease outcomes and specialist recommendations.

- **xgbtest.py**  
  Production inference module. Implements interactive command-line interface for real-time prediction. Accepts free-text symptom input and returns calibrated disease probability scores with specialist recommendations.

- **Model Artifacts (*.pkl)**  
  Serialized machine learning models, label encoders, and feature metadata. Required for deployment-stage inference.

---

## Machine Learning Models

### Model Selection: XGBoost

This system leverages **XGBoost (Extreme Gradient Boosting)** for both disease classification and specialist recommendation. XGBoost was selected for the following technical advantages:

**Why XGBoost:**
- **Gradient Boosting Efficiency**: Iteratively corrects predictions through sequential tree ensembles, achieving superior accuracy with interpretable decision logic
- **Binary Feature Optimization**: Naturally handles binary symptom features without preprocessing overhead
- **Non-Linear Pattern Recognition**: Captures complex symptom-disease relationships that linear classifiers cannot model
- **Regularization Mechanisms**: Built-in L1/L2 regularization prevents overfitting on synthetic training data
- **Calibrated Probabilities**: Produces well-calibrated confidence scores essential for ranking multiple disease hypotheses
- **Production-Ready**: Fast inference times suitable for real-time interactive systems

### Model Architecture Overview

**Dual-Model Pipeline:**

1. **Disease Classification Model** (`disease_model.pkl`)
   - Input: Binary symptom vector
   - Output: Probability distribution across all disease categories
   - Task: Multi-class classification predicting the most likely disease condition
   - Architecture: Gradient-boosted tree ensemble with categorical cross-entropy optimization

2. **Specialist Recommendation Model** (`doctor_model.pkl`)
   - Input: Binary symptom vector
   - Output: Probability distribution across specialist disciplines
   - Task: Multi-class classification predicting appropriate medical specialist
   - Architecture: Gradient-boosted tree ensemble optimized for specialist routing

### How Models Process Predictions

**Inference Pipeline:**

1. User provides free-text symptom input (e.g., "fever, cough, breathing problem")
2. Natural language input is mapped to binary symptom features using pre-defined mappings
3. Disease model generates probability scores for all disease classes
4. Top 3 highest-probability diseases are extracted with confidence percentages
5. Specialist model independently predicts the recommended medical specialty
6. Results are formatted and presented to the user with ranked confidence metrics

### Model Training & Specifications

**Training Approach:**
- Supervised learning with balanced dataset to prevent class imbalance bias
- Features: 50+ binary symptom columns normalized to {0, 1}
- Training objective: Minimize log loss (cross-entropy) across disease categories
- Regularization: XGBoost default hyperparameters optimized for generalization

**Key Hyperparameters:**
- Tree depth: Controlled to prevent overfitting on synthetic patterns
- Learning rate: Moderate step size for stable convergence
- Boosting rounds: Sufficient iterations for pattern convergence without memorization

**Output Characteristics:**
- Confidence scores: Softmax-calibrated probabilities summing to 100%
- Ranking: Diseases sorted by descending probability
- Uncertainty quantification: Low-probability predictions indicate model confidence limitations

---

## Installation & Execution

### Prerequisites

Requires Python 3.9 or later.

Install required dependencies:
```bash
pip install numpy pandas scikit-learn xgboost joblib
```

### Running the System

Execute the inference engine:
```bash
python xgbtest.py
```

Example Execution:
```
Enter symptoms: fever cough breathing problem

Predicted Conditions (Confidence Ranking):
1. pneumonia            : 62.10%
2. tuberculosis         : 21.45%
3. covid19              :  9.80%

Recommended Specialist:
pulmonologist
```

Exit the application with `Ctrl + C`.

---

## Important Disclaimers & Limitations

- **Non-Medical Device**: This system is not a medical diagnostic tool and must not be used for clinical decision-making
- **Evidence Scope**: Predictions derive exclusively from symptom pattern recognition without clinical validation
- **Incomplete Assessment**: No laboratory diagnostics, imaging, or clinical examination data are incorporated
- **Dataset Nature**: Training data is synthetically generated and designed for educational and prototyping scenarios only

---
