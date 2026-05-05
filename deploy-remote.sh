#!/bin/bash
# ============================================================
# LiveMapEvents — Deploy from your Mac to the Ubuntu Server
# ============================================================
# Prerequisites:
#   1. cp server.conf.example server.conf
#   2. Fill in your real values in server.conf
#   3. chmod +x deploy-remote.sh
#   4. ./deploy-remote.sh              (full deploy)
#   5. ./deploy-remote.sh --setup       (first-time server setup: docker, ufw)
#   6. ./deploy-remote.sh --setup-redis (cleanup any orphan Redis containers, then redeploy)
#   7. ./deploy-remote.sh --status      (check server + Redis status)
#   8. ./deploy-remote.sh --logs        (tail server logs)
#   9. ./deploy-remote.sh --ssh         (open SSH session)
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

# ── Auto-generate REDIS_PASSWORD if empty, then write it back to server.local.conf ──
if [ -z "$REDIS_PASSWORD" ] || [ "$REDIS_PASSWORD" = "null" ]; then
    GENERATED_REDIS_PASSWORD="$(openssl rand -base64 32 | tr -d '/+=' | cut -c1-32)"
    if grep -qE '^REDIS_PASSWORD=' "$CONF_FILE"; then
        # Use a temp file because sed -i differs between GNU and BSD/macOS
        awk -v pw="$GENERATED_REDIS_PASSWORD" 'BEGIN{FS=OFS="="} /^REDIS_PASSWORD=/{print "REDIS_PASSWORD=" pw; next} {print}' "$CONF_FILE" > "$CONF_FILE.tmp" && mv "$CONF_FILE.tmp" "$CONF_FILE"
    else
        echo "REDIS_PASSWORD=$GENERATED_REDIS_PASSWORD" >> "$CONF_FILE"
    fi
    REDIS_PASSWORD="$GENERATED_REDIS_PASSWORD"
    log "Generated a new REDIS_PASSWORD and saved it to server.local.conf"
fi

# ── Validate required fields ──
MISSING=()
[ "$SERVER_IP" = "0.0.0.0" ] || [ -z "$SERVER_IP" ] && MISSING+=("SERVER_IP")
[ -z "$SERVER_USER" ] && MISSING+=("SERVER_USER")
[ -z "$SERVER_SSH_KEY" ] && MISSING+=("SERVER_SSH_KEY")
[ -z "$SERVER_DEPLOY_PATH" ] && MISSING+=("SERVER_DEPLOY_PATH")
[ "$DB_PASSWORD" = "CHANGE_ME_TO_A_STRONG_PASSWORD" ] && MISSING+=("DB_PASSWORD")
[ -z "$REDIS_HOST" ] && MISSING+=("REDIS_HOST")
[ -z "$REDIS_PORT" ] && MISSING+=("REDIS_PORT")
[ -z "$REDIS_PASSWORD" ] && MISSING+=("REDIS_PASSWORD")

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
APP_URL=https://api.${SERVER_HOSTNAME:-$SERVER_IP}
# APP_PORT / FRONTEND_PORT are NOT published to the host anymore —
# Caddy fronts everything on 80/443. These are kept only for the
# rare case you want to re-publish a container directly.
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

REDIS_CLIENT=${REDIS_CLIENT:-predis}
REDIS_HOST=${REDIS_HOST}
REDIS_PORT=${REDIS_PORT:-6379}
REDIS_PASSWORD=${REDIS_PASSWORD}
REDIS_DB=${REDIS_DB:-0}
REDIS_CACHE_DB=${REDIS_CACHE_DB:-1}

SESSION_DRIVER=${SESSION_DRIVER:-redis}
CACHE_STORE=${CACHE_STORE:-redis}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-redis}

GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID:-your-google-client-id-here}

