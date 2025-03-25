# Greyhound - Hub Admin Documentation

Greyhound is a comprehensive hub admin project that integrates file management with advanced AI capabilities including object detection, emotion analysis, and duplicate detection powered by YOLO AI.

## Table of Contents
- [Overview](#overview)
- [Features](#features)
- [YOLO AI Key Features (Summary)](#yolo-ai-key-features-summary)
- [Installation and Configuration](#installation-and-configuration)
- [Usage](#usage)
- [Additional Notes](#additional-notes)

## Overview
Greyhound provides a robust administrative platform that facilitates file management and leverages cutting-edge AI technology for real-time object detection, emotion recognition, and duplicate file detection. The project is designed to be customizable and scalable for various deployment environments.

## Features
- **File Management:** Efficient organization, storage, and retrieval of files.
- **AI Object Detection:** Real-time object detection using YOLO AI.
- **Emotion Detection:** Analyze emotional expressions in images and videos.
- **Duplicate Detection:** Identify and manage duplicate files.
- **Customizable & Scalable:** Easily configurable to suit your specific requirements.

## YOLO AI Key Features (Summary)
Below is a summary of the key features from the Ultralytics YOLOv11 documentation:
- **High Accuracy & Speed:** Delivers state-of-the-art performance for real-time object detection tasks.
- **Efficient Architecture:** Optimized model design that enables faster inference with lower computational overhead.
- **Scalability:** Supports multi-scale detection and can handle multiple objects seamlessly.
- **Flexible Training:** Offers customizable training parameters and advanced data augmentation techniques.
- **Advanced Post-Processing:** Implements enhancements for improved localization and classification precision.
- **Multi-Task Capability:** Integrates with various AI tasks, making it versatile for diverse applications.

For full details, please visit the [Ultralytics YOLOv11 documentation](https://docs.ultralytics.com/models/yolo11/#key-features).  
*Note: Due to copyright restrictions, the above is a summarized version of the original documentation.*

## Installation and Configuration

1. **Update Credentials:**  
   - Open the `config.php` file and update it with your credentials.

2. **Set Directory Path:**  
   - Change the directory path to your desired location. For example:  
     `/Applications/XAMPP/xamppfiles/htdocs/testcreative`

3. **Set Permissions:**  
   - Ensure the web server has full access permission on your directory by running:  
     ```bash
     chmod -R 775 /Applications/XAMPP/xamppfiles/htdocs/testcreative/
     ```

4. **Python Setup:**  
   - Install Python 3.10.
   - Install required packages using `requirements.txt`.  
     If errors persist, install the Python packages individually.

5. **Sync File Configuration:**  
   - Update the location of `sync_file.php` to point to your Python 3.10 executable location.

## Usage

- **File Management:**  
  Use the provided interface to upload, organize, and manage your files.

- **AI Features:**  
  Leverage the integrated YOLO AI capabilities for:
  - **Object Detection:** Detect and classify objects in images and videos.
  - **Emotion Detection:** Analyze emotional expressions.
  - **Duplicate Detection:** Identify and manage duplicate files.
  
  Configure each AI task as needed within your project settings.

## Additional Notes

- **Pre-Deployment:**  
  Ensure that all configuration settings (credentials, file paths, permissions) are updated and tested before running the application.

- **Troubleshooting:**  
  If you encounter issues:
  - Verify that the web server has the correct permissions.
  - Double-check that Python 3.10 and all necessary packages are installed.
  - Review the paths and configurations in both `config.php` and `sync_file.php`.

- **Further Documentation:**  
  For more detailed information, refer to the official documentation for PHP, Python, and YOLO AI libraries.

---

This README provides a comprehensive guide to setting up and running the Greyhound - Hub Admin project. For any additional details or advanced configurations, please consult the respective official documentation.
