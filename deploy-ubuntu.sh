#!/bin/bash

export LANG=pt_BR.UTF-8
export LC_ALL=pt_BR.UTF-8

set -e

# ============================================================
# deploy-ubuntu.sh - Instalacao automatizada API Checklist
# Ubuntu 20.04 / 22.04 / 24.04 LTS
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()    { echo -e "${GREEN}[OK]${NC} $1"; }
warn()   { echo -e "${YELLOW}[AVISO]${NC} $1"; }
error()  { echo -e "${RED}[ERRO]${NC} $1"; exit 1; }
info()   { echo -e "${BLUE}[INFO]${NC} $1"; }
header() { echo -e "\n${BLUE}========================================${NC}"; echo -e "${BLUE} $1${NC}"; echo -e "${BLUE}========================================${NC}"; }

# ============================================================
# CONFIGURACOES - Edite antes de executar
# ============================================================
APP_HOST="${APP_HOST:-}"
APP_PORT="${APP_PORT:-8080}"
DB_NAME="${DB_NAME:-checklist_prod}"
DB_USER="${DB_USER:-checklist_user}"
DB_PASS="${DB_PASS:-}"
REPO_URL="${REPO_URL:-}"
PROJECT_DIR="${PROJECT_DIR:-/home/checklist}"
PHP_VERSION="8.2"
TIMEZONE="America/Sao_Paulo"

# ============================================================
# Verificacoes iniciais
# ============================================================
check_root() {
    if [ "$EUID" -ne 0 ]; then
        error "Execute como root: sudo bash deploy-ubuntu.sh"
    fi
}

check_vars() {
    header "Verificando configuracoes"

    if [ -z "$DB_PASS" ]; then
        read -s -p "Senha do banco de dados (DB_PASS): " DB_PASS
        echo
        [ -z "$DB_PASS" ] && error "DB_PASS nao pode ser vazio"
    fi

    if [ -z "$REPO_URL" ]; then
        read -p "URL do repositorio Git (ou deixe vazio para copiar manualmente): " REPO_URL
    fi

    # Detectar IP da maquina automaticamente se APP_HOST nao definido
    if [ -z "$APP_HOST" ]; then
        APP_HOST=$(hostname -I | awk '{print $1}')
        warn "APP_HOST nao definido. Usando IP detectado: $APP_HOST"
    fi

    APP_URL="http://${APP_HOST}:${APP_PORT}"

    info "Host/IP:     $APP_HOST"
    info "Porta:       $APP_PORT"
    info "URL:         $APP_URL"
    info "Banco:       $DB_NAME"
    info "Usuario DB:  $DB_USER"
    info "Diretorio:   $PROJECT_DIR"
    info "PHP:         $PHP_VERSION"

    read -p "Continuar? [s/N] " CONFIRM
    [[ "$CONFIRM" =~ ^[sS]$ ]] || { warn "Cancelado pelo usuario."; exit 0; }
}

# ============================================================
# 1. Atualizar sistema
# ============================================================
step_update_system() {
    header "1. Atualizando sistema"
    apt update -y
    apt upgrade -y
    log "Sistema atualizado"
}

# ============================================================
# 2. Configuracao inicial do servidor
# ============================================================
step_server_config() {
    header "2. Configuracao inicial do servidor"

    timedatectl set-timezone "$TIMEZONE"
    log "Fuso horario: $TIMEZONE"

    apt install -y ufw
    ufw default deny incoming
    ufw default allow outgoing
    ufw allow ssh
    ufw allow ${APP_PORT}/tcp
    echo "y" | ufw enable
    log "Firewall UFW configurado (porta $APP_PORT aberta)"
}

# ============================================================
# 3. Pilha LEMP (Nginx + MySQL + PHP)
# ============================================================
step_lemp() {
    header "3. Instalando pilha LEMP"

    apt install -y nginx
    systemctl start nginx
    systemctl enable nginx
    log "Nginx instalado"

    apt install -y mysql-server
    systemctl start mysql
    systemctl enable mysql
    log "MySQL instalado"

    apt install -y software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt update -y
    apt install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-tokenizer \
        php${PHP_VERSION}-dom \
        php${PHP_VERSION}-fileinfo \
        php${PHP_VERSION}-redis

    systemctl start php${PHP_VERSION}-fpm
    systemctl enable php${PHP_VERSION}-fpm
    log "PHP ${PHP_VERSION} instalado"
}

# ============================================================
# 4. Dependencias especificas
# ============================================================
step_dependencies() {
    header "4. Instalando dependencias especificas"

    if ! command -v node &>/dev/null; then
        curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
        apt install -y nodejs
        log "Node.js instalado: $(node -v)"
    else
        log "Node.js ja instalado: $(node -v)"
    fi

    if ! command -v composer &>/dev/null; then
        php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
        php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
        rm /tmp/composer-setup.php
        log "Composer instalado"
    else
        log "Composer ja instalado"
    fi

    apt install -y ffmpeg
    log "FFmpeg instalado"

    apt install -y ghostscript
    log "Ghostscript instalado"

    apt install -y git
    log "Git instalado"

    apt install -y redis-server
    systemctl start redis-server
    systemctl enable redis-server
    log "Redis instalado"

    apt install -y supervisor
    systemctl start supervisor
    systemctl enable supervisor
    log "Supervisor instalado"
}

