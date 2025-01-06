from flask import Flask, render_template, request
import pandas as pd
import matplotlib.pyplot as plt
import io
import base64

app = Flask(__name__)

def load_and_preprocess_log(file_path, log_type):
    """
    Load and preprocess log based on log type.
    Args:
        file_path (str): Path to the log file.
        log_type (str): Type of log ('Network Traffic', 'User Activity', 'Firewall Logs', 'Application Log', 'DNS Query Log', 'Email Log', 'Endpoint Log').
    Returns:
        DataFrame: Preprocessed log data.
    """
    try:
        logs = pd.read_csv(file_path)
        if log_type == 'Network Traffic' or log_type == 'Firewall Logs' or log_type == 'User Activity':
            logs['Timestamp'] = pd.to_datetime(logs['Timestamp'], errors='coerce')
            logs = logs.dropna(subset=['Timestamp'])
            logs.set_index('Timestamp', inplace=True)
        elif log_type == 'Application Log':
            # Apply specific preprocessing for application logs (e.g., extracting timestamps, error severity)
            logs['Timestamp'] = pd.to_datetime(logs['Timestamp'], errors='coerce')
            logs = logs.dropna(subset=['Timestamp'])
            logs.set_index('Timestamp', inplace=True)
        elif log_type == 'DNS Query Log':
            # Apply preprocessing for DNS logs
            logs['Timestamp'] = pd.to_datetime(logs['Timestamp'], errors='coerce')
            logs = logs.dropna(subset=['Timestamp'])
            logs.set_index('Timestamp', inplace=True)
        elif log_type == 'Email Log':
            # Apply preprocessing for email logs (e.g., check the time of email sent/received)
            logs['Timestamp'] = pd.to_datetime(logs['Timestamp'], errors='coerce')
            logs = logs.dropna(subset=['Timestamp'])
            logs.set_index('Timestamp', inplace=True)
        elif log_type == 'Endpoint Log':
            # Apply preprocessing for endpoint logs
            logs['Timestamp'] = pd.to_datetime(logs['Timestamp'], errors='coerce')
            logs = logs.dropna(subset=['Timestamp'])
            logs.set_index('Timestamp', inplace=True)
        else:
            raise ValueError("Unsupported log type.")
        return logs
    except Exception as e:
        print(f"Error loading log: {e}")
        return None

def detect_anomalies(logs, group_by='H', threshold=2):
    """
    Simple anomaly detection based on log count trends.
    Args:
        logs (DataFrame): Preprocessed logs.
        group_by (str): Grouping interval ('T' for minute, 'H' for hour, 'D' for day).
        threshold (float): Z-score threshold for anomalies.
    Returns:
        DataFrame: Log counts with anomaly labels.
    """
    log_counts = logs.resample(group_by).size().reset_index(name='Log Count')
    mean_logs = log_counts['Log Count'].mean()
    std_logs = log_counts['Log Count'].std()
    log_counts['Z-Score'] = (log_counts['Log Count'] - mean_logs) / std_logs
    log_counts['Anomaly'] = log_counts['Z-Score'].abs() > threshold
    return log_counts

def plot_anomalies(log_counts):
    """
    Generate and plot anomalies, and return the image as a base64 string along with anomaly count.
    """
    plt.figure(figsize=(12, 6))
    plt.plot(log_counts[log_counts.columns[0]], log_counts['Log Count'], label='Log Count', color='blue', marker='o')
    plt.scatter(
        log_counts.loc[log_counts['Anomaly'], log_counts.columns[0]],
        log_counts.loc[log_counts['Anomaly'], 'Log Count'],
        color='red',
        label='Anomalies',
        marker='x',
        s=200
    )
    plt.axhline(log_counts['Log Count'].mean(), color='green', linestyle='--', label='Mean Log Count')
    plt.title('Log Counts with Anomalies')
    plt.xlabel('Timestamp')
    plt.ylabel('Log Count')
    plt.legend()
    plt.grid(True)
    plt.tight_layout()

    # Save the plot to a BytesIO object
    img = io.BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)

    # Convert the image to base64
    img_base64 = base64.b64encode(img.getvalue()).decode('utf-8')

    # Count the anomalies
    anomaly_count = log_counts['Anomaly'].sum()

    return img_base64, anomaly_count

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/upload', methods=['POST'])
def upload():
    if 'file' not in request.files:
        return "No file part", 400
    file = request.files['file']
    if file.filename == '':
        return "No selected file", 400
    
    # Save the file temporarily
    file_path = f"uploads/{file.filename}"
    file.save(file_path)

    log_type = request.form.get('log_type')

    # Load logs and detect anomalies based on selected log type
    logs = load_and_preprocess_log(file_path, log_type)
    if logs is None:
        return "Error processing the file.", 400
    
    log_counts = detect_anomalies(logs)
    
    # Generate the plot and get the base64 image and anomaly count
    img_base64, anomaly_count = plot_anomalies(log_counts)

    # Render the results page with the plot and anomaly count
    return render_template('results.html', img_base64=img_base64, anomaly_count=anomaly_count)

if __name__ == '__main__':
    app.run(debug=True)
