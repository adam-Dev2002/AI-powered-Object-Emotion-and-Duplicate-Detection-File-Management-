import os
import json
import hashlib
import mysql.connector
from ultralytics import YOLO
import cv2  # For video processing
from datetime import datetime
import numpy as np
import sys
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
import time

# Load YOLO models for detection, classification, and pose
models = {
    'detection': YOLO('yolo11n.pt'),         # Object Detection
    'classification': YOLO('yolo11n-cls.pt'), # Image Classification
    'pose': YOLO('yolo11n-pose.pt')          # Pose Estimation
}

# Confidence threshold for storing detections
CONFIDENCE_THRESHOLD = 0.1

# AFP volume path
AFP_VOLUME_PATH = '/Volumes/creative/greyhoundhub/FU_EVENTS/Dal-uy'


# MySQL Database Connection
def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="capstone2425",
        database="greyhound_creative"
    )

db = get_db_connection()

# Supported file types
image_extensions = {'jpg', 'jpeg', 'png', 'gif'}
video_extensions = {'mp4', 'avi', 'mov'}

# Check if AFP volume is mounted
def check_afp_volume():
    base_volume = '/Volumes/creative'
    if not os.path.ismount(base_volume):
        print(f"Error: AFP base volume '{base_volume}' is not mounted.")
        sys.exit(1)
    if not os.path.exists(AFP_VOLUME_PATH):
        print(f"Error: Target folder '{AFP_VOLUME_PATH}' does not exist.")
        sys.exit(1)
    print(f"AFP volume '{AFP_VOLUME_PATH}' is mounted and accessible.")




def get_file_creation_date(filepath):
    """Extract the file creation date."""
    try:
        creation_time = os.path.getctime(filepath)
        return datetime.fromtimestamp(creation_time).strftime('%Y-%m-%d %H:%M:%S')
    except Exception as e:
        print(f"Error retrieving creation date for {filepath}: {e}")
        return None

def populate_missing_columns(file_id, filepath):
    """Populate missing columns in the database."""
    try:
        with db.cursor() as cursor:
            # Check for missing metadata
            cursor.execute("SELECT datecreated, filehash FROM files WHERE id = %s", (file_id,))
            result = cursor.fetchone()

            # Extract missing fields
            datecreated, filehash = result

            # Populate missing file creation date
            if not datecreated:
                creation_date = get_file_creation_date(filepath)
                if creation_date:
                    cursor.execute(
                        "UPDATE files SET datecreated = %s WHERE id = %s",
                        (creation_date, file_id)
                    )
                    print(f"Populated datecreated for file ID {file_id}")

            # Populate missing filehash
            if not filehash:
                new_filehash = calculate_filehash(filepath)
                if new_filehash:
                    cursor.execute(
                        "UPDATE files SET filehash = %s WHERE id = %s",
                        (new_filehash, file_id)
                    )
                    print(f"Populated filehash for file ID {file_id}")

            # Commit changes
            db.commit()

    except Exception as e:
        print(f"Error populating missing columns for file ID {file_id}: {e}")




# Ensure required columns in the database
def ensure_required_columns():
    required_columns = ['classification', 'pose', 'gesture', 'detected_objects', 'filehash']  # Added 'gesture'
    try:
        with db.cursor() as cursor:
            for column in required_columns:
                cursor.execute(f"""
                    SELECT COUNT(*) AS column_exists
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_NAME = 'files' AND COLUMN_NAME = '{column}'
                """)
                column_exists = cursor.fetchone()[0]
                if column_exists == 0:
                    cursor.execute(f"ALTER TABLE files ADD COLUMN {column} TEXT")
                    print(f"Added `{column}` column to the `files` table.")
            db.commit()
    except Exception as e:
        print(f"Error ensuring required columns: {e}")


# Compute file size
def get_file_size(filepath):
    try:
        return os.path.getsize(filepath)
    except Exception as e:
        print(f"Error getting file size for {filepath}: {e}")
        return None

# Calculate file hash
def calculate_filehash(filepath):
    """Calculate the hash of a file using SHA-256."""
    try:
        hasher = hashlib.sha256()
        with open(filepath, 'rb') as f:
            while chunk := f.read(8192):
                hasher.update(chunk)
        return hasher.hexdigest()
    except Exception as e:
        print(f"Error hashing file {filepath}: {e}")
        return None