# ============================================================
# 5. Banco de dados
# ============================================================
step_database() {
    header "5. Configurando banco de dados"

    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
    mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    log "Banco '$DB_NAME' e usuario '$DB_USER' criados"
}

# ============================================================
# 6. Implantacao do codigo
# ============================================================
step_deploy_code() {
    header "6. Implantacao do codigo"

    mkdir -p "$PROJECT_DIR"
    chown -R "${SUDO_USER:-$USER}:${SUDO_USER:-$USER}" "$PROJECT_DIR" 2>/dev/null || true

    if [ -n "$REPO_URL" ]; then
        if [ -d "$PROJECT_DIR/.git" ]; then
            git -C "$PROJECT_DIR" pull origin main
            log "Repositorio atualizado"
        else
            git clone "$REPO_URL" "$PROJECT_DIR"
            log "Repositorio clonado"
        fi
    else
        warn "REPO_URL nao definido. Copie os arquivos para $PROJECT_DIR e execute com --skip-clone"
    fi
}

# ============================================================
# 7. Ambiente Laravel
# ============================================================
step_laravel_env() {
    header "7. Configurando ambiente Laravel"

    cd "$PROJECT_DIR"

    composer install --optimize-autoloader --no-dev --no-interaction
    log "Dependencias Composer instaladas"

    npm install
    npm run build
    log "Assets compilados"

    if [ ! -f ".env" ]; then
        cp .env.example .env
    fi

    # Funcao: substitui linha existente ou adiciona ao final se nao existir
    set_env() {
        local key="$1" val="$2"
        if grep -q "^${key}=" .env; then
            sed -i "s|^${key}=.*|${key}=${val}|" .env
        else
            echo "${key}=${val}" >> .env
        fi
    }

    set_env APP_ENV        "production"
    set_env APP_DEBUG      "false"
    set_env APP_URL        "${APP_URL}"
    set_env DB_CONNECTION  "mysql"
    set_env DB_HOST        "127.0.0.1"
    set_env DB_PORT        "3306"
    set_env DB_DATABASE    "${DB_NAME}"
    set_env DB_USERNAME    "${DB_USER}"
    set_env DB_PASSWORD    "${DB_PASS}"
    set_env CACHE_STORE    "redis"
    set_env QUEUE_CONNECTION "database"
    log ".env configurado"

    php artisan key:generate --force
    log "Chave da aplicacao gerada"

    php artisan migrate --force
    log "Migracoes executadas"

    php artisan storage:link
    log "Link storage criado"
}

# ============================================================
# 8. Permissoes
# ============================================================
step_permissions() {
    header "8. Configurando permissoes"

    chown -R www-data:www-data "$PROJECT_DIR"
    chmod -R 755 "$PROJECT_DIR"
    chmod -R 775 "$PROJECT_DIR/storage"
    chmod -R 775 "$PROJECT_DIR/bootstrap/cache"
    log "Permissoes configuradas"
}

# ============================================================
# 9. Nginx
# ============================================================
step_nginx() {
    header "9. Configurando Nginx"

    cat > /etc/nginx/sites-available/checklist <<NGINX
server {
    listen ${APP_PORT};
    listen [::]:${APP_PORT};
    server_name ${APP_HOST} _;
    root ${PROJECT_DIR}/public;
    index index.php index.html index.htm;

    client_max_body_size 100M;
    client_body_timeout 60s;
    client_header_timeout 60s;

    access_log /var/log/nginx/checklist.access.log;
    error_log /var/log/nginx/checklist.error.log;

    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss image/svg+xml;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|conf)$ {
        deny all;
    }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_read_timeout 300;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX

    rm -f /etc/nginx/sites-enabled/default
    ln -sf /etc/nginx/sites-available/checklist /etc/nginx/sites-enabled/

    nginx -t && systemctl restart nginx
    log "Nginx configurado"
}

# ============================================================
# 10. SSL com Certbot
# ============================================================
step_ssl() {
    header "10. SSL"
    warn "Acesso via IP:porta — SSL ignorado."
    warn "Para habilitar SSL, aponte um dominio para este IP e execute: sudo certbot --nginx -d SEU_DOMINIO"
}

