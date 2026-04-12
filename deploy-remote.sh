#!/bin/bash
# ============================================================
# LiveMapEvents — Deploy to Ubuntu Server
# ============================================================
# Works from Windows (Git Bash / WSL), macOS, and Linux.
#
# Prerequisites:
#   1. cp server.conf.example server.conf
#   2. Fill in your real values in server.conf
#   3. chmod +x deploy-remote.sh
#
# Commands:
#   ./deploy-remote.sh              Full deploy (build + restart)
#   ./deploy-remote.sh --setup      First-time server setup (Docker, firewall)
#   ./deploy-remote.sh --fresh      Deploy with a clean database (wipes volumes)
#   ./deploy-remote.sh --status     Check container health
#   ./deploy-remote.sh --logs       Tail all logs (or: --logs db, --logs redis)
#   ./deploy-remote.sh --ssh        Open interactive SSH session
#   ./deploy-remote.sh --restart    Restart containers without rebuilding
#   ./deploy-remote.sh --down       Stop all containers
#   ./deploy-remote.sh --env        Show the generated .env.docker (for debugging)
# ============================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CONF_FILE="$SCRIPT_DIR/server.conf"

# ── Colors ──
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

log()   { echo -e "${GREEN}[LiveMap]${NC} $1"; }
warn()  { echo -e "${YELLOW}[LiveMap]${NC} $1"; }
error() { echo -e "${RED}[LiveMap]${NC} $1"; }
info()  { echo -e "${CYAN}[LiveMap]${NC} $1"; }

# ── Load config ──
if [ ! -f "$CONF_FILE" ]; then
    error "server.conf not found!"
    echo ""
    warn "Create it by running:"
    echo "  cp server.conf.example server.conf"
    echo "  nano server.conf   (fill in your server details)"
    exit 1
fi

source "$CONF_FILE"

# ── Validate required fields ──
MISSING=()
[ "$SERVER_IP" = "0.0.0.0" ] || [ -z "$SERVER_IP" ] && MISSING+=("SERVER_IP")
[ -z "$SERVER_USER" ] && MISSING+=("SERVER_USER")
[ -z "$SERVER_SSH_KEY" ] && MISSING+=("SERVER_SSH_KEY")
[ -z "$SERVER_DEPLOY_PATH" ] && MISSING+=("SERVER_DEPLOY_PATH")
[ -z "$DB_PASSWORD" ] || [ "$DB_PASSWORD" = "CHANGE_ME_TO_A_STRONG_PASSWORD" ] && MISSING+=("DB_PASSWORD")
[ -z "$DB_DATABASE" ] && MISSING+=("DB_DATABASE")
[ -z "$DB_USERNAME" ] && MISSING+=("DB_USERNAME")

