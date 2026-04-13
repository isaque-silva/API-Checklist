#!/bin/bash

# ============================================================================
#  API Checklist - Instalador Automático para Ubuntu Server
#  Compatível com Ubuntu 20.04 / 22.04 / 24.04 LTS
# ============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
GRAY='\033[0;90m'
NC='\033[0m'
BOLD='\033[1m'
DIM='\033[2m'

LOG_FILE="/tmp/checklist-install-$(date +%Y%m%d_%H%M%S).log"

STEP_CURRENT=0
STEP_TOTAL=0

log() { echo "[$(date '+%H:%M:%S')] $1" | tee -a "$LOG_FILE"; }
info() { echo -e "  ${BLUE}>>>>${NC} $1" | tee -a "$LOG_FILE"; }
success() { echo -e "  ${GREEN} OK ${NC} $1" | tee -a "$LOG_FILE"; }
warn() { echo -e "  ${YELLOW}!!!!${NC} $1" | tee -a "$LOG_FILE"; }
error() { echo -e "  ${RED}ERRO${NC} $1" | tee -a "$LOG_FILE"; }

# ── Função de input com label, descrição e exemplo ─────────────────────────
# Toda exibição vai para stderr (>&2) para não ser capturada pelo $()
field() {
    local label="$1"
    local description="$2"
    local example="$3"
    local default="$4"
    local required="$5"
    local result=""

    echo "" >&2
    echo -e "  ${BOLD}${label}${NC}" >&2

    if [ -n "$description" ]; then
        echo -e "  ${GRAY}${description}${NC}" >&2
    fi

    if [ -n "$example" ]; then
        echo -e "  ${DIM}Exemplo: ${example}${NC}" >&2
    fi

    if [ -n "$default" ]; then
        echo -en "  ${CYAN}>${NC} [${GREEN}${default}${NC}]: " >&2
    else
        echo -en "  ${CYAN}>${NC} " >&2
    fi

    read -r result
    result="${result:-$default}"

    if [ "$required" = "true" ] && [ -z "$result" ]; then
        echo -e "  ${RED}ERRO${NC} Este campo é obrigatório." >&2
        field "$label" "$description" "$example" "$default" "$required"
        return
    fi

    echo "$result"
}

# ── Função de input secreto (senha/token) ──────────────────────────────────
field_secret() {
    local label="$1"
    local description="$2"
    local required="$3"
    local result=""

    echo "" >&2
    echo -e "  ${BOLD}${label}${NC}" >&2

    if [ -n "$description" ]; then
        echo -e "  ${GRAY}${description}${NC}" >&2
    fi

    echo -en "  ${CYAN}>${NC} " >&2
    read -rs result
    echo "" >&2

    if [ "$required" = "true" ] && [ -z "$result" ]; then
        echo -e "  ${RED}ERRO${NC} Este campo é obrigatório." >&2
        field_secret "$label" "$description" "$required"
        return
    fi

    echo "$result"
}

# ── Função de pergunta sim/não ─────────────────────────────────────────────
field_yes_no() {
    local label="$1"
    local description="$2"
    local default="${3:-s}"
    local result=""

    echo "" >&2
    echo -e "  ${BOLD}${label}${NC}" >&2

    if [ -n "$description" ]; then
        echo -e "  ${GRAY}${description}${NC}" >&2
    fi

    if [ "$default" = "s" ]; then
        echo -en "  ${CYAN}>${NC} (${GREEN}S${NC}/n): " >&2
    else
        echo -en "  ${CYAN}>${NC} (s/${GREEN}N${NC}): " >&2
    fi

    read -r result
    result="${result:-$default}"
    if [[ "$result" =~ ^[sS]$ ]]; then
        return 0
    else
        return 1
    fi
}

# ── Função de seleção de opções ────────────────────────────────────────────
field_select() {
    local label="$1"
    local description="$2"
    shift 2
    local options=("$@")
    local result=""

    echo "" >&2
    echo -e "  ${BOLD}${label}${NC}" >&2

    if [ -n "$description" ]; then
        echo -e "  ${GRAY}${description}${NC}" >&2
    fi

    for i in "${!options[@]}"; do
        echo -e "  ${CYAN}$((i+1))${NC}) ${options[$i]}" >&2
    done

    echo -en "  ${CYAN}>${NC} Escolha [1]: " >&2
    read -r result
    result="${result:-1}"

    echo "$((result-1))"
}

# ── Banner ─────────────────────────────────────────────────────────────────
banner() {
    clear
    echo ""
    echo -e "${BOLD}${BLUE}  ╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${BOLD}${BLUE}  ║                                                              ║${NC}"
    echo -e "${BOLD}${BLUE}  ║       ${GREEN}API CHECKLIST${BLUE} - Instalador Automático                 ║${NC}"
    echo -e "${BOLD}${BLUE}  ║       Ubuntu Server 20.04 / 22.04 / 24.04 LTS               ║${NC}"
    echo -e "${BOLD}${BLUE}  ║                                                              ║${NC}"
    echo -e "${BOLD}${BLUE}  ╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${GRAY}Log da instalação: ${LOG_FILE}${NC}"
    echo -e "  ${GRAY}Data: $(date '+%d/%m/%Y %H:%M')${NC}"
    echo ""
}

