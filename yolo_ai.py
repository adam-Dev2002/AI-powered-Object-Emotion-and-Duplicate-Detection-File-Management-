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
CONFIDENCE_THRESHOLD = 0.8  # or higher depending on your results


# Local folder path
LOCAL_FOLDER_PATH = '/Applications/XAMPP/xamppfiles/htdocs/testcreative'
# ✅ Define TRASH folder path to exclude from scanning
TRASH_FOLDER_PATH = os.path.join(LOCAL_FOLDER_PATH, 'TRASH')




# MySQL Database Connection
def get_db_connection():
    try:
        connection = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="fm_system",
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




def construct_web_url(file_path):
    """Ensure the stored file path is absolute and correctly formatted."""
    base_path = "/Applications/XAMPP/xamppfiles/htdocs/testcreative"

    if not file_path.startswith(base_path):
        # Ensure correct absolute path formatting
        absolute_path = os.path.join(base_path, file_path.lstrip(os.sep))
        absolute_path = absolute_path.replace("\\", "/")  # Ensure forward slashes
        return absolute_path  # ✅ Store the absolute path
    return file_path  # Already absolute, return as is




# Check if local folder exists
def check_local_folder():
    if not os.path.exists(LOCAL_FOLDER_PATH):
        print(f"Error: Target folder '{LOCAL_FOLDER_PATH}' does not exist.")
        sys.exit(1)
    print(f"Local folder '{LOCAL_FOLDER_PATH}' is accessible.")





def get_file_creation_date(filepath):
    """Extract the file creation date."""
    try:
        creation_time = os.path.getctime(filepath)
        return datetime.fromtimestamp(creation_time).strftime('%Y-%m-%d %H:%M:%S')
    except Exception as e:
        print(f"Error retrieving creation date for {filepath}: {e}")
        return None

def populate_missing_columns(filepath):
    """Ensure file exists in the database and populate missing columns, including AI scanning."""
    try:
        with db.cursor(dictionary=True) as cursor:
            # Check if file already exists based on its path
            cursor.execute("SELECT id FROM files WHERE filepath = %s", (filepath,))
            result = cursor.fetchone()

            if not result:
                # File is missing in the database, so insert it
                filehash = calculate_filehash(filepath)
                datecreated = get_file_creation_date(filepath)

                cursor.execute("""
                    INSERT INTO files (filename, filepath, filetype, size, datecreated, filehash) 
                    VALUES (%s, %s, %s, %s, %s, %s)
                """, (
                    os.path.basename(filepath),  # Extract filename
                    filepath,
                    filepath.split('.')[-1].lower(),  # File type
                    os.path.getsize(filepath),  # File size
                    datecreated,
                    filehash
                ))
                
                db.commit()
                file_id = cursor.lastrowid
                print(f"Inserted missing file into the database: {filepath} (ID: {file_id})")

                # New file - must be processed by AI
                process_file(file_id, filepath, filepath.split('.')[-1].lower())
                print(f"AI processing completed for newly inserted file ID {file_id}")

            else:
                file_id = result['id']

            # Now check and populate missing columns
            cursor.execute("""
                SELECT datecreated, filehash, detected_objects, classification, pose, gesture, emotion 
                FROM files WHERE id = %s
            """, (file_id,))
            result = cursor.fetchone()

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

            # AI Processing: Populate detected_objects, classification, pose, gesture, and emotion
            file_type = filepath.split('.')[-1].lower()
            if not detected_objects or not classification or not pose or not gesture or not emotion:
                process_file(file_id, filepath, file_type)  # Calls your AI for scanning
                print(f"AI processing completed for file ID {file_id}")

            db.commit()

    except Exception as e:
        print(f"Error populating missing columns for file: {filepath} - {e}")





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

        detected_objects = []
        bounding_boxes = []

        for box in detection_results[0].boxes:
            if box.conf >= CONFIDENCE_THRESHOLD:
                label = models['detection'].names[int(box.cls)]  # Object label
                x, y, w, h = box.xywh[0].tolist()  # Convert tensor to list (x, y, width, height)

                detected_objects.append(label)
                bounding_boxes.append({
                    "label": label, 
                    "x": x, "y": y, "width": w, "height": h,
                    "confidence": float(box.conf)  # Store confidence score
                })

        return detected_objects, bounding_boxes

    except Exception as e:
        print(f"Error processing image {file_path}: {e}")
        return [], []



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


