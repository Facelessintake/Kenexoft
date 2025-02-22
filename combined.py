import pandas as pd
import matplotlib.pyplot as plt
from sklearn.ensemble import IsolationForest
import numpy as np
import os
import datetime

# Define log folders
LOG_FOLDERS = {
    'firewall': "logs/firewall_logs",
    'email': "logs/email_logs",
    'dns': "logs/dns_logs",
    'user_activity': "logs/activity",
    'network': "logs/network",
    'endpoint': "logs/endpoint",
    'application': "logs/application"
}

OUTPUT_BASE_PATH = "static"

# Get the latest log file from a specified folder
def get_latest_log(log_folder):
    try:
        files = [os.path.join(log_folder, f) for f in os.listdir(log_folder) if f.endswith(".csv")]
        if not files:
            return None
        latest_file = max(files, key=os.path.getctime)
        return latest_file
    except Exception as e:
        # print(f"❌ Error accessing log files in {log_folder}: {e}")
        return None

# Load logs and normalize column names
def load_logs(file_path):
    try:
        logs = pd.read_csv(file_path)
        logs.columns = logs.columns.str.lower().str.replace(' ', '_')
        if 'timestamp' not in logs.columns:
            return None
        logs['timestamp'] = pd.to_datetime(logs['timestamp'], errors='coerce')
        logs.dropna(subset=['timestamp'], inplace=True)
        logs.set_index('timestamp', inplace=True)
        return logs
    except Exception as e:
        # print(f"❌ Error loading {file_path}: {e}")
        return None

# Extract features
def extract_features(logs):
    if logs is None or logs.empty:
        return None
    numeric_columns = logs.select_dtypes(include=[np.number]).columns.tolist()
    if not numeric_columns:
        return None
    features = logs.resample('h')[numeric_columns].agg(['sum', 'mean', 'std', 'max', 'min']).reset_index()
    features.columns = ['timestamp'] + ['{}_{}'.format(col[0], col[1]) for col in features.columns[1:]]
    return features

# Detect anomalies
def detect_anomalies(features, contamination=0.1):
    if features is None or features.empty:
        return None
    anomaly_columns = [col for col in features.columns if col != 'timestamp']
    if not anomaly_columns:
        return None
    model = IsolationForest(contamination=contamination, random_state=42)
    features['Anomaly_Score'] = model.fit_predict(features[anomaly_columns])
    features['Anomaly'] = features['Anomaly_Score'] == -1
    return features

# Save anomaly graph
def save_anomaly_graph(features, log_type):
    output_folder = os.path.join(OUTPUT_BASE_PATH, log_type)
    os.makedirs(output_folder, exist_ok=True)
    timestamp = datetime.datetime.now().strftime("%Y%m%d_%H%M%S")
    old_output_file = os.path.join(output_folder, f'anomalies_{timestamp}.png')
    output_file = os.path.join(output_folder, 'anomalies.png')

    if os.path.exists(output_file):
        os.rename(output_file, old_output_file)

    plt.figure(figsize=(12, 6))
    metric = features.columns[1]  # Plot the first metric
    plt.plot(features['timestamp'], features[metric], label=metric, marker='o', linestyle='-', alpha=0.7)
    anomalies = features[features['Anomaly']]
    plt.scatter(anomalies['timestamp'], anomalies[metric], color='red', label='Anomalies', marker='x', s=100)
    plt.title(f'Anomalies in {log_type.capitalize()} Logs')
    plt.xlabel('Timestamp')
    plt.ylabel('Metrics')
    plt.legend()
    plt.grid(True)
    plt.tight_layout()
    plt.savefig(output_file)
    plt.close()

# Main function: Runs for all log types
def main():
    # print("\n🚀 Running anomaly detection on all log types...\n")

    for log_type, folder in LOG_FOLDERS.items():
        # print(f"🔍 Checking latest log file for: {log_type}")

        log_file = get_latest_log(folder)
        
        if not log_file:
            # print(f"❌ No log files found for {log_type}\n")
            continue

        # print(f"✅ Processing latest log file: {log_file}")

        logs = load_logs(log_file)
        if logs is None:
            # print("❌ Failed to load log data.\n")
            continue

        features = extract_features(logs)
        if features is None:
            # print("❌ Failed to extract features.\n")
            continue

        features = detect_anomalies(features)
        if features is None:
            # print("❌ Failed to detect anomalies.\n")
            continue

        save_anomaly_graph(features, log_type)
        # print(f"✅ Anomaly graph saved for {log_type}. Check static/{log_type}/anomalies.png.\n")

    # print("🎉 Done! All logs processed.\n")

if __name__ == "__main__":
    main()