# ── Cabeçalho de seção ─────────────────────────────────────────────────────
section() {
    echo ""
    echo -e "  ${BOLD}${YELLOW}┌──────────────────────────────────────────────────────────┐${NC}"
    echo -e "  ${BOLD}${YELLOW}│  $1$(printf '%*s' $((55 - ${#1})) '')│${NC}"
    echo -e "  ${BOLD}${YELLOW}└──────────────────────────────────────────────────────────┘${NC}"
}

# ── Cabeçalho de passo de instalação ───────────────────────────────────────
step() {
    STEP_CURRENT=$((STEP_CURRENT + 1))
    echo ""
    echo -e "  ${BOLD}${GREEN}[${STEP_CURRENT}/${STEP_TOTAL}]${NC} ${BOLD}$1${NC}"
    echo -e "  ${GREEN}$(printf '%.0s─' {1..58})${NC}"
}

# ── Verificações ───────────────────────────────────────────────────────────
check_root() {
    if [ "$EUID" -ne 0 ]; then
        error "Este script precisa ser executado como root."
        echo ""
        echo -e "  Execute com: ${BOLD}sudo bash install.sh${NC}"
        echo ""
        exit 1
    fi
}

check_ubuntu() {
    if ! grep -qi "ubuntu" /etc/os-release 2>/dev/null; then
        error "Este instalador é compatível apenas com Ubuntu Server."
        exit 1
    fi
    success "Sistema: $(lsb_release -ds 2>/dev/null || grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d '"')"
}