if [ ${#MISSING[@]} -gt 0 ]; then
    error "Missing or default values in server.conf:"
    for m in "${MISSING[@]}"; do
        echo -e "  ${RED}✗${NC} $m"
    done
    echo ""
    warn "Edit server.conf: nano $CONF_FILE"
    exit 1
fi

# ── SSH/SCP commands (arrays handle paths with spaces, e.g. Windows usernames) ──
SSH_CMD=(ssh -i "$SERVER_SSH_KEY" "$SERVER_USER@$SERVER_IP")
SCP_CMD=(scp -i "$SERVER_SSH_KEY")

# ── Generate .env.docker from server.conf ──
# This file is loaded by docker-compose (env_file: .env.docker) and passed
# as environment variables into all containers. The entrypoint.sh inside the
# app container then creates Laravel's .env from these environment variables.
#
# DB_HOST, REDIS_HOST, etc. use Docker Compose service names (db, redis)
# because all containers share the same Docker network (livemap).
generate_env() {
    local ENV_FILE="$SCRIPT_DIR/.env.docker"

    cat > "$ENV_FILE" << ENVEOF
# ── App ──
APP_NAME=LiveMapEvents
APP_ENV=${APP_ENV:-production}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}
APP_PORT=${APP_PORT:-8080}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

BCRYPT_ROUNDS=12

# ── Logging ──
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=${LOG_LEVEL:-warning}

# ── Database (PostgreSQL + PostGIS) ──
# DB_HOST=db refers to the "db" service in docker-compose.yml.
# These credentials MUST match POSTGRES_DB / POSTGRES_USER / POSTGRES_PASSWORD
# in docker-compose.yml (they are passed through automatically).
# IMPORTANT: If you change these after the first deploy, you must run:
#   ./deploy-remote.sh --fresh
# because PostgreSQL only creates the user/database on first initialization.
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

# ── Redis ──
# REDIS_HOST=redis refers to the "redis" service in docker-compose.yml.
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ── Sessions / Cache / Queue (all backed by Redis) ──
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# ── Google OAuth ──
GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-}

# ── UltraMsg (WhatsApp OTP) ──
ULTRAMSG_URL=${ULTRAMSG_URL:-https://api.ultramsg.com}
ULTRAMSG_INSTANCE_ID=${ULTRAMSG_INSTANCE_ID:-}
ULTRAMSG_TOKEN=${ULTRAMSG_TOKEN:-}

# ── Mail ──
MAIL_MAILER=${MAIL_MAILER:-log}
ENVEOF

    log "Generated .env.docker"

    # Show a summary for verification
    info "  DB_DATABASE=$DB_DATABASE"
    info "  DB_USERNAME=$DB_USERNAME"
    info "  DB_HOST=db (Docker internal)"
    info "  APP_ENV=${APP_ENV:-production}"
    info "  APP_PORT=${APP_PORT:-8080}"
}

# ── First-time server setup ──
setup_server() {
    log "Setting up server at $SERVER_IP..."
    echo ""
    info "Server:  $SERVER_USER@$SERVER_IP"
    info "Path:    $SERVER_DEPLOY_PATH"
    info "SSH key: $SERVER_SSH_KEY"
    echo ""

    # Test SSH connection
    log "Testing SSH connection..."
    "${SSH_CMD[@]}" "echo 'SSH connection successful!'" || {
        error "Cannot connect to $SERVER_IP"
        echo ""
        warn "Make sure:"
        echo "  1. The server is running"
        echo "  2. SSH key is authorized: ssh-copy-id -i \"${SERVER_SSH_KEY}.pub\" $SERVER_USER@$SERVER_IP"
        echo "  3. The user '$SERVER_USER' exists on the server"
        exit 1
    }

    # Run setup on server
    "${SSH_CMD[@]}" << 'SETUP'
        set -e
        echo "[LiveMap] Installing Docker..."

        if ! command -v docker &> /dev/null; then
            sudo apt-get update
            sudo apt-get install -y ca-certificates curl gnupg
            sudo install -m 0755 -d /etc/apt/keyrings
            curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
            sudo chmod a+r /etc/apt/keyrings/docker.gpg
            echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo $VERSION_CODENAME) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
            sudo apt-get update
            sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
            sudo usermod -aG docker $USER
            echo "[LiveMap] Docker installed!"
        else
            echo "[LiveMap] Docker already installed: $(docker --version)"
        fi

        # Firewall — allow SSH, HTTP, HTTPS, and the app port
        if command -v ufw &> /dev/null; then
            echo "[LiveMap] Configuring firewall..."
            sudo ufw allow 22/tcp
            sudo ufw allow 80/tcp
            sudo ufw allow 443/tcp
            sudo ufw allow 8080/tcp
            sudo ufw --force enable
            echo "[LiveMap] Firewall configured. Open ports: 22, 80, 443, 8080"
        fi

        echo "[LiveMap] Server setup complete!"
SETUP

    # Create deploy directory
    "${SSH_CMD[@]}" "sudo mkdir -p $SERVER_DEPLOY_PATH && sudo chown $SERVER_USER:$SERVER_USER $SERVER_DEPLOY_PATH"
    log "Created $SERVER_DEPLOY_PATH on server"

    echo ""
    warn "IMPORTANT: Log out of the server and back in for Docker group to take effect."
    info "Then run: ./deploy-remote.sh"
}

