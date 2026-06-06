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
We use a custom, production-ready `Dockerfile` and `docker-compose.yaml` to ensure maximum reliability. 

We provide a helper script (`shell.sh`) so you never have to type complex docker commands. To build the images and start the system, simply run:
```bash
sh shell.sh up
```
*(On Windows, ensure Docker Desktop is running and execute this within WSL2 or Git Bash).*

**What happens automatically?**
1. Docker builds the custom PHP 8.2 image.
2. The `init.sh` container entrypoint runs automatically inside the container.
3. It installs `composer` dependencies, creates `.env`, generates the app key, and seeds the database with a test client (`test-token-123`).
4. Finally, it starts the local server.

---

## 3. Testing & Reviewing

### Create the 100MB Dummy File
Because the edge client is running *inside* the Docker container, it cannot access your computer's home directory. Therefore, we will create the dummy 100MB file directly in the project root directory (which is synced with the Docker container). 

**On Linux/macOS/WSL:**
```bash
dd if=/dev/urandom of=file_to_download.txt bs=1M count=100
```
**On Windows (PowerShell):**
Run this inside the `silentmode` project directory:
```powershell
fsutil file createnew file_to_download.txt 104857600
```

### Start the Edge Client Daemon
The client runs as an artisan command. Open a separate terminal window/tab to run the polling daemon:
```bash
sh shell.sh poll
```
You should see output indicating it is connected and waiting for jobs.

### Trigger the Download from the Server
In another terminal, run the server CLI command to trigger the download from `client_id = 1`:
```bash
sh shell.sh trigger 1
```

### Observe the Results
1. Watch the edge client terminal. You will see it pick up the pending job and stream the 100MB file to the server.
2. Check the `storage/app/downloads/` directory on the server to verify the file was successfully received.

> **Note on PHP Upload Limits:** For the server to accept a 100MB multipart file upload, the `Dockerfile` automatically configures the PHP container's `php.ini` to set `upload_max_filesize` and `post_max_size` to `120M`.