# ============================================================================
#  ETAPA 1: COLETA DE INFORMAÇÕES
# ============================================================================
collect_info() {

    # ── GIT ─────────────────────────────────────────────────────────────
    section "1/6  REPOSITÓRIO GIT"
    echo ""
    echo -e "  ${GRAY}Configure o acesso ao repositório do projeto.${NC}"
    echo -e "  ${GRAY}Suporta GitHub, GitLab, Bitbucket ou qualquer servidor Git.${NC}"

    GIT_PROVIDER_IDX=$(field_select \
        "Provedor Git" \
        "Selecione onde o repositório está hospedado." \
        "GitHub" "GitLab" "Bitbucket" "Outro")

    GIT_PROVIDERS=("GitHub" "GitLab" "Bitbucket" "Outro")
    GIT_PROVIDER="${GIT_PROVIDERS[$GIT_PROVIDER_IDX]}"

    GIT_REPO_URL=$(field \
        "URL do Repositório (HTTP/HTTPS)" \
        "Cole a URL HTTP ou HTTPS do repositório. Não use SSH." \
        "https://github.com/usuario/api-checklist.git  ou  http://servidor/projeto.git" \
        "" \
        "true")

    GIT_IS_PRIVATE=false
    if field_yes_no \
        "Repositório privado?" \
        "Se o repositório exigir autenticação para clone, responda Sim." \
        "s"; then
        GIT_IS_PRIVATE=true

        GIT_USERNAME=$(field \
            "Usuário ${GIT_PROVIDER}" \
            "Seu nome de usuário no ${GIT_PROVIDER}." \
            "meu-usuario" \
            "" \
            "true")

        GIT_TOKEN=$(field_secret \
            "Token de Acesso (${GIT_PROVIDER} Personal Access Token)" \
            "Gere um token em: Settings > Developer Settings > Personal Access Tokens. O token precisa de permissão de leitura no repositório." \
            "true")

        if [[ "$GIT_REPO_URL" == https://* ]]; then
            PROTOCOL="https://"
            REPO_PATH="${GIT_REPO_URL#https://}"
        elif [[ "$GIT_REPO_URL" == http://* ]]; then
            PROTOCOL="http://"
            REPO_PATH="${GIT_REPO_URL#http://}"
        else
            PROTOCOL="https://"
            REPO_PATH="$GIT_REPO_URL"
        fi
        GIT_CLONE_URL="${PROTOCOL}${GIT_USERNAME}:${GIT_TOKEN}@${REPO_PATH}"
    else
        GIT_CLONE_URL="$GIT_REPO_URL"
    fi

    GIT_BRANCH=$(field \
        "Branch para Deploy" \
        "A branch que será clonada e usada em produção." \
        "main" \
        "main" \
        "false")

    # ── BANCO DE DADOS ─────────────────────────────────────────────────
    section "2/6  BANCO DE DADOS MySQL"
    echo ""
    echo -e "  ${GRAY}O MySQL será instalado automaticamente.${NC}"
    echo -e "  ${GRAY}Configure as credenciais do banco que será criado.${NC}"

    DB_NAME=$(field \
        "Nome do Banco de Dados" \
        "Nome do schema MySQL que será criado para a aplicação." \
        "checklist" \
        "checklist" \
        "false")

    DB_USER=$(field \
        "Usuário do Banco de Dados" \
        "Usuário MySQL que será criado com acesso ao banco." \
        "checklist_user" \
        "checklist_user" \
        "false")

    DB_PASSWORD=$(field_secret \
        "Senha do Banco de Dados" \
        "Defina uma senha forte para o usuário MySQL. Mínimo 8 caracteres recomendado." \
        "true")

    DB_PORT=$(field \
        "Porta do MySQL" \
        "Porta padrão do MySQL. Altere apenas se necessário." \
        "3306" \
        "3306" \
        "false")

    # ── SERVIDOR WEB ───────────────────────────────────────────────────
    section "3/6  SERVIDOR WEB (Nginx)"
    echo ""
    echo -e "  ${GRAY}O Nginx será instalado e configurado como proxy reverso.${NC}"

    APP_DOMAIN=$(field \
        "Domínio do Servidor" \
        "O domínio que será usado para acessar a API. Use 'localhost' para teste local." \
        "api.meusite.com.br" \
        "localhost" \
        "false")

    INSTALL_DIR=$(field \
        "Diretório de Instalação" \
        "Caminho absoluto onde o projeto será instalado no servidor." \
        "/var/www/checklist" \
        "/var/www/checklist" \
        "false")

    SETUP_SSL=false
    if [ "$APP_DOMAIN" != "localhost" ]; then
        if field_yes_no \
            "Configurar Certificado SSL (HTTPS)?" \
            "Instala certificado gratuito Let's Encrypt. Requer domínio apontando para este servidor." \
            "s"; then
            SETUP_SSL=true

            SSL_EMAIL=$(field \
                "E-mail para o Certificado SSL" \
                "Let's Encrypt envia avisos de expiração para este e-mail." \
                "admin@meusite.com.br" \
                "" \
                "true")
        fi
    fi

    # ── EMAIL SMTP ─────────────────────────────────────────────────────
    section "4/6  ENVIO DE E-MAIL (SMTP)"
    echo ""
    echo -e "  ${GRAY}Configure para que a aplicação possa enviar e-mails${NC}"
    echo -e "  ${GRAY}(notificações de checklist concluído, etc).${NC}"

    SETUP_MAIL=false
    if field_yes_no \
        "Configurar envio de e-mails?" \
        "Se não configurar agora, pode configurar depois no arquivo .env." \
        "n"; then
        SETUP_MAIL=true

        SMTP_PROVIDERS=("Gmail" "Outlook/Office 365" "Amazon SES" "Outro servidor SMTP")
        SMTP_IDX=$(field_select \
            "Provedor de E-mail" \
            "Selecione o provedor SMTP que será usado." \
            "${SMTP_PROVIDERS[@]}")

        case $SMTP_IDX in
            0) MAIL_HOST_DEFAULT="smtp.gmail.com"; MAIL_PORT_DEFAULT="587"; MAIL_ENC_DEFAULT="tls" ;;
            1) MAIL_HOST_DEFAULT="smtp.office365.com"; MAIL_PORT_DEFAULT="587"; MAIL_ENC_DEFAULT="tls" ;;
            2) MAIL_HOST_DEFAULT="email-smtp.us-east-1.amazonaws.com"; MAIL_PORT_DEFAULT="587"; MAIL_ENC_DEFAULT="tls" ;;
            *) MAIL_HOST_DEFAULT=""; MAIL_PORT_DEFAULT="587"; MAIL_ENC_DEFAULT="tls" ;;
        esac

        MAIL_HOST=$(field \
            "Servidor SMTP (Host)" \
            "Endereço do servidor SMTP." \
            "$MAIL_HOST_DEFAULT" \
            "$MAIL_HOST_DEFAULT" \
            "true")

        MAIL_PORT=$(field \
            "Porta SMTP" \
            "Porta de conexão SMTP. 587 (TLS) ou 465 (SSL)." \
            "$MAIL_PORT_DEFAULT" \
            "$MAIL_PORT_DEFAULT" \
            "false")

        MAIL_USERNAME=$(field \
            "Usuário SMTP (E-mail)" \
            "E-mail ou usuário usado para autenticar no servidor SMTP." \
            "notificacoes@meusite.com.br" \
            "" \
            "true")

        MAIL_PASSWORD=$(field_secret \
            "Senha SMTP" \
            "Para Gmail, use uma 'Senha de App' (Google > Segurança > Senhas de app)." \
            "true")

        MAIL_FROM=$(field \
            "E-mail Remetente (From)" \
            "O endereço que aparecerá como remetente nos e-mails enviados." \
            "$MAIL_USERNAME" \
            "$MAIL_USERNAME" \
            "false")

        MAIL_ENCRYPTION=$(field \
            "Criptografia SMTP" \
            "Protocolo de segurança: tls (porta 587) ou ssl (porta 465)." \
            "tls" \
            "$MAIL_ENC_DEFAULT" \
            "false")
    fi

    # ── EXTRAS ─────────────────────────────────────────────────────────
    section "5/6  CONFIGURAÇÕES ADICIONAIS"

    APP_TIMEZONE=$(field \
        "Fuso Horário do Servidor" \
        "Fuso horário PHP/Laravel. Afeta datas e logs." \
        "America/Sao_Paulo" \
        "America/Sao_Paulo" \
        "false")

    SETUP_REDIS=false
    if field_yes_no \
        "Instalar Redis?" \
        "Redis melhora performance de cache e filas. Recomendado para produção." \
        "s"; then
        SETUP_REDIS=true
    fi

    SETUP_SUPERVISOR=false
    if field_yes_no \
        "Instalar Supervisor (processamento de filas)?" \
        "Mantém os workers de fila rodando em background. Necessário para envio de e-mails assíncronos." \
        "s"; then
        SETUP_SUPERVISOR=true
    fi

    # ── RESUMO ─────────────────────────────────────────────────────────
    section "6/6  RESUMO DA INSTALAÇÃO"
    echo ""
    echo -e "  ${GRAY}Confira todas as configurações antes de iniciar:${NC}"
    echo ""

    echo -e "  ${BOLD}Repositório Git${NC}"
    echo -e "  ├─ Provedor:       ${GREEN}${GIT_PROVIDER}${NC}"
    echo -e "  ├─ URL:            ${GREEN}${GIT_REPO_URL}${NC}"
    echo -e "  ├─ Branch:         ${GREEN}${GIT_BRANCH}${NC}"
    if [ "$GIT_IS_PRIVATE" = true ]; then
        echo -e "  ├─ Privado:        ${GREEN}Sim${NC}"
        echo -e "  └─ Usuário:        ${GREEN}${GIT_USERNAME}${NC}"
    else
        echo -e "  └─ Privado:        ${GREEN}Não${NC}"
    fi

    echo ""
    echo -e "  ${BOLD}Banco de Dados MySQL${NC}"
    echo -e "  ├─ Nome:           ${GREEN}${DB_NAME}${NC}"
    echo -e "  ├─ Usuário:        ${GREEN}${DB_USER}${NC}"
    echo -e "  ├─ Senha:          ${GREEN}********${NC}"
    echo -e "  └─ Porta:          ${GREEN}${DB_PORT}${NC}"

    echo ""
    echo -e "  ${BOLD}Servidor Web${NC}"
    echo -e "  ├─ Domínio:        ${GREEN}${APP_DOMAIN}${NC}"
    echo -e "  ├─ Diretório:      ${GREEN}${INSTALL_DIR}${NC}"
    if [ "$SETUP_SSL" = true ]; then
        echo -e "  └─ SSL (HTTPS):    ${GREEN}Sim (${SSL_EMAIL})${NC}"
    else
        echo -e "  └─ SSL (HTTPS):    ${YELLOW}Não${NC}"
    fi

    if [ "$SETUP_MAIL" = true ]; then
        echo ""
        echo -e "  ${BOLD}E-mail SMTP${NC}"
        echo -e "  ├─ Host:           ${GREEN}${MAIL_HOST}:${MAIL_PORT}${NC}"
        echo -e "  ├─ Usuário:        ${GREEN}${MAIL_USERNAME}${NC}"
        echo -e "  ├─ Remetente:      ${GREEN}${MAIL_FROM}${NC}"
        echo -e "  └─ Criptografia:   ${GREEN}${MAIL_ENCRYPTION}${NC}"
    fi

    echo ""
    echo -e "  ${BOLD}Extras${NC}"
    echo -e "  ├─ Timezone:       ${GREEN}${APP_TIMEZONE}${NC}"
    if [ "$SETUP_REDIS" = true ]; then
        echo -e "  ├─ Redis:          ${GREEN}Sim${NC}"
    else
        echo -e "  ├─ Redis:          ${YELLOW}Não${NC}"
    fi
    if [ "$SETUP_SUPERVISOR" = true ]; then
        echo -e "  └─ Supervisor:     ${GREEN}Sim${NC}"
    else
        echo -e "  └─ Supervisor:     ${YELLOW}Não${NC}"
    fi

    echo ""
    echo -e "  ${BOLD}${YELLOW}─────────────────────────────────────────────────────────${NC}"

    if ! field_yes_no \
        "Iniciar a instalação com essas configurações?" \
        "Após confirmar, o processo de instalação será iniciado automaticamente." \
        "s"; then
        echo ""
        warn "Instalação cancelada."
        echo ""
        exit 0
    fi

    # Conta os passos de instalação
    STEP_TOTAL=12
    if [ "$SETUP_REDIS" = true ]; then STEP_TOTAL=$((STEP_TOTAL + 1)); fi
    if [ "$SETUP_SUPERVISOR" = true ]; then STEP_TOTAL=$((STEP_TOTAL + 1)); fi
    if [ "$SETUP_SSL" = true ]; then STEP_TOTAL=$((STEP_TOTAL + 1)); fi
}

