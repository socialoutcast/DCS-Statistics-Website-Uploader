import os
import time
import ftplib
import configparser
from datetime import datetime

CONFIG_FILE = 'config.ini'
FILELIST_FILE = 'filelist.txt'
MAX_RETRIES = 10
RETRY_INTERVAL = 120  # seconds
LOG_FILE = 'upload_log.txt'
CHUNK_SIZE = 8192  # 8 KB

def log_message(message):
    timestamped = f"[{datetime.now()}] {message}"
    print(timestamped)
    with open(LOG_FILE, 'a') as log_file:
        log_file.write(timestamped + '\n')

def load_config():
    config = configparser.ConfigParser()
    config.read(CONFIG_FILE)
    return config

def read_file_list(enabled_files):
    with open(FILELIST_FILE, 'r') as f:
        return [line.strip() for line in f if enabled_files.get(line.strip(), 'false').lower() == 'true']

def upload_file_with_progress(ftp, local_path, remote_path):
    total_size = os.path.getsize(local_path)
    uploaded = 0

    def handle(block):
        nonlocal uploaded
        uploaded += len(block)
        percent = (uploaded / total_size) * 100
        print(f"Uploading {remote_path}: {percent:.2f}% complete", end='\r')

    with open(local_path, 'rb') as f:
        ftp.storbinary(f'STOR {remote_path}', f, blocksize=CHUNK_SIZE, callback=handle)
    log_message(f"Uploading {remote_path}: 100.00% complete")

def connect_ftp(config):
    ftp = ftplib.FTP(config['FTP']['host'])
    ftp.login(config['FTP']['user'], config['FTP']['password'])
    ftp.cwd(config['Paths']['remote_folder'])
    return ftp

def try_upload(file, local_folder, config):
    local_path = os.path.join(local_folder, file)
    for attempt in range(1, MAX_RETRIES + 1):
        ftp = None
        try:
            ftp = connect_ftp(config)
            ftp.nlst()
            upload_file_with_progress(ftp, local_path, file)
            log_message(f"Upload successful: {file}")
            return True
        except Exception as e:
            log_message(f"Upload failed (Attempt {attempt}/10): {file}, Error: {e}")
            time.sleep(RETRY_INTERVAL)
        finally:
            if ftp:
                try:
                    ftp.quit()
                except Exception:
                    pass
                try:
                    ftp.close()
                except Exception:
                    pass
    return False

def main():
    config = load_config()
    display_countdown = config['Upload'].getboolean('display_countdown', True)
    log_message("Sky Pirates Stats Uploader started.")
    while True:
        local_folder = config['Paths']['local_folder']
        throttle = int(config['Upload'].get('throttle_seconds', 1))
        enabled_files = config['Files']
        files_to_upload = read_file_list(enabled_files)

        for file in files_to_upload:
            try_upload(file, local_folder, config)
            time.sleep(throttle)

        if display_countdown:
            for remaining in range(3600, 0, -1):
                mins, secs = divmod(remaining, 60)
                print(f"Next upload in: {mins:02d}:{secs:02d}", end='\r')
                time.sleep(1)
            print()
        else:
            time.sleep(3600)

if __name__ == '__main__':
    main()
