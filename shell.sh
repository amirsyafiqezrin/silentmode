#!/bin/bash
set -e

command=$1

case $command in
  "up")
    echo "Starting Silentmode Docker Environment..."
    docker compose up -d --build
    echo "Containers are up! The init.sh script will handle dependencies and migrations in the background."
    ;;
  "down")
    docker compose down
    ;;
  "poll")
    docker compose exec -e NODE_ROLE=client silentmode php artisan edge:poll
    ;;
  "trigger")
    client_id=${2:-1}
    docker compose exec silentmode php artisan server:trigger $client_id
    ;;
  "artisan")
    shift
    docker compose exec silentmode php artisan "$@"
    ;;
  *)
    echo "====================================="
    echo " Silentmode Helper Script            "
    echo "====================================="
    echo "Usage: sh shell.sh [command]"
    echo ""
    echo "Commands:"
    echo "  up       - Build and start the Docker containers"
    echo "  down     - Stop and remove the Docker containers"
    echo "  poll     - Run the edge client daemon (polls server for jobs)"
    echo "  trigger  - Trigger a download from server (e.g., sh shell.sh trigger 1)"
    echo "  artisan  - Run any php artisan command (e.g., sh shell.sh artisan tinker)"
    echo ""
    ;;
esac
