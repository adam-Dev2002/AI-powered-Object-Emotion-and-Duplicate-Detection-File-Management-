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
from deepface import DeepFace  # Added for emotion detection

# Load YOLO models for detection, classification, and pose
models = {
    'detection': YOLO('yolo11n.pt'),         # Object Detection
    'classification': YOLO('yolo11n-cls.pt'), # Image Classification
    'pose': YOLO('yolo11n-pose.pt')          # Pose Estimation
}

# Confidence threshold for storing detections
CONFIDENCE_THRESHOLD = 0.1

# AFP volume path
AFP_VOLUME_PATH = '/Volumes/creative/Hara All About/HARA sa FU 2025/Photoshoot'


# MySQL Database Connection
def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="capstone2425",
            database="greyhound_creative",
            autocommit=True  # Ensure changes are saved permanently
        )
        return connection
    except mysql.connector.Error as err:
        print(f"Error: {err}")
        sys.exit(1)

# Initialize global database connection
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
        with db.cursor(dictionary=True) as cursor:
            # Check for missing metadata
            cursor.execute("""
                SELECT datecreated, filehash, detected_objects, classification, pose, gesture, emotion 
                FROM files WHERE id = %s
            """, (file_id,))
            result = cursor.fetchone()

            if not result:
                print(f"Error: File with ID {file_id} not found.")
                return

            # Extract missing fields
            datecreated = result.get('datecreated')
            filehash = result.get('filehash')
            detected_objects = result.get('detected_objects')
            classification = result.get('classification')
            pose = result.get('pose')
            gesture = result.get('gesture')
            emotion = result.get('emotion')

            # Populate missing creation date
            if not datecreated:
                creation_date = get_file_creation_date(filepath)
                if creation_date:
                    cursor.execute("UPDATE files SET datecreated = %s WHERE id = %s", (creation_date, file_id))
                    print(f"Populated datecreated for file ID {file_id}")

            # Populate missing filehash
            if not filehash:
                new_filehash = calculate_filehash(filepath)
                if new_filehash:
                    cursor.execute("UPDATE files SET filehash = %s WHERE id = %s", (new_filehash, file_id))
                    print(f"Populated filehash for file ID {file_id}")

            # Populate detected_objects, classification, pose, gesture, and emotion
            file_type = filepath.split('.')[-1].lower()
            if not detected_objects or not classification or not pose or not gesture or not emotion:
                process_file(file_id, filepath, file_type)
                print(f"Populated AI analysis results for file ID {file_id}")

            db.commit()

    except Exception as e:
        print(f"Error populating missing columns for file ID {file_id}: {e}")




# Ensure required columns in the database
def ensure_required_columns():
    required_columns = ['classification', 'pose', 'gesture', 'detected_objects', 'filehash', 'content_hash', 'emotion']
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
def file_exists(filehash, content_hash):
    """Check if a file with the same hash or AI content already exists."""
    try:
        with db.cursor(dictionary=True) as cursor:
            query = "SELECT * FROM files WHERE filehash = %s OR content_hash = %s"
            cursor.execute(query, (filehash, content_hash))
            duplicates = cursor.fetchall()
            if duplicates:
                print(f"Duplicate detected based on filehash or content analysis: {duplicates}")
                return True
        return False
    except Exception as e:
        print(f"Error checking file existence: {e}")
        return False



# Improved Map keypoints to gestures with added accuracy
def interpret_gesture(keypoints):
    """Interpret gestures based on pose keypoints."""
    try:
        if len(keypoints) < 17:
            return "No clear gesture detected"

        # Extract body keypoints
        left_wrist = keypoints[9]
        right_wrist = keypoints[10]
        left_shoulder = keypoints[5]
        right_shoulder = keypoints[6]
        left_knee = keypoints[13]
        right_knee = keypoints[14]
        left_ankle = keypoints[15]
        right_ankle = keypoints[16]

        # Helper to calculate distance
        def distance(p1, p2):
            return ((p1[0] - p2[0]) ** 2 + (p1[1] - p2[1]) ** 2) ** 0.5

        # Movement Detection Logic
        if left_wrist[1] < left_shoulder[1] or right_wrist[1] < right_shoulder[1]:
            return "Raising Hands"
        if distance(left_wrist, right_wrist) < 30:
            return "Clapping"
        if abs(left_ankle[1] - right_ankle[1]) > 20:
            return "Walking"
        return "No significant movement detected"
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
    except Exception as e:
        print(f"Error checking duplicates: {e}")