# ✅ Ensure the 'duplicate_of' column exists in the database
def ensure_duplicate_column():
    """Ensure that the `duplicate_of` column exists in the `files` table."""
    try:
        with db.cursor() as cursor:
            cursor.execute("""
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = 'files' AND COLUMN_NAME = 'duplicate_of'
            """)
            column_exists = cursor.fetchone()[0]

            if column_exists == 0:
                cursor.execute("ALTER TABLE files ADD COLUMN duplicate_of INT NULL")
                print("Added `duplicate_of` column to the `files` table.")
        db.commit()
    except Exception as e:
        print(f"Error ensuring `duplicate_of` column: {e}")



# ✅ Check if file already exists in the database based on filehash or content_hash
def file_exists(filehash, content_hash):
    """Check if a file with the same hash or AI content already exists and store duplicates."""
    try:
        with db.cursor(dictionary=True) as cursor:
            query = "SELECT id FROM files WHERE filehash = %s OR content_hash = %s"
            cursor.execute(query, (filehash, content_hash))
            
            duplicate = cursor.fetchone()  # ✅ Fetch only one result
            
            if duplicate:
                duplicate_id = duplicate['id']
                print(f"Duplicate detected! This file matches file ID {duplicate_id}.")

                # ✅ Clear unread results
                cursor.fetchall()  # Clears remaining results in case of multiple matches
                
                # ✅ Store the duplicate reference
                cursor.execute("""
                    UPDATE files 
                    SET duplicate_of = %s 
                    WHERE filehash = %s OR content_hash = %s
                """, (duplicate_id, filehash, content_hash))

                db.commit()
                return duplicate_id  # ✅ Return duplicate file ID

        return None  # No duplicate found
    except Exception as e:
        print(f"Error checking and storing duplicate: {e}")
        return None






# ✅ Alert and handle duplicate detection properly
def alert_and_handle_duplicates(filehash, content_hash, filepath):
    """Mark the duplicate file in the database and store its reference."""
    try:
        duplicate_id = file_exists(filehash, content_hash)
        
        if duplicate_id:
            print(f"Marking {filepath} as duplicate of file ID {duplicate_id}")

            file_size = os.path.getsize(filepath)  # ✅ Get the file size

            with db.cursor() as cursor:
                cursor.execute("""
                    INSERT INTO files (filename, filepath, filehash, content_hash, filetype, size, duplicate_of)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                """, (
                    os.path.basename(filepath),
                    construct_web_url(filepath),  # Store web-accessible path
                    filehash,
                    content_hash,
                    filepath.split('.')[-1].lower(),
                    file_size,  # ✅ Include the file size
                    duplicate_id  # ✅ Reference to the original file
                ))
            db.commit()
            return True
        
        return False  # No duplicate found
    except Exception as e:
        print(f"Error handling duplicate file {filepath}: {e}")
        return False




def scan_and_process_directory(directory):
    print("Scanning directory and processing files...")
    start_time = time.time()

    total_files = 0
    processed_files = 0

    # ✅ First Pass: Count total files (excluding TRASH)
    for root, _, files in os.walk(directory):
        if TRASH_FOLDER_PATH in root:
            print(f"Skipping TRASH folder: {root}")
            continue
        total_files += len(files)

    # ✅ Second Pass: Process files
    for root, _, files in os.walk(directory):
        if TRASH_FOLDER_PATH in root:
            print(f"Skipping TRASH folder: {root}")
            continue

        for file in files:
            filepath = os.path.join(root, file)
            filetype = file.split('.')[-1].lower()

            # ✅ Skip unsupported file types
            if filetype not in image_extensions.union(video_extensions):
                print(f"Skipping unsupported file: {file}")
                continue

            # ✅ Calculate file hash
            filehash = calculate_filehash(filepath)
            content_hash = None  # Initialize content_hash

            if not filehash:
                print(f"Error: Unable to calculate filehash for {filepath}")
                continue

            # ✅ Check for duplicates and store them properly
            if alert_and_handle_duplicates(filehash, content_hash, filepath):
                continue  # Skip further processing for duplicates

            try:
                # ✅ Construct web URL for filepath
                web_url = construct_web_url(filepath)

                # ✅ Insert file metadata into the database
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
                        web_url,  # Store web-accessible file path
                        filehash,
                        filetype,
                        size,
                        datetime.now(),
                        creation_date
                    ))
                    file_id = cursor.lastrowid
                db.commit()

                # ✅ Process the file and update AI results
                process_file(file_id, filepath, filetype)

                processed_files += 1
                progress_percentage = (processed_files / total_files) * 100
                print(f"Progress: {progress_percentage:.2f}%")
                sys.stdout.flush()

            except Exception as e:
                print(f"Error processing file '{file}': {e}")

    end_time = time.time()
    print(f"Finished scanning. Time taken: {end_time - start_time:.2f} seconds.")