# ============================================================================
#  FUNÇÕES DE INSTALAÇÃO
# ============================================================================
install_system_deps() {
    step "Atualizando sistema e instalando pacotes base"

    info "Atualizando lista de pacotes (apt update)..."
    apt update -y >> "$LOG_FILE" 2>&1
    info "Atualizando pacotes instalados (apt upgrade)..."
    apt upgrade -y >> "$LOG_FILE" 2>&1
    success "Sistema atualizado."

    info "Instalando ferramentas base (curl, wget, unzip, git, ufw)..."
    apt install -y software-properties-common curl wget unzip git ufw \
        acl apt-transport-https ca-certificates gnupg lsb-release >> "$LOG_FILE" 2>&1
    success "Pacotes base instalados."

    info "Configurando fuso horário: ${APP_TIMEZONE}"
    timedatectl set-timezone "$APP_TIMEZONE" >> "$LOG_FILE" 2>&1
    success "Fuso horário configurado."

    info "Configurando firewall (UFW): SSH, HTTP, HTTPS"
    ufw default deny incoming >> "$LOG_FILE" 2>&1 || true
    ufw default allow outgoing >> "$LOG_FILE" 2>&1 || true
    ufw allow ssh >> "$LOG_FILE" 2>&1 || true
    ufw allow 80/tcp >> "$LOG_FILE" 2>&1 || true
    ufw allow 443/tcp >> "$LOG_FILE" 2>&1 || true
    echo "y" | ufw enable >> "$LOG_FILE" 2>&1 || true
    success "Firewall ativo."
}

install_nginx() {
    step "Instalando Nginx (servidor web)"

    apt install -y nginx >> "$LOG_FILE" 2>&1
    systemctl start nginx >> "$LOG_FILE" 2>&1
    systemctl enable nginx >> "$LOG_FILE" 2>&1
    success "Nginx instalado e rodando."
}

