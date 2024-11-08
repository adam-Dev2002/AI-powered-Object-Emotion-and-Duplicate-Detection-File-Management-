import os
import sys
import subprocess
import json
import mysql.connector
from ultralytics import YOLO
import cv2  # For video processing
from datetime import datetime
import hashlib


# Background launch code
if 'BACKGROUND' not in os.environ:
    env = os.environ.copy()
    env['BACKGROUND'] = '1'
    subprocess.Popen([sys.executable] + sys.argv, env=env)
    sys.exit()
    
# Load YOLO detection model
detect_model = YOLO('yolo11n.pt')  # Updated to the new model

# Confidence threshold for storing detections
CONFIDENCE_THRESHOLD = 0.1

# MySQL Database Connection


# MySQL Database Connection
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="capstone2425",
    database="greyhoundhub"
)

# Create a cursor object
cursor = db.cursor()


# Supported file types
image_extensions = ['.jpg', '.jpeg', '.png', '.gif']
video_extensions = ['.mp4', '.avi', '.mov']

# Function to calculate file hash
def calculate_file_hash(file_path):
    hash_sha256 = hashlib.sha256()
    with open(file_path, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_sha256.update(chunk)
    return hash_sha256.hexdigest()

# Function to update tags, file hash, and last_scanned timestamp in the database
def update_file_metadata(file_id, tags, new_hash):
    try:
        tags_json = json.dumps(tags) if tags else None
        current_time = datetime.now()
        sql = "UPDATE files SET tags = %s, last_scanned = %s, file_hash = %s WHERE id = %s"
        cursor.execute(sql, (tags_json, current_time, new_hash, file_id))
        db.commit()
        print(f"Updated tags for file ID {file_id}")
    except Exception as e:
        print(f"Database error while updating file metadata for file ID {file_id}: {e}")

# Function to handle YOLO detection on a single frame
def detect_on_frame(frame):
    try:
        detection_results = detect_model(frame)
        detected_objects = [
            detect_model.names[int(box.cls)]
            for box in detection_results[0].boxes
            if box.conf >= CONFIDENCE_THRESHOLD
        ]
        return detected_objects
    except Exception as e:
        print(f"Error processing frame: {e}")
        return []

# Function to scan a video file for detections
def scan_video(file_path):
    detected_terms = set()
    cap = cv2.VideoCapture(file_path)
    frame_count = 0

    while cap.isOpened():
        ret, frame = cap.read()
        if not ret:
            break
        frame_count += 1

        # Perform detection on every nth frame for efficiency
        if frame_count % 10 == 0:
            detected_objects = detect_on_frame(frame)
            detected_terms.update(detected_objects)

    cap.release()
    return {"detection": list(detected_terms)}

# Function to scan an image file for detections
def scan_image(file_path):
    try:
        detection_results = detect_model(file_path)
        detected_objects = [
            detect_model.names[int(box.cls)]
            for box in detection_results[0].boxes
            if box.conf >= CONFIDENCE_THRESHOLD
        ]
        return {"detection": detected_objects}
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return None

# Function to retrieve only new or modified files from the database
def get_files_for_processing():
    try:
        cursor.execute("SELECT id, filepath, filetype, file_hash FROM files")
        files = cursor.fetchall()
        files_to_process = []

        for file_id, file_path, file_type, stored_hash in files:
            if os.path.exists(file_path):
                new_hash = calculate_file_hash(file_path)
                # Process if hash is new or changed
                if stored_hash != new_hash:
                    files_to_process.append((file_id, file_path, file_type, new_hash))
            else:
                print(f"File not found: {file_path}")

        return files_to_process
    except Exception as e:
        print(f"Database error while fetching files: {e}")
        return []

# Main function to process only new or modified files
def process_files():
    files = get_files_for_processing()
    for file_id, file_path, file_type, new_hash in files:
        # Process video files first
        if file_type.lower() in video_extensions:
            print(f"Processing video file: {file_path}")
            tags = scan_video(file_path)
        # Then process image files
        elif file_type.lower() in image_extensions:
            print(f"Processing image file: {file_path}")
            tags = scan_image(file_path)
        else:
            print(f"Unsupported file type: {file_type}")
            continue

        if tags:
            # Update database with tags, new hash, and last scanned timestamp
            update_file_metadata(file_id, tags, new_hash)

# Run the function to process and update tags for only new or modified files
process_files()

# Close database connection
cursor.close()
db.close()