# ── Deploy to server ──
deploy() {
    local FRESH="${1:-false}"

    log "Deploying to $SERVER_USER@$SERVER_IP..."
    echo ""
    info "Server:    $SERVER_IP"
    info "Hostname:  ${SERVER_HOSTNAME:-$SERVER_IP}"
    info "Path:      $SERVER_DEPLOY_PATH"
    info "Port:      ${APP_PORT:-8080}"
    if [ "$FRESH" = "true" ]; then
        warn "Mode:      FRESH (database will be wiped and recreated)"
    fi
    echo ""

    # Generate .env.docker
    generate_env
    echo ""

    # Upload files
    log "Uploading project files..."
    "${SCP_CMD[@]}" "$SCRIPT_DIR/docker-compose.yml" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"
    "${SCP_CMD[@]}" "$SCRIPT_DIR/.env.docker" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"
    "${SCP_CMD[@]}" "$SCRIPT_DIR/deploy.sh" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/" 2>/dev/null || true
    "${SCP_CMD[@]}" -r "$SCRIPT_DIR/backend" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"

    # Determine the down command based on fresh flag
    local DOWN_CMD="docker compose down --timeout 30 2>/dev/null || true"
    if [ "$FRESH" = "true" ]; then
        DOWN_CMD="docker compose down -v --timeout 30 2>/dev/null || true"
    fi

    # Deploy on server
    log "Building and starting containers on server..."
    "${SSH_CMD[@]}" << DEPLOY
        set -e
        cd $SERVER_DEPLOY_PATH

        echo "[LiveMap] Building Docker image..."
        docker compose build app

        echo "[LiveMap] Stopping existing containers..."
        $DOWN_CMD

        echo "[LiveMap] Starting containers..."
        docker compose up -d

        echo "[LiveMap] Waiting for health check..."
        for i in \$(seq 1 30); do
            if docker compose exec -T app curl -sf http://localhost/health > /dev/null 2>&1; then
                echo "[LiveMap] Health check passed!"
                break
            fi
            if [ "\$i" -eq 30 ]; then
                echo "[LiveMap] ERROR: Health check failed after 30 attempts!"
                echo ""
                echo "[LiveMap] Last 30 lines of app logs:"
                docker compose logs app --tail=30
                exit 1
            fi
            echo "[LiveMap] Waiting... (\$i/30)"
            sleep 5
        done

        # Verify database connection
        echo "[LiveMap] Verifying database..."
        docker compose exec -T app php artisan migrate:status > /dev/null 2>&1 && \
            echo "[LiveMap] Database OK — migrations applied." || \
            echo "[LiveMap] WARNING: Database check failed. Run: ./deploy-remote.sh --logs app"

        # Clean up old images
        docker image prune -f > /dev/null 2>&1

        echo ""
        echo "[LiveMap] Deployment successful!"
        docker compose ps
DEPLOY

    echo ""
    log "Deployment complete!"
    echo ""
    info "Your app is live at:"
    info "  App:     http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}"
    info "  Health:  http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}/health"
    info "  API:     http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}/api/v1/auth/me"
    echo ""
    info "Useful commands:"
    info "  ./deploy-remote.sh --status     Check container health"
    info "  ./deploy-remote.sh --logs       View live logs"
    info "  ./deploy-remote.sh --ssh        SSH into server"
}

# ── Show server status ──
show_status() {
    log "Checking server at $SERVER_IP..."
    "${SSH_CMD[@]}" "cd $SERVER_DEPLOY_PATH && docker compose ps"
    echo ""

    info "Testing health endpoint..."
    "${SSH_CMD[@]}" "curl -sf http://localhost:${APP_PORT:-8080}/health && echo '' || echo 'UNREACHABLE'"
}

# ── Show generated env (for debugging) ──
show_env() {
    generate_env
    echo ""
    log "Full .env.docker contents:"
    echo "──────────────────────────────────────"
    cat "$SCRIPT_DIR/.env.docker"
    echo "──────────────────────────────────────"
}

# ── Main ──
case "${1:-}" in
    --setup)
        setup_server
        ;;
    --fresh)
        warn "This will WIPE the database and all data. Containers will be recreated."
        echo -n "Are you sure? (y/N): "
        read -r CONFIRM
        if [ "$CONFIRM" = "y" ] || [ "$CONFIRM" = "Y" ]; then
            deploy "true"
        else
            log "Cancelled."
        fi
        ;;
    --status)
        show_status
        ;;
    --logs)
        "${SSH_CMD[@]}" "cd $SERVER_DEPLOY_PATH && docker compose logs -f --tail=100 ${2:-}"
        ;;
    --ssh)
        log "Connecting to $SERVER_USER@$SERVER_IP..."
        "${SSH_CMD[@]}"
        ;;
    --down)
        log "Stopping containers on server..."
        "${SSH_CMD[@]}" "cd $SERVER_DEPLOY_PATH && docker compose down"
        log "All containers stopped."
        ;;
    --restart)
        log "Restarting containers on server..."
        "${SSH_CMD[@]}" "cd $SERVER_DEPLOY_PATH && docker compose restart"
        show_status
        ;;
    --env)
        show_env
        ;;
    --help|-h)
        echo "Usage: ./deploy-remote.sh [command]"
        echo ""
        echo "Commands:"
        echo "  (none)      Full deploy (build + restart)"
        echo "  --setup     First-time server setup (Docker, firewall)"
        echo "  --fresh     Deploy with clean database (wipes volumes)"
        echo "  --status    Check container health"
        echo "  --logs      Tail logs (optionally: --logs db, --logs redis)"
        echo "  --ssh       Open interactive SSH session"
        echo "  --restart   Restart without rebuilding"
        echo "  --down      Stop all containers"
        echo "  --env       Show generated .env.docker (debug)"
        echo "  --help      Show this help"
        ;;
    *)
        deploy
        ;;
esac
