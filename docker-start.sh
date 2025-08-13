#!/bin/bash

# DCS Statistics Docker Startup Script with Port Management
# This script handles port availability checking and automatic port selection

set -e

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default configuration
DEFAULT_PORT=8080
CONTAINER_NAME="dcs-statistics"
ENV_FILE=".env"

# Function to print colored output
print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Function to check if a port is available
check_port() {
    local port=$1
    if command -v lsof >/dev/null 2>&1; then
        # macOS/Linux with lsof
        lsof -i :$port >/dev/null 2>&1 && return 1 || return 0
    elif command -v ss >/dev/null 2>&1; then
        # Linux with ss
        ss -tuln | grep -q ":$port " && return 1 || return 0
    elif command -v netstat >/dev/null 2>&1; then
        # Windows/Linux with netstat
        netstat -an | grep -q ":$port.*LISTEN" && return 1 || return 0
    else
        # Fallback: try to bind to the port
        (echo >/dev/tcp/localhost/$port) >/dev/null 2>&1 && return 1 || return 0
    fi
}

# Function to find an available port
find_available_port() {
    local start_port=$1
    local max_attempts=100
    local port=$start_port
    
    for i in $(seq 1 $max_attempts); do
        if check_port $port; then
            echo $port
            return 0
        fi
        port=$((port + 1))
    done
    
    return 1
}

# Function to get current port from .env file
get_current_port() {
    if [ -f "$ENV_FILE" ]; then
        grep "^WEB_PORT=" "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "$DEFAULT_PORT"
    else
        echo "$DEFAULT_PORT"
    fi
}

# Function to update .env file with new port
update_env_port() {
    local port=$1
    
    if [ -f "$ENV_FILE" ]; then
        # Update existing WEB_PORT
        if grep -q "^WEB_PORT=" "$ENV_FILE"; then
            sed -i.bak "s/^WEB_PORT=.*/WEB_PORT=$port/" "$ENV_FILE"
            rm -f "$ENV_FILE.bak"
        else
            echo "WEB_PORT=$port" >> "$ENV_FILE"
        fi
    else
        # Create new .env file
        echo "WEB_PORT=$port" > "$ENV_FILE"
    fi
}

# Function to check Docker installation
check_docker() {
    if ! command -v docker >/dev/null 2>&1; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker daemon is not running. Please start Docker."
        exit 1
    fi
    
    if ! command -v docker-compose >/dev/null 2>&1 && ! docker compose version >/dev/null 2>&1; then
        print_error "Docker Compose is not installed. Please install Docker Compose."
        exit 1
    fi
}

# Function to stop existing container
stop_existing_container() {
    if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        print_info "Stopping existing container..."
        docker compose down >/dev/null 2>&1 || true
    fi
}

# Function to get external IP
get_external_ip() {
    # Try multiple services to get external IP
    local ip=""
    
    # Try curl first
    if command -v curl >/dev/null 2>&1; then
        ip=$(curl -s -4 ifconfig.me 2>/dev/null || \
             curl -s -4 icanhazip.com 2>/dev/null || \
             curl -s -4 ipecho.net/plain 2>/dev/null || \
             curl -s -4 api.ipify.org 2>/dev/null)
    fi
    
    # Try wget if curl failed
    if [ -z "$ip" ] && command -v wget >/dev/null 2>&1; then
        ip=$(wget -qO- -4 ifconfig.me 2>/dev/null || \
             wget -qO- -4 icanhazip.com 2>/dev/null)
    fi
    
    echo "$ip"
}

