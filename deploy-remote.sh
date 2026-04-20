#!/bin/bash
# ============================================================
# LiveMapEvents — Deploy from your Mac to the Ubuntu Server
# ============================================================
# Prerequisites:
#   1. cp server.conf.example server.conf
#   2. Fill in your real values in server.conf
#   3. chmod +x deploy-remote.sh
#   4. ./deploy-remote.sh              (full deploy)
#   5. ./deploy-remote.sh --setup      (first-time server setup)
#   6. ./deploy-remote.sh --status     (check server status)
#   7. ./deploy-remote.sh --logs       (tail server logs)
#   8. ./deploy-remote.sh --ssh        (open SSH session)
# ============================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CONF_FILE="$SCRIPT_DIR/server.local.conf"

# Colors
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
[ "$DB_PASSWORD" = "CHANGE_ME_TO_A_STRONG_PASSWORD" ] && MISSING+=("DB_PASSWORD")

if [ ${#MISSING[@]} -gt 0 ]; then
    error "Missing or default values in server.conf:"
    for m in "${MISSING[@]}"; do
        echo -e "  ${RED}✗${NC} $m"
    done
    echo ""
    warn "Edit server.conf: nano $CONF_FILE"
    exit 1
fi

SSH_CMD="ssh -i $SERVER_SSH_KEY $SERVER_USER@$SERVER_IP"
SCP_CMD="scp -i $SERVER_SSH_KEY"

# ── Generate .env.docker from server.conf ──
generate_env() {
    cat > "$SCRIPT_DIR/.env.docker" << ENVEOF
APP_NAME=LiveMapEvents
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}
APP_PORT=${APP_PORT:-8080}
FRONTEND_PORT=${FRONTEND_PORT:-3000}

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
DB_SSLMODE=${DB_SSLMODE:-require}

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-your-google-client-id-here}

ULTRAMSG_URL=${ULTRAMSG_URL:-https://api.ultramsg.com}
ULTRAMSG_INSTANCE_ID=${ULTRAMSG_INSTANCE_ID:-your-instance-id}
ULTRAMSG_TOKEN=${ULTRAMSG_TOKEN:-your-ultramsg-token}

MAIL_MAILER=log
ENVEOF
    log "Generated .env.docker from server.conf"
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
    $SSH_CMD "echo 'SSH connection successful!'" || {
        error "Cannot connect to $SERVER_IP"
        echo ""
        warn "Make sure:"
        echo "  1. The server is running"
        echo "  2. SSH key is authorized: ssh-copy-id -i ${SERVER_SSH_KEY}.pub $SERVER_USER@$SERVER_IP"
        echo "  3. The user '$SERVER_USER' exists on the server"
        exit 1
    }

    # Run setup on server
    $SSH_CMD << 'SETUP'
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

        # Firewall
        if command -v ufw &> /dev/null; then
            echo "[LiveMap] Configuring firewall..."
            sudo ufw allow 22/tcp
            sudo ufw allow 80/tcp
            sudo ufw allow 443/tcp
            sudo ufw allow 3000/tcp
            sudo ufw allow 8080/tcp
            sudo ufw --force enable
        fi

        echo "[LiveMap] Server setup complete!"
SETUP

    # Create deploy directory
    $SSH_CMD "sudo mkdir -p $SERVER_DEPLOY_PATH && sudo chown $SERVER_USER:$SERVER_USER $SERVER_DEPLOY_PATH"
    log "Created $SERVER_DEPLOY_PATH on server"

    warn "IMPORTANT: Log out of the server and back in for Docker group to take effect."
    info "Then run: ./deploy-remote.sh"
}

# ── Deploy to server ──
deploy() {
    log "Deploying to $SERVER_USER@$SERVER_IP..."
    echo ""
    info "Server:    $SERVER_IP"
    info "Hostname:  ${SERVER_HOSTNAME:-$SERVER_IP}"
    info "Path:      $SERVER_DEPLOY_PATH"
    info "Backend:   port ${APP_PORT:-8080}"
    info "Frontend:  port ${FRONTEND_PORT:-3000}"
    echo ""

    # Generate .env.docker
    generate_env

    # Upload files
    log "Uploading project files..."
    $SCP_CMD "$SCRIPT_DIR/docker-compose.yml" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"
    $SCP_CMD "$SCRIPT_DIR/.env.docker" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"
    $SCP_CMD "$SCRIPT_DIR/deploy.sh" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/"

    log "Compressing and uploading backend..."
    tar czf /tmp/livemap-backend.tar.gz -C "$SCRIPT_DIR" \
        --exclude='backend/.git' \
        --exclude='backend/node_modules' \
        --exclude='backend/storage/logs/*.log' \
        --exclude='backend/storage/framework/cache' \
        --exclude='backend/storage/framework/sessions' \
        --exclude='backend/storage/framework/views' \
        backend/
    $SCP_CMD /tmp/livemap-backend.tar.gz "$SERVER_USER@$SERVER_IP:/tmp/"
    $SSH_CMD "tar xzf /tmp/livemap-backend.tar.gz -C $SERVER_DEPLOY_PATH/ && rm /tmp/livemap-backend.tar.gz"
    rm -f /tmp/livemap-backend.tar.gz
    log "Backend uploaded successfully."

    log "Compressing and uploading frontend (Flutter web)..."
    tar czf /tmp/livemap-mobile.tar.gz -C "$SCRIPT_DIR" \
        --exclude='mobile/.git' \
        --exclude='mobile/.dart_tool' \
        --exclude='mobile/build' \
        --exclude='mobile/android/.gradle' \
        --exclude='mobile/ios/Pods' \
        --exclude='mobile/ios/.symlinks' \
        --exclude='mobile/.flutter-plugins-dependencies' \
        mobile/
    $SCP_CMD /tmp/livemap-mobile.tar.gz "$SERVER_USER@$SERVER_IP:/tmp/"
    $SSH_CMD "tar xzf /tmp/livemap-mobile.tar.gz -C $SERVER_DEPLOY_PATH/ && rm /tmp/livemap-mobile.tar.gz"
    rm -f /tmp/livemap-mobile.tar.gz
    log "Frontend uploaded successfully."

    # Deploy on server
    log "Building and starting containers on server..."
    $SSH_CMD << DEPLOY
        set -e
        cd $SERVER_DEPLOY_PATH
        chmod +x deploy.sh 2>/dev/null || true

        echo "[LiveMap] Building Docker images (backend + frontend)..."
        docker compose build app frontend

        echo "[LiveMap] Stopping existing containers..."
        docker compose down --timeout 30 2>/dev/null || true

        echo "[LiveMap] Starting all containers..."
        docker compose up -d

        echo "[LiveMap] Waiting for backend health check..."
        for i in \$(seq 1 30); do
            if docker compose exec -T app curl -sf http://localhost/health > /dev/null 2>&1; then
                echo "[LiveMap] Backend health check passed!"
                break
            fi
            if [ "\$i" -eq 30 ]; then
                echo "[LiveMap] ERROR: Backend health check failed!"
                docker compose logs app --tail=30
                exit 1
            fi
            echo "[LiveMap] Waiting... (\$i/30)"
            sleep 5
        done

        echo "[LiveMap] Waiting for frontend health check..."
        for i in \$(seq 1 15); do
            if docker compose exec -T frontend curl -sf http://localhost/health > /dev/null 2>&1; then
                echo "[LiveMap] Frontend health check passed!"
                break
            fi
            if [ "\$i" -eq 15 ]; then
                echo "[LiveMap] WARNING: Frontend health check failed. Check: ./deploy-remote.sh --logs frontend"
            fi
            echo "[LiveMap] Waiting... (\$i/15)"
            sleep 3
        done

        docker image prune -f > /dev/null 2>&1
        echo ""
        echo "[LiveMap] Deployment successful!"
        docker compose ps
DEPLOY

    echo ""
    log "Deployment complete!"
    echo ""
    info "Your app is live at:"
    info "  Backend API:  http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}"
    info "  Frontend:     http://${SERVER_HOSTNAME:-$SERVER_IP}:${FRONTEND_PORT:-3000}"
    info "  Health (API): http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}/health"
    info "  Health (Web): http://${SERVER_HOSTNAME:-$SERVER_IP}:${FRONTEND_PORT:-3000}/health"
    info "  API:          http://${SERVER_HOSTNAME:-$SERVER_IP}:${APP_PORT:-8080}/api/v1/auth/me"
}

# ── Show server status ──
show_status() {
    log "Checking server at $SERVER_IP..."
    $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose ps"
    echo ""

    info "Testing backend health..."
    $SSH_CMD "curl -sf http://localhost:${APP_PORT:-8080}/health && echo ' OK' || echo 'UNREACHABLE'"
    info "Testing frontend health..."
    $SSH_CMD "curl -sf http://localhost:${FRONTEND_PORT:-3000}/health && echo ' OK' || echo 'UNREACHABLE'"
}

# ── Main ──
case "${1:-}" in
    --setup)
        setup_server
        ;;
    --status)
        show_status
        ;;
    --logs)
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose logs -f --tail=100 ${2:-app}"
        ;;
    --ssh)
        log "Connecting to $SERVER_USER@$SERVER_IP..."
        $SSH_CMD
        ;;
    --down)
        log "Stopping containers on server..."
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose down"
        log "All containers stopped."
        ;;
    --restart)
        log "Restarting containers on server..."
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose restart"
        show_status
        ;;
    *)
        deploy
        ;;
esac