install_mysql() {
    step "Instalando MySQL e criando banco de dados"

    info "Instalando MySQL Server..."
    apt install -y mysql-server >> "$LOG_FILE" 2>&1
    systemctl start mysql >> "$LOG_FILE" 2>&1
    systemctl enable mysql >> "$LOG_FILE" 2>&1
    success "MySQL instalado."

    info "Criando banco '${DB_NAME}' e usuário '${DB_USER}'..."
    if mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >> "$LOG_FILE" 2>&1 && \
       mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';" >> "$LOG_FILE" 2>&1 && \
       mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';" >> "$LOG_FILE" 2>&1 && \
       mysql -e "FLUSH PRIVILEGES;" >> "$LOG_FILE" 2>&1; then
        success "Banco de dados e usuário criados."
    else
        error "Falha ao criar banco de dados ou usuário MySQL."
        tail -10 "$LOG_FILE"
        exit 1
    fi
}

install_php() {
    step "Instalando PHP 8.2 e extensões"

    info "Adicionando repositório PHP (ppa:ondrej/php)..."
    add-apt-repository ppa:ondrej/php -y >> "$LOG_FILE" 2>&1
    apt update -y >> "$LOG_FILE" 2>&1

    info "Instalando PHP 8.2 + extensões (mysql, xml, mbstring, curl, gd, redis...)..."
    apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
        php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl \
        php8.2-sqlite3 php8.2-tokenizer php8.2-dom php8.2-fileinfo \
        php8.2-redis php8.2-soap php8.2-imagick >> "$LOG_FILE" 2>&1

    systemctl start php8.2-fpm >> "$LOG_FILE" 2>&1
    systemctl enable php8.2-fpm >> "$LOG_FILE" 2>&1

    info "Otimizando php.ini (memory=256M, upload=100M, timeout=300s)..."
    PHP_INI="/etc/php/8.2/fpm/php.ini"
    sed -i "s/memory_limit = .*/memory_limit = 256M/" "$PHP_INI"
    sed -i "s/max_execution_time = .*/max_execution_time = 300/" "$PHP_INI"
    sed -i "s/max_input_time = .*/max_input_time = 300/" "$PHP_INI"
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" "$PHP_INI"
    sed -i "s/post_max_size = .*/post_max_size = 100M/" "$PHP_INI"
    systemctl restart php8.2-fpm >> "$LOG_FILE" 2>&1

    success "PHP 8.2 instalado: $(php -v 2>/dev/null | head -1)"
}

install_composer() {
    step "Instalando Composer e Node.js"

    info "Instalando Composer (gerenciador de pacotes PHP)..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" >> "$LOG_FILE" 2>&1
    php composer-setup.php >> "$LOG_FILE" 2>&1
    mv composer.phar /usr/local/bin/composer >> "$LOG_FILE" 2>&1
    chmod +x /usr/local/bin/composer
    rm -f composer-setup.php
    success "Composer instalado."

    info "Instalando Node.js LTS..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - >> "$LOG_FILE" 2>&1
    apt install -y nodejs >> "$LOG_FILE" 2>&1
    success "Node.js instalado: $(node -v 2>/dev/null)"
}

install_extras() {
    step "Instalando FFmpeg e Ghostscript"

    info "Instalando FFmpeg (compressão de vídeos)..."
    apt install -y ffmpeg >> "$LOG_FILE" 2>&1
    success "FFmpeg instalado."

    info "Instalando Ghostscript (compressão de PDFs)..."
    apt install -y ghostscript >> "$LOG_FILE" 2>&1
    success "Ghostscript instalado."
}

install_redis() {
    if [ "$SETUP_REDIS" = true ]; then
        step "Instalando Redis (cache e filas)"

        apt install -y redis-server >> "$LOG_FILE" 2>&1
        systemctl start redis-server >> "$LOG_FILE" 2>&1
        systemctl enable redis-server >> "$LOG_FILE" 2>&1
        success "Redis instalado e rodando."
    fi
}

install_supervisor() {
    if [ "$SETUP_SUPERVISOR" = true ]; then
        step "Instalando Supervisor (workers de fila)"

        apt install -y supervisor >> "$LOG_FILE" 2>&1
        systemctl start supervisor >> "$LOG_FILE" 2>&1
        systemctl enable supervisor >> "$LOG_FILE" 2>&1
        success "Supervisor instalado."
    fi
}