# Function to display access information
display_access_info() {
    local port=$1
    
    echo ""
    echo "========================================"
    echo -e "${GREEN}DCS Statistics Website is running!${NC}"
    echo "========================================"
    echo ""
    echo "Access your site at:"
    echo -e "  ${BLUE}Local:${NC}      http://localhost:$port"
    
    # Get local network IPs
    if command -v ip >/dev/null 2>&1; then
        # Linux
        local_ips=$(ip -4 addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v '127.0.0.1')
    elif command -v ifconfig >/dev/null 2>&1; then
        # macOS/BSD
        local_ips=$(ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | sed 's/addr://')
    else
        local_ips=""
    fi
    
    if [ -n "$local_ips" ]; then
        echo -e "  ${BLUE}Network:${NC}"
        for ip in $local_ips; do
            echo "              http://$ip:$port"
        done
    fi
    
    # Get external IP
    external_ip=$(get_external_ip)
    if [ -n "$external_ip" ]; then
        echo -e "  ${BLUE}External:${NC}   http://$external_ip:$port"
        echo ""
        print_warning "Note: External access requires port forwarding on your router"
    fi
    
    echo ""
    echo "Admin Panel: http://localhost:$port/site-config/install.php"
    echo ""
    echo "To stop the server, run:"
    echo "  docker compose down"
    echo ""
    
    # Check if port forwarding might be needed
    if [ -n "$external_ip" ]; then
        echo "========================================"
        echo "Port Forwarding Instructions:"
        echo "========================================"
        echo "If you want external access, configure your router to:"
        echo "  1. Forward external port $port to internal port $port"
        echo "  2. Point to this machine's IP address"
        echo ""
        echo "Common router interfaces:"
        echo "  - http://192.168.1.1"
        echo "  - http://192.168.0.1"
        echo "  - http://10.0.0.1"
        echo "========================================"
    fi
}

# Main execution
main() {
    echo "========================================"
    echo "DCS Statistics Docker Launcher"
    echo "========================================"
    echo ""
    
    # Check Docker installation
    print_info "Checking Docker installation..."
    check_docker
    print_success "Docker is installed and running"
    
    # Stop existing container if running
    stop_existing_container
    
    # Get desired port
    DESIRED_PORT=$(get_current_port)
    
    # Check if port is available
    print_info "Checking port $DESIRED_PORT availability..."
    
    if check_port $DESIRED_PORT; then
        print_success "Port $DESIRED_PORT is available"
        SELECTED_PORT=$DESIRED_PORT
    else
        print_warning "Port $DESIRED_PORT is in use"
        
        # Find alternative port
        print_info "Finding available port..."
        SELECTED_PORT=$(find_available_port $DESIRED_PORT)
        
        if [ -z "$SELECTED_PORT" ]; then
            print_error "No available ports found in range $DESIRED_PORT-$((DESIRED_PORT + 100))"
            exit 1
        fi
        
        print_success "Using port $SELECTED_PORT instead"
    fi
    
    # Update .env file with selected port
    update_env_port $SELECTED_PORT
    
    # Build and start container
    print_info "Building Docker image (this may take a few minutes on first run)..."
    
    if docker compose build --no-cache >/dev/null 2>&1; then
        print_success "Docker image built successfully"
    else
        print_error "Failed to build Docker image"
        echo "Run 'docker compose build --no-cache' to see detailed error"
        exit 1
    fi
    
    print_info "Starting container..."
    
    if docker compose up -d >/dev/null 2>&1; then
        print_success "Container started successfully"
    else
        print_error "Failed to start container"
        echo "Run 'docker compose up' to see detailed error"
        exit 1
    fi
    
    # Wait for service to be ready
    print_info "Waiting for service to be ready..."
    sleep 3
    
    # Check if service is responding
    if curl -s -f -o /dev/null http://localhost:$SELECTED_PORT/health-check.php 2>/dev/null; then
        print_success "Service is ready!"
    else
        print_warning "Service may still be starting up..."
        echo "Check the logs with: docker logs dcs-statistics"
    fi
    
    # Always display access info
    display_access_info $SELECTED_PORT
}

# Handle script arguments
case "${1:-}" in
    stop)
        print_info "Stopping DCS Statistics..."
        docker compose down
        print_success "Stopped"
        ;;
    restart)
        print_info "Restarting DCS Statistics..."
        docker compose down
        main
        ;;
    logs)
        docker logs -f dcs-statistics
        ;;
    status)
        if docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
            PORT=$(get_current_port)
            print_success "DCS Statistics is running on port $PORT"
            echo "Access at: http://localhost:$PORT"
        else
            print_info "DCS Statistics is not running"
        fi
        ;;
    *)
        main
        ;;
esac