# Check if file already exists in the database based on filehash
def file_exists(filehash):
    """Check if a file with the given hash already exists in the database."""
    try:
        with db.cursor() as cursor:
            cursor.execute("SELECT COUNT(*) FROM files WHERE filehash = %s", (filehash,))
            return cursor.fetchone()[0] > 0
    except Exception as e:
        print(f"Error checking file existence: {e}")
        return False

# Improved Map keypoints to gestures with added accuracy
def interpret_gesture(keypoints):
    try:
        if len(keypoints) < 17:
            return "No clear gesture detected"

        # Define key body parts for readability
        nose = keypoints[0]
        left_eye = keypoints[1]
        right_eye = keypoints[2]
        left_ear = keypoints[3]
        right_ear = keypoints[4]
        left_shoulder = keypoints[5]
        right_shoulder = keypoints[6]
        left_elbow = keypoints[7]
        right_elbow = keypoints[8]
        left_wrist = keypoints[9]
        right_wrist = keypoints[10]
        left_hip = keypoints[11]
        right_hip = keypoints[12]
        left_knee = keypoints[13]
        right_knee = keypoints[14]
        left_ankle = keypoints[15]
        right_ankle = keypoints[16]

        # Calculate distances and angles for gesture recognition
        def distance(p1, p2):
            return ((p1[0] - p2[0]) ** 2 + (p1[1] - p2[1]) ** 2) ** 0.5

        def angle(p1, p2, p3):
            a = distance(p2, p3)
            b = distance(p1, p3)
            c = distance(p1, p2)
            if a * b == 0:
                return 0
            return math.acos((a**2 + b**2 - c**2) / (2 * a * b)) * (180 / math.pi)

        # Gesture: Walking
        if (distance(left_ankle, right_ankle) > 50 and
            distance(left_knee, right_knee) > 50 and
            abs(left_ankle[1] - right_ankle[1]) > 20):
            return "Walking detected"

        # Gesture: Dancing
        if (left_wrist[1] < left_shoulder[1] or right_wrist[1] < right_shoulder[1]):
            return "Dancing detected"

        # Gesture: Clapping
        if distance(left_wrist, right_wrist) < 30:
            return "Clapping detected"

        # Gesture: Smiling
        if (distance(left_eye, left_ear) > 20 and
            distance(right_eye, right_ear) > 20 and
            distance(left_eye, right_eye) > 30):
            return "Smiling detected"

        return "No significant gesture detected"
    except Exception as e:
        print(f"Error interpreting gesture: {e}")
        return "Error during gesture interpretation"




# YOLO functions
def scan_image(file_path):
    try:
        detection_results = models['detection'](file_path)
        return [
            models['detection'].names[int(box.cls)]
            for box in detection_results[0].boxes
            if box.conf >= CONFIDENCE_THRESHOLD
        ]
    except Exception as e:
        print(f"Error processing image {file_path}: {e}")
        return []

def classify_image(file_path):
    try:
        results = models['classification'](file_path)
        top_class = results[0].probs.top1  # Most probable class index
        top_class_conf = results[0].probs.top1conf  # Confidence of the top class
        return {
            "class": models['classification'].names[top_class],
            "confidence": top_class_conf.item()
        }
    except Exception as e:
        print(f"Error during classification for {file_path}: {e}")
        return {}

def estimate_pose(file_path):
    try:
        results = models['pose'](file_path)
        keypoints = results[0].keypoints.xy.tolist() if results[0].keypoints is not None else []
        gesture = interpret_gesture(keypoints) if keypoints else "No gesture detected"
        return {"keypoints": keypoints, "gesture": gesture}
    except Exception as e:
        print(f"Error during pose estimation for {file_path}: {e}")
        return {"keypoints": [], "gesture": "Error during pose estimation"}

