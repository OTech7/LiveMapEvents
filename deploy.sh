#!/bin/bash
# ============================================================
# LiveMapEvents — Manual Deployment Script for Ubuntu Server
# ============================================================
# Usage:
#   chmod +x deploy.sh
#   ./deploy.sh              # Build and deploy
#   ./deploy.sh --rebuild    # Force rebuild without cache
#   ./deploy.sh --down       # Stop all containers
#   ./deploy.sh --logs       # Tail application logs
#   ./deploy.sh --status     # Show container status
#   ./deploy.sh --setup      # First-time server setup
# ============================================================

set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
COMPOSE_FILE="$DEPLOY_DIR/docker-compose.yml"

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

# ── First-time server setup ──
setup_server() {
    log "Setting up Ubuntu server for Docker deployment..."

    # Install Docker if not present
    if ! command -v docker &> /dev/null; then
        log "Installing Docker..."
        sudo apt-get update
        sudo apt-get install -y ca-certificates curl gnupg
        sudo install -m 0755 -d /etc/apt/keyrings
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
        sudo chmod a+r /etc/apt/keyrings/docker.gpg
        echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo $VERSION_CODENAME) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
        sudo apt-get update
        sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
        sudo usermod -aG docker $USER
        log "Docker installed. You may need to log out and back in for group changes."
    else
        log "Docker already installed: $(docker --version)"
    fi

    # Create .env.docker from example if it doesn't exist
    if [ ! -f "$DEPLOY_DIR/.env.docker" ]; then
        if [ -f "$DEPLOY_DIR/.env.docker.example" ]; then
            cp "$DEPLOY_DIR/.env.docker.example" "$DEPLOY_DIR/.env.docker"
            warn "Created .env.docker from example. Please edit it with your actual values!"
            warn "Run: nano $DEPLOY_DIR/.env.docker"
        else
            error ".env.docker.example not found!"
            exit 1
        fi
    fi

    # Configure firewall
    if command -v ufw &> /dev/null; then
        log "Configuring UFW firewall..."
        sudo ufw allow 22/tcp    # SSH
        sudo ufw allow 80/tcp    # HTTP
        sudo ufw allow 443/tcp   # HTTPS (future)
        sudo ufw --force enable
        log "Firewall configured."
    fi

    log "Server setup complete!"
}

# ── Build and Deploy ──
deploy() {
    local no_cache=""
    if [ "$1" == "--rebuild" ]; then
        no_cache="--no-cache"
        log "Force rebuilding without cache..."
    fi

    # Check .env.docker exists
    if [ ! -f "$DEPLOY_DIR/.env.docker" ]; then
        error ".env.docker not found! Run: ./deploy.sh --setup"
        exit 1
    fi

    log "Building Docker image..."
    docker compose -f "$COMPOSE_FILE" build $no_cache app

    log "Stopping old containers..."
    docker compose -f "$COMPOSE_FILE" down --timeout 30

    log "Starting containers..."
    docker compose -f "$COMPOSE_FILE" up -d

    log "Waiting for application to be healthy..."
    for i in $(seq 1 30); do
        if docker compose -f "$COMPOSE_FILE" exec -T app curl -sf http://localhost/health > /dev/null 2>&1; then
            log "Application is healthy!"
            break
        fi
        if [ "$i" -eq 30 ]; then
            error "Application failed health check after 150 seconds"
            docker compose -f "$COMPOSE_FILE" logs app --tail=30
            exit 1
        fi
        info "Waiting... ($i/30)"
        sleep 5
    done

    # Cleanup
    docker image prune -f > /dev/null 2>&1

    log "Deployment successful!"
    echo ""
    show_status
}

# ── Show Status ──
show_status() {
    log "Container status:"
    docker compose -f "$COMPOSE_FILE" ps
    echo ""
    info "App URL: http://$(hostname -I | awk '{print $1}'):${APP_PORT:-8080}"
    info "Health:  http://$(hostname -I | awk '{print $1}'):${APP_PORT:-8080}/health"
    info "API:     http://$(hostname -I | awk '{print $1}'):${APP_PORT:-8080}/api/v1/auth/me"
}

# ── Main ──
case "${1:-}" in
    --setup)
        setup_server
        ;;
    --down)
        log "Stopping all containers..."
        docker compose -f "$COMPOSE_FILE" down
        log "All containers stopped."
        ;;
    --logs)
        docker compose -f "$COMPOSE_FILE" logs -f --tail=100 ${2:-app}
        ;;
    --status)
        show_status
        ;;
    --rebuild)
        deploy --rebuild
        ;;
    *)
        deploy
        ;;
esac