OTP_DRIVER=${OTP_DRIVER:-ultramsg}
OTP_TTL=${OTP_TTL:-300}
OTP_MAX_ATTEMPTS=${OTP_MAX_ATTEMPTS:-3}
OTP_RESEND_COOLDOWN=${OTP_RESEND_COOLDOWN:-60}
OTP_FAKE=${OTP_FAKE:-false}
OTP_FAKE_CODE=${OTP_FAKE_CODE:-000000}

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
            # 3000 / 8080 are NOT publicly exposed anymore — Caddy fronts
            # both services on 80/443. Drop any old allow rules left from
            # earlier deploys (silently ignore if they were never added).
            sudo ufw delete allow 3000/tcp 2>/dev/null || true
            sudo ufw delete allow 8080/tcp 2>/dev/null || true
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

    # Caddy config (reverse proxy + auto HTTPS)
    log "Uploading Caddyfile..."
    $SSH_CMD "mkdir -p $SERVER_DEPLOY_PATH/caddy"
    $SCP_CMD "$SCRIPT_DIR/caddy/Caddyfile" "$SERVER_USER@$SERVER_IP:$SERVER_DEPLOY_PATH/caddy/Caddyfile"

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
    # Note: --env-file .env.docker tells docker compose to use that file for BOTH
    # the app's env_file substitution AND \${VAR} interpolation in compose itself
    # (e.g. \${REDIS_PASSWORD} in the redis service's command).
    log "Building and starting containers on server..."
    $SSH_CMD << DEPLOY
        set -e
        cd $SERVER_DEPLOY_PATH
        chmod +x deploy.sh 2>/dev/null || true

        # Stop any standalone redis container that's hogging port 6379
        if docker ps --format '{{.Names}} {{.Image}}' | grep -E '^[^ ]+ redis(:|$)' | grep -v livemap-redis > /dev/null; then
            echo "[LiveMap] Found a standalone Redis container; stopping it..."
            docker ps --format '{{.ID}} {{.Image}}' | awk '/redis/ && \$0 !~ /livemap-redis/ {print \$1}' | xargs -r docker stop
            docker ps -a --format '{{.ID}} {{.Image}}' | awk '/redis/ && \$0 !~ /livemap-redis/ {print \$1}' | xargs -r docker rm
        fi

        echo "[LiveMap] Building Docker images (backend + frontend)..."
        docker compose --env-file .env.docker build app frontend

        echo "[LiveMap] Stopping existing containers..."
        docker compose --env-file .env.docker down --timeout 30 2>/dev/null || true

        echo "[LiveMap] Starting all containers..."
        docker compose --env-file .env.docker up -d

        echo "[LiveMap] Waiting for backend health check..."
        for i in \$(seq 1 30); do
            if docker compose --env-file .env.docker exec -T app curl -sf http://localhost/health > /dev/null 2>&1; then
                echo "[LiveMap] Backend health check passed!"
                break
            fi
            if [ "\$i" -eq 30 ]; then
                echo "[LiveMap] ERROR: Backend health check failed!"
                docker compose --env-file .env.docker logs app --tail=30
                exit 1
            fi
            echo "[LiveMap] Waiting... (\$i/30)"
            sleep 5
        done

        echo "[LiveMap] Waiting for frontend health check..."
        for i in \$(seq 1 15); do
            if docker compose --env-file .env.docker exec -T frontend curl -sf http://localhost/health > /dev/null 2>&1; then
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
        docker compose --env-file .env.docker ps
DEPLOY

    echo ""
    log "Deployment complete!"
    echo ""
    info "Your app is live at:"
    info "  Frontend:     https://${SERVER_HOSTNAME:-$SERVER_IP}"
    info "  Backend API:  https://api.${SERVER_HOSTNAME:-$SERVER_IP}"
    info "  Swagger UI:   https://api.${SERVER_HOSTNAME:-$SERVER_IP}/api/documentation"
    info "  Health (API): https://api.${SERVER_HOSTNAME:-$SERVER_IP}/health"
    info "  Health (Web): https://${SERVER_HOSTNAME:-$SERVER_IP}/health"
    echo ""
    warn "First boot of Caddy may take 30-60s while Let's Encrypt issues the cert."
    warn "If https:// fails, run: ./deploy-remote.sh --logs caddy"
}