# ============================================================================
#  DEPLOY DA APLICAÇÃO
# ============================================================================
clone_and_setup() {
    step "Clonando repositório e instalando dependências"

    if [ -d "$INSTALL_DIR" ] && [ "$(ls -A "$INSTALL_DIR" 2>/dev/null)" ]; then
        warn "Diretório ${INSTALL_DIR} já existe e não está vazio."
        info "Limpando diretório para novo clone..."
        rm -rf "$INSTALL_DIR"
    fi

    info "Criando diretório ${INSTALL_DIR}..."
    mkdir -p "$INSTALL_DIR"

    info "Clonando ${GIT_REPO_URL} (branch: ${GIT_BRANCH})..."
    local GIT_ERROR
    GIT_ERROR=$(git clone --branch "$GIT_BRANCH" --single-branch "$GIT_CLONE_URL" "$INSTALL_DIR" 2>&1) || {
        echo "$GIT_ERROR" >> "$LOG_FILE"
        error "Falha ao clonar repositório!"
        echo ""
        echo -e "  ${RED}Detalhes do erro:${NC}"
        echo "$GIT_ERROR" | tail -5 | while read -r line; do echo -e "  ${GRAY}${line}${NC}"; done
        echo ""
        echo -e "  ${YELLOW}Verifique:${NC}"
        echo -e "  ├─ URL do repositório: ${CYAN}${GIT_REPO_URL}${NC}"
        echo -e "  ├─ Branch: ${CYAN}${GIT_BRANCH}${NC}"
        if [ "$GIT_IS_PRIVATE" = true ]; then
            echo -e "  ├─ Usuário: ${CYAN}${GIT_USERNAME}${NC}"
            echo -e "  └─ Token: ${CYAN}(verifique se está correto e tem permissão de leitura)${NC}"
        else
            echo -e "  └─ O repositório é acessível publicamente?"
        fi
        echo ""
        exit 1
    }
    echo "$GIT_ERROR" >> "$LOG_FILE"

    if [ "$GIT_IS_PRIVATE" = true ]; then
        cd "$INSTALL_DIR"
        git remote set-url origin "$GIT_REPO_URL" >> "$LOG_FILE" 2>&1 || true
    fi
    success "Repositório clonado."

    cd "$INSTALL_DIR"

    info "Instalando dependências PHP (composer install --no-dev)..."
    if COMPOSER_ALLOW_SUPERUSER=1 composer install --optimize-autoloader --no-dev --no-interaction >> "$LOG_FILE" 2>&1; then
        success "Dependências PHP instaladas."
    else
        error "Falha ao instalar dependências PHP."
        tail -15 "$LOG_FILE"
        exit 1
    fi

    if [ -f "package.json" ]; then
        info "Instalando dependências Node.js (npm install)..."
        if npm install >> "$LOG_FILE" 2>&1; then
            success "Dependências Node.js instaladas."
        else
            warn "Falha ao instalar dependências Node.js."
        fi

        if npm run build --dry-run >> "$LOG_FILE" 2>&1; then
            info "Compilando assets (npm run build)..."
            if npm run build >> "$LOG_FILE" 2>&1; then
                success "Assets compilados."
            else
                warn "Falha ao compilar assets."
            fi
        fi
    fi
}

configure_env() {
    step "Configurando ambiente Laravel (.env)"

    cd "$INSTALL_DIR"

    if [ "$APP_DOMAIN" = "localhost" ]; then
        APP_URL="http://localhost"
    elif [ "$SETUP_SSL" = true ]; then
        APP_URL="https://${APP_DOMAIN}"
    else
        APP_URL="http://${APP_DOMAIN}"
    fi

    CACHE_STORE="file"
    QUEUE_CONNECTION="database"
    if [ "$SETUP_REDIS" = true ]; then
        CACHE_STORE="redis"
    fi

    info "Gerando arquivo .env de produção..."

    cat > .env << ENVEOF
APP_NAME="API Checklist"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL}

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
APP_TIMEZONE=${APP_TIMEZONE}

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${QUEUE_CONNECTION}

CACHE_STORE=${CACHE_STORE}
CACHE_PREFIX=checklist

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=${MAIL_HOST:-smtp.gmail.com}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
MAIL_FROM_ADDRESS="${MAIL_FROM:-}"
MAIL_FROM_NAME="\${APP_NAME}"
MAIL_TIMEOUT=120

VITE_APP_NAME="\${APP_NAME}"
ENVEOF

    success "Arquivo .env gerado."

    info "Gerando APP_KEY (php artisan key:generate)..."
    if php artisan key:generate --force >> "$LOG_FILE" 2>&1; then
        success "APP_KEY gerada."
    else
        error "Falha ao gerar APP_KEY. Verifique o log: $LOG_FILE"
    fi
}

run_migrations() {
    step "Executando migrações e seeders"

    cd "$INSTALL_DIR"

    info "Rodando migrations (php artisan migrate --force)..."
    if php artisan migrate --force >> "$LOG_FILE" 2>&1; then
        success "Migrações executadas."
    else
        warn "Algumas migrações falharam. Verifique o log: $LOG_FILE"
        warn "Últimas linhas do erro:"
        tail -20 "$LOG_FILE" | while read -r line; do echo "  $line"; done
        echo ""
        info "Tentando continuar a instalação..."
    fi

    info "Rodando seeders (php artisan db:seed --force)..."
    if php artisan db:seed --force >> "$LOG_FILE" 2>&1; then
        success "Seeders executados."
    else
        warn "Nenhum seeder para executar ou seeder falhou."
    fi

    info "Criando storage link (php artisan storage:link)..."
    php artisan storage:link >> "$LOG_FILE" 2>&1 || true
    success "Storage link criado."

    info "Configurando permissões de diretório..."
    chown -R www-data:www-data "$INSTALL_DIR"
    chmod -R 755 "$INSTALL_DIR"
    chmod -R 775 "$INSTALL_DIR/storage"
    chmod -R 775 "$INSTALL_DIR/bootstrap/cache"
    success "Permissões aplicadas (www-data)."
}

