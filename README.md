# Silentmode Remote File Retrieval System

This project is a Laravel 10 application designed to implement a robust, on-demand file transfer system where a central cloud server can retrieve a 100MB file from multiple isolated, NAT-restricted on-premise clients.

## Prerequisites
- **Git**: To clone the repository.
- **Docker & Docker Desktop**: For running the Laravel Sail environment (MySQL, Redis, etc.).
- **PHP 8.1+ & Composer**: (Optional, if you wish to run outside Docker).

---

## 1. Cloning the Repository
Open your terminal and run:
```bash
git clone https://github.com/amirsyafiqezrin/silentmode.git
cd silentmode
```

---

## 2. Setup and Configuration
This project is configured with Docker using Laravel Sail.

### Environment Setup
Create your `.env` file from the example:
```bash
cp .env.example .env
```

Ensure the following variables are set in your `.env` for the **central server**:
```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=silentmode
DB_USERNAME=sail
DB_PASSWORD=password

NODE_ROLE=server
CENTRAL_SERVER_URL=http://localhost
CLIENT_API_TOKEN=test-token-123
```

Install the PHP dependencies using a small Docker container:
```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php8.2-composer:latest \
    composer install --ignore-platform-reqs
```

---

## 3. Running the Application (Docker)

Start the Docker containers in the background:
```bash
./vendor/bin/sail up -d
```
*(On Windows, you must have Docker Desktop running and execute this within WSL2 or Git Bash).*

Once the containers are running, generate the application key and run migrations:
```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

---

## 4. Testing & Reviewing

### Seed a Test Client
First, let's create a client in the database with the token we set in `.env`.
```bash
./vendor/bin/sail artisan tinker --execute="App\Models\Client::create(['name' => 'Test Client', 'api_token' => 'test-token-123']);"
```

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
The client runs as an artisan command. Open a separate terminal window/tab to run the polling daemon:
```bash
# If running via Sail
./vendor/bin/sail artisan edge:poll

# Or if running locally (ensure NODE_ROLE=client is set)
NODE_ROLE="client" php artisan edge:poll
```
You should see output indicating it is connected and waiting for jobs.

### Trigger the Download from the Server
In another terminal, run the server CLI command to trigger the download from `client_id = 1`:
```bash
./vendor/bin/sail artisan server:trigger 1
```

### Observe the Results
1. Watch the edge client terminal. You will see it pick up the pending job and stream the 100MB file to the server.
2. Check the `storage/app/downloads/` directory on the server to verify the file was successfully received.

> **Note on PHP Upload Limits:** For the server to accept a 100MB multipart file upload, the PHP container's `php.ini` must have `upload_max_filesize` and `post_max_size` configured to allow at least 120M.