# ── Stop & remove any standalone Redis container that might conflict with docker-compose's redis ──
setup_redis() {
    log "Cleaning up any standalone Redis container on $SERVER_IP..."
    info "docker-compose now manages Redis itself (service 'redis'), so any other"
    info "Redis container or host service holding port 6379 needs to go away."
    echo ""

    $SSH_CMD bash -s << 'REDISCLEAN'
        set -e

        echo "[LiveMap] Looking for Redis containers..."
        # List anything that is a Redis image but is NOT our compose-managed livemap-redis
        ORPHANS=$(docker ps -a --format '{{.ID}} {{.Names}} {{.Image}}' | awk '/redis/ && $2 !~ /^livemap-redis$/ {print $1}')
        if [ -z "$ORPHANS" ]; then
            echo "[LiveMap] No orphan Redis containers found."
        else
            echo "[LiveMap] Found orphan Redis containers — stopping and removing:"
            docker ps -a --format '  - {{.Names}} ({{.Image}}) {{.Status}}' | grep -i redis | grep -v livemap-redis || true
            echo "$ORPHANS" | xargs -r docker stop
            echo "$ORPHANS" | xargs -r docker rm
            echo "[LiveMap] Orphan Redis containers removed."
        fi

        # If a native redis-server is running on the host, warn (don't auto-stop — user might want it)
        if pgrep -x redis-server > /dev/null 2>&1 && ! docker top livemap-redis 2>/dev/null | grep -q redis-server; then
            echo "[LiveMap] WARNING: A redis-server process is running on the host that is NOT in a Docker container."
            echo "[LiveMap]          It would block port 6379 if you publish the compose redis port."
            echo "[LiveMap]          To stop it: sudo systemctl stop redis-server && sudo systemctl disable redis-server"
            echo "[LiveMap]          (Our compose redis uses 'expose' not 'ports', so this only matters if you change that.)"
        fi

        echo "[LiveMap] Redis cleanup done."
REDISCLEAN

    log "Cleanup complete."
    info "Next: ./deploy-remote.sh   (will start the compose-managed Redis with the password from server.local.conf)"
}

# ── Show server status ──
show_status() {
    log "Checking server at $SERVER_IP..."
    $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker ps"
    echo ""

    info "Testing backend health (via Caddy)..."
    $SSH_CMD "curl -sfk https://api.${SERVER_HOSTNAME:-$SERVER_IP}/health && echo ' OK' || echo 'UNREACHABLE'"
    info "Testing frontend health (via Caddy)..."
    $SSH_CMD "curl -sfk https://${SERVER_HOSTNAME:-$SERVER_IP}/health && echo ' OK' || echo 'UNREACHABLE'"
    info "Testing Redis container PING..."
    $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker exec -T redis redis-cli -a '$REDIS_PASSWORD' --no-auth-warning ping 2>/dev/null || echo 'UNREACHABLE'"
    info "Testing Redis from inside app container (Laravel/Predis)..."
    $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker exec -T app php artisan tinker --execute=\"echo Redis::connection()->ping();\" 2>&1 | tail -n 1 || echo 'container not running'"
}

# ── Main ──
case "${1:-}" in
    --setup)
        setup_server
        ;;
    --setup-redis)
        setup_redis
        ;;
    --status)
        show_status
        ;;
    --logs)
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker logs -f --tail=100 ${2:-app}"
        ;;
    --ssh)
        log "Connecting to $SERVER_USER@$SERVER_IP..."
        $SSH_CMD
        ;;
    --down)
        log "Stopping containers on server..."
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker down"
        log "All containers stopped."
        ;;
    --restart)
        log "Restarting containers on server..."
        $SSH_CMD "cd $SERVER_DEPLOY_PATH && docker compose --env-file .env.docker restart"
        show_status
        ;;
    *)
        deploy
        ;;
esac