# ============================================================================
#  NGINX + SSL + SUPERVISOR
# ============================================================================
configure_nginx() {
    step "Configurando virtual host Nginx"

    rm -f /etc/nginx/sites-enabled/default

    if [ "$SETUP_SSL" = true ]; then
        NGINX_CONF=$(cat << NGINXEOF
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN};
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${APP_DOMAIN};
    root ${INSTALL_DIR}/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/${APP_DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${APP_DOMAIN}/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    client_max_body_size 100M;

    access_log /var/log/nginx/checklist.access.log;
    error_log /var/log/nginx/checklist.error.log;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ /\. { deny all; }
    location ~ \.(env|log|conf)$ { deny all; }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
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
NGINXEOF
)
    else
        NGINX_CONF=$(cat << NGINXEOF
server {
    listen 80;
    listen [::]:80;
    server_name ${APP_DOMAIN};
    root ${INSTALL_DIR}/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;

    client_max_body_size 100M;

    access_log /var/log/nginx/checklist.access.log;
    error_log /var/log/nginx/checklist.error.log;

    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ /\. { deny all; }
    location ~ \.(env|log|conf)$ { deny all; }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
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
NGINXEOF
)
    fi

    echo "$NGINX_CONF" > /etc/nginx/sites-available/checklist
    ln -sf /etc/nginx/sites-available/checklist /etc/nginx/sites-enabled/

    info "Testando configuração Nginx..."
    if nginx -t >> "$LOG_FILE" 2>&1; then
        systemctl restart nginx >> "$LOG_FILE" 2>&1
        success "Nginx configurado e reiniciado."
    else
        warn "Configuração Nginx com erro. Verifique: nginx -t"
        tail -5 "$LOG_FILE"
    fi
}

configure_ssl() {
    if [ "$SETUP_SSL" = true ]; then
        step "Obtendo certificado SSL (Let's Encrypt)"

        info "Instalando Certbot..."
        apt install -y certbot python3-certbot-nginx >> "$LOG_FILE" 2>&1

        TEMP_CONF=$(cat << TMPEOF
server {
    listen 80;
    server_name ${APP_DOMAIN};
    root ${INSTALL_DIR}/public;
    index index.php;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
    }
}
TMPEOF
)
        echo "$TEMP_CONF" > /etc/nginx/sites-available/checklist
        nginx -t >> "$LOG_FILE" 2>&1 && systemctl reload nginx >> "$LOG_FILE" 2>&1

        info "Solicitando certificado para ${APP_DOMAIN}..."
        if certbot --nginx -d "$APP_DOMAIN" --non-interactive --agree-tos -m "$SSL_EMAIL" >> "$LOG_FILE" 2>&1; then
            success "Certificado SSL emitido."
        else
            warn "Falha ao obter certificado SSL. Você pode tentar manualmente depois com:"
            warn "  sudo certbot --nginx -d ${APP_DOMAIN}"
        fi

        configure_nginx

        info "Testando renovação automática..."
        certbot renew --dry-run >> "$LOG_FILE" 2>&1 || warn "Teste de renovação falhou. Verifique manualmente."
        success "SSL configurado com renovação automática."
    fi
}