# Improved duplicate handling to scan duplicates
def alert_and_handle_duplicates(filehash, filepath):
    try:
        with db.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT * FROM files WHERE filehash = %s", (filehash,))
            duplicates = cursor.fetchall()

        if duplicates:
            print(f"Duplicate detected for {filepath}: Existing files:")
            for duplicate in duplicates:
                print(f"- {duplicate['filepath']} (Uploaded on {duplicate['dateupload']})")
            
            # Continue scanning even if duplicates are found
            return duplicates
        return None
    except Exception as e:
        print(f"Error checking for duplicates: {e}")
        return None



# Updated scan_and_process_directory function
def scan_and_process_directory(directory):
    print("Scanning directory and processing files...")
    start_time = time.time()  # Start time for the scan

    for root, _, files in os.walk(directory):
        for file in files:
            filepath = os.path.join(root, file)
            filetype = file.split('.')[-1].lower()

            # Skip unsupported file types
            if filetype not in image_extensions.union(video_extensions):
                print(f"Skipping unsupported file: {file}")
                continue

            # Calculate filehash
            filehash = calculate_filehash(filepath)
            if not filehash:
                print(f"Error: Unable to calculate filehash for {filepath}")
                continue

            try:
                # Insert file into database or get its ID
                size = get_file_size(filepath)
                creation_date = get_file_creation_date(filepath)
                sql = """
                    INSERT INTO files (filename, filepath, filehash, filetype, size, dateupload, datecreated)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
                """
                with db.cursor() as cursor:
                    cursor.execute(sql, (
                        os.path.basename(filepath),
                        filepath,
                        filehash,
                        filetype,
                        size,
                        datetime.now(),
                        creation_date
                    ))
                    file_id = cursor.lastrowid
                db.commit()

                # Populate missing metadata if needed
                populate_missing_columns(file_id, filepath)

                # Process file for AI (optional)
                process_file(file_id, filepath, filetype)

            except Exception as e:
                print(f"Error processing file '{file}': {e}")

    end_time = time.time()
    print(f"Finished scanning. Time taken: {end_time - start_time} seconds.")


# Process individual files
def process_file(file_id, file_path, file_type):
    detected_objects = []
    classification = {}
    pose = {}
    gesture = None  # New addition

    if file_type in image_extensions:
        try:
            detected_objects = scan_image(file_path)
            classification = classify_image(file_path)
            pose_data = estimate_pose(file_path)
            pose = pose_data.get("keypoints", [])
            gesture = pose_data.get("gesture", "No gesture detected")
        except Exception as e:
            print(f"Error during AI processing for file '{file_path}': {e}")
    elif file_type in video_extensions:
        try:
            detected_objects = scan_video(file_path)
        except Exception as e:
            print(f"Error during video processing for file '{file_path}': {e}")
    else:
        print(f"Unsupported file type: {file_type}")
        return

    try:
        # Update database with new data, including gesture
        sql = """
            UPDATE files
            SET
                detected_objects = %s,
                classification = %s,
                pose = %s,
                gesture = %s,  -- New addition
                last_scanned = %s
            WHERE id = %s
        """
        with db.cursor() as cursor:
            cursor.execute(sql, (
                json.dumps(detected_objects),
                json.dumps(classification),
                json.dumps(pose),
                gesture,  # Add gesture here
                datetime.now(),
                file_id
            ))
        db.commit()
        print(f"Updated file ID {file_id} with AI results.")
    except Exception as e:
        print(f"Error updating file ID {file_id}: {e}")


# Monitor directory changes and trigger scan
class TriggerHandler(FileSystemEventHandler):
    def on_any_event(self, event):
        if event.is_directory:
            return  # Ignore directory changes
        if event.event_type in ('created', 'modified'):
            print(f"Detected {event.event_type} for file: {event.src_path}")
            scan_and_process_directory(AFP_VOLUME_PATH)

def monitor_directory():
    """Start monitoring the directory for changes."""
    event_handler = TriggerHandler()
    observer = Observer()
    observer.schedule(event_handler, path=AFP_VOLUME_PATH, recursive=True)
    observer.start()
    print(f"Monitoring directory: {AFP_VOLUME_PATH}")
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()

# Main execution
if __name__ == "__main__":
    try:
        check_afp_volume()
        ensure_required_columns()
        scan_and_process_directory(AFP_VOLUME_PATH)
        monitor_directory()
    finally:
        db.close()
        print("Database connection closed.")