# ============================================================
# 11. Supervisor (workers) + Cron (scheduler)
# ============================================================
step_supervisor() {
    header "11. Configurando Supervisor e Scheduler"

    # Apenas queue:work no Supervisor (processo longo - correto)
    cat > /etc/supervisor/conf.d/checklist-worker.conf <<SUP
[program:checklist-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${PROJECT_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${PROJECT_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUP

    # Remover configuracao incorreta do scheduler no Supervisor (se existir)
    rm -f /etc/supervisor/conf.d/checklist-scheduler.conf

    supervisorctl reread
    supervisorctl update
    supervisorctl start checklist-worker:* || true
    log "Supervisor configurado (queue workers)"

    # schedule:run deve rodar via cron a cada minuto (nao via Supervisor)
    (crontab -l 2>/dev/null | grep -v "schedule:run"; echo "* * * * * www-data php ${PROJECT_DIR}/artisan schedule:run >> ${PROJECT_DIR}/storage/logs/scheduler.log 2>&1") | crontab -
    log "Scheduler configurado via cron (a cada minuto)"
}

# ============================================================
# 12. Otimizacoes Laravel
# ============================================================
step_optimize() {
    header "12. Otimizando para producao"

    cd "$PROJECT_DIR"
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize
    composer dump-autoload --optimize --classmap-authoritative
    log "Otimizacoes aplicadas"
}

# ============================================================
# 13. Backup automatizado
# ============================================================
step_backup() {
    header "13. Configurando backup automatizado"

    mkdir -p /var/backups/checklist

    cat > /usr/local/bin/backup-checklist.sh <<'BACKUP'
#!/bin/bash
BACKUP_DIR="/var/backups/checklist"
PROJECT_DIR="PROJECT_DIR_PLACEHOLDER"
DB_NAME="DB_NAME_PLACEHOLDER"
DB_USER="DB_USER_PLACEHOLDER"
DB_PASS="DB_PASS_PLACEHOLDER"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7

BACKUP_DAY_DIR="$BACKUP_DIR/$(date +%Y%m%d)"
mkdir -p "$BACKUP_DAY_DIR"

echo "Iniciando backup: $(date)"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DAY_DIR/database_$DATE.sql.gz"
tar -czf "$BACKUP_DAY_DIR/files_$DATE.tar.gz" -C "$PROJECT_DIR" storage/app/public
cp "$PROJECT_DIR/.env" "$BACKUP_DAY_DIR/env_$DATE.backup"
find "$BACKUP_DIR" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} + 2>/dev/null || true
echo "Backup concluido: $(date)"
BACKUP

    sed -i "s|PROJECT_DIR_PLACEHOLDER|${PROJECT_DIR}|g" /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_NAME_PLACEHOLDER|${DB_NAME}|g"         /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_USER_PLACEHOLDER|${DB_USER}|g"         /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_PASS_PLACEHOLDER|${DB_PASS}|g"         /usr/local/bin/backup-checklist.sh

    chmod +x /usr/local/bin/backup-checklist.sh
    touch /var/log/backup-checklist.log
    chmod 644 /var/log/backup-checklist.log

    (crontab -l 2>/dev/null | grep -v backup-checklist; echo "0 2 * * * /usr/local/bin/backup-checklist.sh >> /var/log/backup-checklist.log 2>&1") | crontab -

    log "Backup diario agendado para 2:00"
}

# ============================================================
# 14. Log rotation
# ============================================================
step_logrotate() {
    header "14. Configurando log rotation"

    cat > /etc/logrotate.d/checklist <<LOGROTATE
${PROJECT_DIR}/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php${PHP_VERSION}-fpm
    endscript
}
LOGROTATE

    log "Log rotation configurado"
}

# ============================================================
# Verificacao final
# ============================================================
step_final_check() {
    header "Verificacao final"

    services=("nginx" "php${PHP_VERSION}-fpm" "mysql" "redis-server" "supervisor")
    for svc in "${services[@]}"; do
        if systemctl is-active --quiet "$svc"; then
            log "$svc: rodando"
        else
            warn "$svc: nao esta rodando"
        fi
    done

    echo ""
    info "Teste de acesso: curl -I ${APP_URL}"
    info "Logs Laravel:    tail -f ${PROJECT_DIR}/storage/logs/laravel.log"
    info "Logs Nginx:      tail -f /var/log/nginx/checklist.error.log"
    echo ""
    log "Implantacao concluida!"
}

# ============================================================
# Main
# ============================================================
main() {
    check_root
    check_vars

    step_update_system
    step_server_config
    step_lemp
    step_dependencies
    step_database
    step_deploy_code
    step_laravel_env
    step_permissions
    step_nginx
    step_ssl
    step_supervisor
    step_optimize
    step_backup
    step_logrotate
    step_final_check
}

case "${1:-}" in
    --skip-clone) REPO_URL=""; main ;;
    --only-optimize) check_root; step_optimize ;;
    --only-permissions) check_root; step_permissions ;;
    --only-nginx) check_root; step_nginx ;;
    --only-ssl) check_root; step_ssl ;;
    --only-backup) check_root; step_backup ;;
    --update)
        check_root
        header "Atualizando aplicacao"
        /usr/local/bin/backup-checklist.sh
        git -C "$PROJECT_DIR" pull origin main
        cd "$PROJECT_DIR"
        composer install --optimize-autoloader --no-dev --no-interaction
        npm install && npm run build
        php artisan migrate --force
        step_permissions
        step_optimize
        supervisorctl restart checklist-worker:*
        supervisorctl restart checklist-scheduler
        log "Atualizacao concluida"
        ;;
    *) main ;;
esac