def scan_video(file_path):
    """Perform object detection on video frames and store bounding boxes."""
    try:
        cap = cv2.VideoCapture(file_path)
        detected_objects = set()  # Use a set to avoid duplicate labels
        bounding_boxes = []
        frame_count = 0
        frame_interval = 10  # Process every 10th frame
        max_frames = 500  # Limit to avoid infinite processing

        while cap.isOpened() and frame_count < max_frames:
            ret, frame = cap.read()
            if not ret:
                break

            if frame_count % frame_interval == 0:
                try:
                    print(f"Processing frame {frame_count} of video: {file_path}")
                    # Perform detection on the frame
                    results = models['detection'](frame)

                    for box in results[0].boxes:
                        if box.conf >= CONFIDENCE_THRESHOLD:
                            label = models['detection'].names[int(box.cls)]
                            x, y, w, h = box.xywh[0].tolist()

                            detected_objects.add(label)
                            bounding_boxes.append({
                                "label": label, 
                                "x": x, "y": y, "width": w, "height": h,
                                "confidence": float(box.conf)
                            })

                    print(f"Detected objects in frame {frame_count}: {list(detected_objects)}")

                except Exception as e:
                    print(f"Error detecting objects in video frame {frame_count}: {e}")

            frame_count += 1

        cap.release()
        return list(detected_objects), bounding_boxes

    except Exception as e:
        print(f"Error processing video {file_path}: {e}")
        return [], []






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
    bounding_boxes = []
    classification = {}
    pose = {}
    gesture = "No gesture detected"
    emotion = "Unknown"
    content_hash = None  # Initialize content hash

    try:
        web_url = construct_web_url(file_path)

        if file_type in image_extensions:
            detected_objects, bounding_boxes = scan_image(file_path) or ([], [])  # ✅ Now extracts bounding boxes
            classification = classify_image(file_path) or {}
            pose_data = estimate_pose(file_path) or {}
            pose = pose_data.get("keypoints", [])
            gesture = pose_data.get("gesture", "No gesture detected")
            emotion = detect_emotion(file_path) or "Unknown"

            combined_content = json.dumps({
                "detected_objects": detected_objects,
                "classification": classification,
                "pose": pose,
                "gesture": gesture,
                "emotion": emotion,
                "bounding_boxes": bounding_boxes  # ✅ Store bounding boxes
            })
            content_hash = hashlib.sha256(combined_content.encode()).hexdigest()

        elif file_type in video_extensions:
            detected_objects, bounding_boxes = scan_video(file_path) or ([], [])  # ✅ Now extracts bounding boxes
            emotion = analyze_emotion_in_video(file_path) or "Neutral"

            combined_content = json.dumps({
                "detected_objects": detected_objects,
                "emotion": emotion,
                "bounding_boxes": bounding_boxes  # ✅ Store bounding boxes
            })
            content_hash = hashlib.sha256(combined_content.encode()).hexdigest()

        # ✅ Update database with detected objects, bounding boxes, and AI results
        sql = """
            UPDATE files
            SET detected_objects = %s, classification = %s, pose = %s, 
                gesture = %s, emotion = %s, content_hash = %s, bounding_boxes = %s, last_scanned = %s
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
                json.dumps(bounding_boxes),  # ✅ Store bounding boxes in JSON format
                datetime.now(),
                file_id
            ))
        db.commit()
        print(f"Updated file ID {file_id} with AI results and bounding boxes.")

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
        max_frames = 300  # Limit the number of frames analyzed

        while cap.isOpened() and frame_count < max_frames:
            ret, frame = cap.read()
            if not ret:
                break

            if frame_count % frame_interval == 0:
                try:
                    print(f"Analyzing frame {frame_count} for emotion.")
                    result = DeepFace.analyze(frame, actions=['emotion'], enforce_detection=False)
                    detected_emotion = result[0]['dominant_emotion']
                    emotions_detected.append(detected_emotion)
                    print(f"Emotion detected in frame {frame_count}: {detected_emotion}")
                except Exception as e:
                    print(f"Error analyzing emotion in frame {frame_count}: {e}")
            frame_count += 1

        cap.release()
        # Return the most frequent emotion detected
        dominant_emotion = max(set(emotions_detected), key=emotions_detected.count, default="Neutral")
        print(f"Dominant emotion for video {video_path}: {dominant_emotion}")
        return dominant_emotion
    except Exception as e:
        print(f"Error processing video emotion: {e}")
        return "Neutral"





# Main execution
if __name__ == "__main__":
    try:
        check_local_folder()
        ensure_required_columns()
        scan_and_process_directory(LOCAL_FOLDER_PATH)
    finally:
        db.close()
        print("Database connection closed.")