# Updated scan_and_process_directory function
def scan_and_process_directory(directory):
    print("Scanning directory and processing files...")
    start_time = time.time()

    # Calculate total files for progress
    total_files = sum([len(files) for _, _, files in os.walk(directory)])
    processed_files = 0

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
            content_hash = None  # Initialize content_hash

            if not filehash:
                print(f"Error: Unable to calculate filehash for {filepath}")
                continue

            # Check for duplicates
            if file_exists(filehash, content_hash):
                print(f"Duplicate detected: {filepath}")
                alert_and_handle_duplicates(filehash, filepath)
                continue

            try:
                # Insert file metadata into the database
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

                # Process the file and update AI results
                process_file(file_id, filepath, filetype)

                processed_files += 1
                progress_percentage = (processed_files / total_files) * 100
                print(f"Progress: {progress_percentage:.2f}%")
                sys.stdout.flush()

            except Exception as e:
                print(f"Error processing file '{file}': {e}")

    end_time = time.time()
    print(f"Finished scanning. Time taken: {end_time - start_time:.2f} seconds.")



def calculate_content_hash(detected_objects, pose_gesture, emotions):
    """Calculate a hash combining objects, pose gestures, and emotions."""
    try:
        content_string = json.dumps({
            "objects": detected_objects,
            "gesture": pose_gesture,
            "emotions": emotions
        }, sort_keys=True)
        return hashlib.sha256(content_string.encode()).hexdigest()
    except Exception as e:
        print(f"Error calculating content hash: {e}")
        return None

# Process individual files
def process_file(file_id, file_path, file_type):
    detected_objects = []
    classification = {}
    pose = {}
    gesture = "No gesture detected"
    emotion = "Unknown"
    content_hash = None  # Initialize content hash

    try:
        if file_type in image_extensions:
            # Perform detection, classification, pose estimation, and emotion detection
            detected_objects = scan_image(file_path) or []
            classification = classify_image(file_path) or {}
            pose_data = estimate_pose(file_path) or {}
            pose = pose_data.get("keypoints", [])
            gesture = pose_data.get("gesture", "No gesture detected")
            emotion = detect_emotion(file_path) or "Unknown"

            # Generate a content hash based on AI results
            combined_content = json.dumps({
                "detected_objects": detected_objects,
                "classification": classification,
                "pose": pose,
                "gesture": gesture,
                "emotion": emotion
            })
            content_hash = hashlib.sha256(combined_content.encode()).hexdigest()

        elif file_type in video_extensions:
            # Process video files for detection and emotion analysis
            detected_objects = scan_video(file_path) or []
            emotion = analyze_emotion_in_video(file_path) or "Neutral"

            # Generate a content hash for video AI results
            combined_content = json.dumps({
                "detected_objects": detected_objects,
                "emotion": emotion
            })
            content_hash = hashlib.sha256(combined_content.encode()).hexdigest()

        # Update the database with the results
        sql = """
            UPDATE files
            SET detected_objects = %s, classification = %s, pose = %s, 
                gesture = %s, emotion = %s, content_hash = %s, last_scanned = %s
            WHERE id = %s
        """
        with db.cursor() as cursor:
            cursor.execute(sql, (
                json.dumps(detected_objects),
                json.dumps(classification),
                json.dumps(pose),
                gesture,
                emotion,
                content_hash,
                datetime.now(),
                file_id
            ))
        db.commit()
        print(f"Updated file ID {file_id} with AI results and content_hash.")
    except Exception as e:
        print(f"Error processing file ID {file_id}: {e}")



def detect_emotion(file_path):
    """Detect emotions in an image using DeepFace."""
    try:
        img = cv2.imread(file_path)
        if img is None:
            return "Unknown"
        result = DeepFace.analyze(img, actions=['emotion'], enforce_detection=False)
        return result[0]['dominant_emotion']
    except Exception as e:
        print(f"Error detecting emotion in image: {e}")
        return "Unknown"

def analyze_emotion_in_video(video_path):
    """Analyze dominant emotion from video frames."""
    try:
        cap = cv2.VideoCapture(video_path)
        emotions_detected = []
        frame_count = 0
        frame_interval = 30  # Analyze every 30 frames

        while cap.isOpened():
            ret, frame = cap.read()
            if not ret:
                break

            if frame_count % frame_interval == 0:
                try:
                    result = DeepFace.analyze(frame, actions=['emotion'], enforce_detection=False)
                    emotions_detected.append(result[0]['dominant_emotion'])
                except Exception:
                    pass
            frame_count += 1

        cap.release()
        return max(set(emotions_detected), key=emotions_detected.count, default="Neutral")
    except Exception as e:
        print(f"Error processing video emotion: {e}")
        return "Neutral"



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