configure_supervisor() {
    if [ "$SETUP_SUPERVISOR" = true ]; then
        step "Configurando workers de fila (Supervisor)"

        cat > /etc/supervisor/conf.d/checklist-worker.conf << SUPEOF
[program:checklist-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log
stopwaitsecs=3600
SUPEOF

        supervisorctl reread >> "$LOG_FILE" 2>&1
        supervisorctl update >> "$LOG_FILE" 2>&1
        supervisorctl start checklist-worker:* >> "$LOG_FILE" 2>&1 || true
        success "Workers de fila configurados e ativos."
    fi
}

optimize_and_backup() {
    step "Otimizando aplicação e configurando backup"

    cd "$INSTALL_DIR"

    info "Cacheando configurações, rotas e views..."
    php artisan config:cache >> "$LOG_FILE" 2>&1 || warn "config:cache falhou."
    php artisan route:cache >> "$LOG_FILE" 2>&1 || warn "route:cache falhou."
    php artisan view:cache >> "$LOG_FILE" 2>&1 || warn "view:cache falhou."
    php artisan optimize >> "$LOG_FILE" 2>&1 || warn "optimize falhou."
    COMPOSER_ALLOW_SUPERUSER=1 composer dump-autoload --optimize --classmap-authoritative >> "$LOG_FILE" 2>&1 || true
    success "Aplicação otimizada."

    info "Criando script de backup automático..."
    mkdir -p /var/backups/checklist

    cat > /usr/local/bin/backup-checklist.sh << 'BKPEOF'
#!/bin/bash
BACKUP_DIR="/var/backups/checklist"
PROJECT_DIR="INSTALL_DIR_PLACEHOLDER"
DB_NAME="DB_NAME_PLACEHOLDER"
DB_USER="DB_USER_PLACEHOLDER"
DB_PASS="DB_PASS_PLACEHOLDER"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7

mkdir -p "$BACKUP_DIR"
echo "[$(date)] Iniciando backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_$DATE.sql.gz"
tar -czf "$BACKUP_DIR/files_$DATE.tar.gz" -C "$PROJECT_DIR" .env storage/app
find "$BACKUP_DIR" -type f -mtime +$RETENTION_DAYS -delete
echo "[$(date)] Backup concluido: db_$DATE.sql.gz"
BKPEOF

    sed -i "s|INSTALL_DIR_PLACEHOLDER|${INSTALL_DIR}|g" /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_NAME_PLACEHOLDER|${DB_NAME}|g" /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_USER_PLACEHOLDER|${DB_USER}|g" /usr/local/bin/backup-checklist.sh
    sed -i "s|DB_PASS_PLACEHOLDER|${DB_PASSWORD}|g" /usr/local/bin/backup-checklist.sh

    chmod +x /usr/local/bin/backup-checklist.sh
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/backup-checklist.sh >> /var/log/backup-checklist.log 2>&1") | sort -u | crontab -

    success "Backup agendado diariamente as 02:00."
}

# ============================================================================
#  TELA FINAL
# ============================================================================
show_result() {
    echo ""
    echo ""
    echo -e "  ${BOLD}${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "  ${BOLD}${GREEN}║                                                              ║${NC}"
    echo -e "  ${BOLD}${GREEN}║          INSTALAÇÃO CONCLUÍDA COM SUCESSO!                    ║${NC}"
    echo -e "  ${BOLD}${GREEN}║                                                              ║${NC}"
    echo -e "  ${BOLD}${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BOLD}Acesso${NC}"
    echo -e "  ├─ URL da API:     ${GREEN}${APP_URL}${NC}"
    echo -e "  ├─ Diretório:      ${GREEN}${INSTALL_DIR}${NC}"
    echo -e "  └─ Log:            ${GREEN}${LOG_FILE}${NC}"
    echo ""
    echo -e "  ${BOLD}Banco de Dados${NC}"
    echo -e "  ├─ Host:           ${GREEN}127.0.0.1:${DB_PORT}${NC}"
    echo -e "  ├─ Database:       ${GREEN}${DB_NAME}${NC}"
    echo -e "  └─ Usuário:        ${GREEN}${DB_USER}${NC}"
    echo ""
    echo -e "  ${BOLD}Serviços Ativos${NC}"
    echo -e "  ├─ Nginx:          ${GREEN}rodando${NC}"
    echo -e "  ├─ PHP-FPM 8.2:    ${GREEN}rodando${NC}"
    echo -e "  ├─ MySQL:          ${GREEN}rodando${NC}"
    if [ "$SETUP_REDIS" = true ]; then
        echo -e "  ├─ Redis:          ${GREEN}rodando${NC}"
    fi
    if [ "$SETUP_SUPERVISOR" = true ]; then
        echo -e "  ├─ Supervisor:     ${GREEN}rodando${NC}"
    fi
    if [ "$SETUP_SSL" = true ]; then
        echo -e "  ├─ SSL:            ${GREEN}ativo (auto-renovação)${NC}"
    fi
    echo -e "  └─ Backup:         ${GREEN}agendado (02:00 diário)${NC}"
    echo ""
    echo -e "  ${BOLD}Endpoints da API${NC}"
    echo -e "  ├─ POST /api/v1/auth/register     Criar conta"
    echo -e "  ├─ POST /api/v1/auth/login         Login (retorna token)"
    echo -e "  ├─ GET  /api/v1/auth/me            Dados do usuário"
    echo -e "  ├─ GET  /api/v1/checklist-templates Listar checklists"
    echo -e "  └─ ...e todas as demais rotas"
    echo ""
    echo -e "  ${BOLD}Comandos Úteis${NC}"
    echo -e "  ├─ ${CYAN}cd ${INSTALL_DIR}${NC}"
    echo -e "  ├─ ${CYAN}php artisan migrate --force${NC}          Rodar migrations"
    echo -e "  ├─ ${CYAN}php artisan db:seed --force${NC}           Rodar seeders"
    echo -e "  ├─ ${CYAN}sudo supervisorctl status${NC}             Status dos workers"
    echo -e "  ├─ ${CYAN}sudo backup-checklist.sh${NC}              Backup manual"
    echo -e "  └─ ${CYAN}tail -f storage/logs/laravel.log${NC}      Monitorar logs"
    echo ""
    echo -e "  ${YELLOW}${BOLD}Primeiro passo: Crie um client e um usuário via API${NC}"
    echo ""
    echo -e "  ${GRAY}# 1. Criar um client${NC}"
    echo -e "  curl -X POST ${APP_URL}/api/v1/clients \\"
    echo -e "    -H 'Content-Type: application/json' \\"
    echo -e "    -d '{\"name\":\"Minha Empresa\",\"document\":\"12345678000199\"}'"
    echo ""
    echo -e "  ${GRAY}# 2. Registrar usuário vinculado ao client${NC}"
    echo -e "  curl -X POST ${APP_URL}/api/v1/auth/register \\"
    echo -e "    -H 'Content-Type: application/json' \\"
    echo -e "    -d '{\"client_id\":\"<ID_DO_CLIENT>\",\"name\":\"Admin\",\"email\":\"admin@email.com\",\"password\":\"senha123\",\"password_confirmation\":\"senha123\"}'"
    echo ""
    echo -e "  ${GRAY}# 3. Fazer login${NC}"
    echo -e "  curl -X POST ${APP_URL}/api/v1/auth/login \\"
    echo -e "    -H 'Content-Type: application/json' \\"
    echo -e "    -d '{\"email\":\"admin@email.com\",\"password\":\"senha123\"}'"
    echo ""
}

# ============================================================================
#  EXECUÇÃO PRINCIPAL
# ============================================================================
main() {
    banner
    check_root
    check_ubuntu

    collect_info

    install_system_deps
    install_nginx
    install_mysql
    install_php
    install_composer
    install_extras
    install_redis
    install_supervisor
    clone_and_setup
    configure_env
    run_migrations
    configure_nginx
    configure_ssl
    configure_supervisor
    optimize_and_backup

    show_result
}

main "$@"
