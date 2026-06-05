# Silentmode Remote File Retrieval System

This project is a Laravel 10 application designed to implement a robust, on-demand file transfer system where a central cloud server can retrieve a 100MB file from multiple isolated, NAT-restricted on-premise clients.

## Prerequisites
- **Git**: To clone the repository.
- **Docker & Docker Desktop**: For running the environment.

---

## 1. Cloning the Repository
Open your terminal and run:
```bash
git clone https://github.com/amirsyafiqezrin/silentmode.git
cd silentmode
```

---

## 2. Quick Setup & Run (Docker)
We have provided an easy setup script that handles installing PHP dependencies, setting up the `.env` file, starting Docker, and seeding the database.

Simply run:
```bash
sh shell.sh
```
*(On Windows, ensure Docker Desktop is running and execute this within WSL2 or Git Bash).*

This script natively uses `docker compose up -d` and seeds the database with a test client automatically.

---

## 3. Testing & Reviewing

### Create the 100MB Dummy File
The edge client looks for a file in your `$HOME` directory named `file_to_download.txt`. Let's create a dummy 100MB file.
**On Linux/macOS/WSL:**
```bash
dd if=/dev/urandom of=~/file_to_download.txt bs=1M count=100
```
**On Windows (PowerShell):**
```powershell
fsutil file createnew $env:USERPROFILE\file_to_download.txt 104857600
```

### Start the Edge Client Daemon
The client runs as an artisan command. Open a separate terminal window/tab to run the polling daemon using docker compose:
```bash
docker compose exec laravel.test php artisan edge:poll
```
You should see output indicating it is connected and waiting for jobs.

### Trigger the Download from the Server
In another terminal, run the server CLI command to trigger the download from `client_id = 1`:
```bash
docker compose exec laravel.test php artisan server:trigger 1
```

### Observe the Results
1. Watch the edge client terminal. You will see it pick up the pending job and stream the 100MB file to the server.
2. Check the `storage/app/downloads/` directory on the server to verify the file was successfully received.

> **Note on PHP Upload Limits:** For the server to accept a 100MB multipart file upload, the PHP container's `php.ini` must have `upload_max_filesize` and `post_max_size` configured to allow at least 120M.
