# Guia Completo de Implantação em Produção - Ubuntu Server

Este guia fornece instruções detalhadas passo a passo para implantar o projeto API Checklist em um servidor Ubuntu (20.04/22.04/24.04 LTS).

## 📋 Índice

1. [Pré-requisitos do Sistema](#1-pré-requisitos-do-sistema)
2. [Configuração Inicial do Servidor](#2-configuração-inicial-do-servidor)
3. [Instalação do Pilha LEMP](#3-instalação-da-pilha-lemp)
4. [Instalação de Dependências Específicas](#4-instalação-de-dependências-específicas)
5. [Configuração do Banco de Dados](#5-configuração-do-banco-de-dados)
6. [Implantação do Código](#6-implantação-do-código)
7. [Configuração do Ambiente Laravel](#7-configuração-do-ambiente-laravel)
8. [Configuração do Nginx](#8-configuração-do-nginx)
9. [Configuração do SSL](#9-configuração-do-ssl)
10. [Configuração de Filas e Tarefas Agendadas](#10-configuração-de-filas-e-tarefas-agendadas)
11. [Otimizações de Desempenho](#11-otimizações-de-desempenho)
12. [Backup Automatizado](#12-backup-automatizado)
13. [Monitoramento e Logs](#13-monitoramento-e-logs)
14. [Solução de Problemas](#14-solução-de-problemas)

---

## 1. Pré-requisitos do Sistema

### Requisitos Mínimos
- Ubuntu Server 20.04/22.04/24.04 LTS
- Mínimo 2GB RAM (recomendado 4GB+)
- Mínimo 20GB de espaço em disco
- Acesso SSH com usuário sudo
- Nome de domínio configurado (opcional, mas recomendado)

### Atualizar Sistema
```bash
# Atualizar lista de pacotes
sudo apt update

# Atualizar sistema
sudo apt upgrade -y

# Reiniciar se necessário
sudo reboot
```

---

## 2. Configuração Inicial do Servidor

### 2.1 Configurar Fuso Horário
```bash
# Listar fusos horários disponíveis
sudo timedatectl list-timezones

# Definir fuso horário (exemplo: America/Sao_Paulo)
sudo timedatectl set-timezone America/Sao_Paulo

# Verificar configuração
sudo timedatectl status
```

### 2.2 Configurar Firewall
```bash
# Instalar UFW se não estiver instalado
sudo apt install ufw -y

# Configurar regras básicas
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir SSH (essencial!)
sudo ufw allow ssh

# Permitir HTTP e HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Ativar firewall
sudo ufw enable

# Verificar status
sudo ufw status
```

### 2.3 Criar Usuário para Deploy (Opcional)
```bash
# Criar usuário
sudo adduser deploy

# Adicionar ao grupo sudo
sudo usermod -aG sudo deploy

# Fazer login como usuário deploy
su - deploy
```

---

## 3. Instalação da Pilha LEMP

### 3.1 Instalar Nginx
```bash
# Instalar Nginx
#sudo apt install nginx -y

# Iniciar e habilitar serviço
#sudo systemctl start nginx
#sudo systemctl enable nginx

# Verificar status
sudo systemctl status nginx

# Testar acesso (abra no navegador: http://IP_DO_SERVIDOR)
curl http://localhost
```

### 3.2 Instalar MySQL
```bash
# Instalar MySQL Server
sudo apt install mysql-server -y

# Iniciar e habilitar serviço
sudo systemctl start mysql
sudo systemctl enable mysql

# Verificar status
sudo systemctl status mysql

# Script de segurança inicial
sudo mysql_secure_installation

# Fazer login no MySQL
sudo mysql
```

### 3.3 Instalar PHP e Extensões
```bash
# Adicionar repositório PHP (para versões mais recentes)
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Instalar PHP 8.2 e extensões necessárias
sudo apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring \
php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl \
php8.2-sqlite3 php8.2-tokenizer php8.2-dom php8.2-fileinfo -y

# Iniciar e habilitar PHP-FPM
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Verificar status
sudo systemctl status php8.2-fpm

# Verificar versão do PHP
php -v
```

---

## 4. Instalação de Dependências Específicas

### 4.1 Instalar Node.js e NPM
```bash
# Instalar Node.js usando NVM (recomendado)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Recarregar perfil
source ~/.bashrc

# Instalar Node.js LTS
nvm install --lts
nvm use --lts
nvm alias default node

# Verificar instalação
node -v
npm -v

# Ou instalar diretamente (alternativa)
# curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
# sudo apt install nodejs -y
```

### 4.2 Instalar Composer
```bash
# Baixar e instalar Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer

# Tornar executável
sudo chmod +x /usr/local/bin/composer

# Verificar instalação
composer --version

# Limpar arquivo de instalação
rm composer-setup.php
```

### 4.3 Instalar FFmpeg (Compressão de Vídeos)
```bash
# Adicionar repositório FFmpeg
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:jonathonf/ffmpeg-4 -y
sudo apt update

# Instalar FFmpeg
sudo apt install ffmpeg -y

# Verificar instalação
ffmpeg -version

# Testar compressão básica
ffmpeg -i input.mp4 -c:v libx264 -crf 28 output.mp4
```

### 4.4 Instalar Ghostscript (Compressão de PDFs)
```bash
# Instalar Ghostscript
sudo apt install ghostscript -y

# Verificar instalação
gs --version

# Testar compressão PDF
gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen \
-dNOPAUSE -dQUIET -dBATCH -sOutputFile=output.pdf input.pdf
```

### 4.5 Instalar Git
```bash
# Instalar Git
sudo apt install git -y

# Configurar Git (substitua com seus dados)
git config --global user.name "Seu Nome"
git config --global user.email "seu.email@dominio.com"

# Verificar configuração
git config --list
```

---

## 5. Configuração do Banco de Dados

### 5.1 Criar Banco de Dados e Usuário
```bash
# Fazer login no MySQL
sudo mysql

# Criar banco de dados
CREATE DATABASE checklist_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Criar usuário com senha forte (substitua SENHA_FORTE)
CREATE USER 'checklist_user'@'localhost' IDENTIFIED BY 'SENHA_FORTE';

# Conceder privilégios
GRANT ALL PRIVILEGES ON checklist_prod.* TO 'checklist_user'@'localhost';

# Aplicar mudanças
FLUSH PRIVILEGES;

# Sair do MySQL
EXIT;

# Testar conexão
mysql -u checklist_user -p checklist_prod
```

### 5.2 Otimizações do MySQL
```bash
# Editar configuração do MySQL
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf

# Adicionar ou modificar estas linhas no final do arquivo:
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
query_cache_type = 1

# Reiniciar MySQL
sudo systemctl restart mysql
```

---

## 6. Implantação do Código

### 6.1 Criar Diretório do Projeto
```bash
# Criar diretório principal
sudo mkdir -p /var/www/checklist

# Definir permissões
sudo chown -R $USER:$USER /var/www/checklist

# Navegar para o diretório
cd /var/www/checklist
```

### 6.2 Clonar Repositório
```bash
# Clonar repositório (substitua URL do seu repositório)
git clone https://seu-repositorio.git .

# Ou se for um projeto novo, copie os arquivos manualmente
# scp -r /path/to/local/project/* user@server:/var/www/checklist/
```

### 6.3 Instalar Dependências PHP
```bash
# Navegar para o diretório do projeto
cd /var/www/checklist

# Instalar dependências do Composer (produção)
composer install --optimize-autoloader --no-dev --no-interaction

# Otimizar carregador automático
composer dump-autoload --optimize
```

### 6.4 Instalar Dependências Node e Compilar Assets
```bash
# Instalar dependências NPM
npm install

# Compilar assets para produção
npm run build

# Limpar cache do npm
npm cache clean --force
```

---

## 7. Configuração do Ambiente Laravel

### 7.1 Configurar Arquivo .env
```bash
# Copiar arquivo de exemplo
cp .env.example .env

# Editar arquivo .env
nano .env
```

**Conteúdo do arquivo .env (produção):**
```env
APP_NAME="API Checklist"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://seu-dominio.com

APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR

APP_MAINTENANCE_DRIVER=file
PHP_CLI_SERVER_WORKERS=4

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=checklist_prod
DB_USERNAME=checklist_user
DB_PASSWORD=SENHA_FORTE

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=redis
CACHE_PREFIX=checklist

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=seu-smtp.dominio.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@dominio.com
MAIL_PASSWORD=sua_senha_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=seu-email@dominio.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

### 7.2 Gerar Chave da Aplicação
```bash
# Gerar chave
php artisan key:generate

# Verificar se foi gerada
grep APP_KEY .env
```

### 7.3 Executar Migrações e Seeders
```bash
# Executar migrações (forçar em produção)
php artisan migrate --force

# Executar seeders (se existirem)
php artisan db:seed --force
```

### 7.4 Criar Links Simbólicos
```bash
# Link simbólico para storage
php artisan storage:link

# Verificar se foi criado
ls -la public/storage
```

### 7.5 Configurar Permissões
```bash
# Definir permissões corretas
sudo chown -R www-data:www-data /var/www/checklist
sudo chmod -R 755 /var/www/checklist
sudo chmod -R 775 /var/www/checklist/storage
sudo chmod -R 775 /var/www/checklist/bootstrap/cache

# Dar permissão de escrita nas pastas necessárias
sudo chmod -R 777 /var/www/checklist/storage/logs
sudo chmod -R 777 /var/www/checklist/storage/framework/cache
sudo chmod -R 777 /var/www/checklist/storage/framework/sessions
sudo chmod -R 777 /var/www/checklist/storage/framework/views
```

---

## 8. Configuração do Nginx

### 8.1 Criar Virtual Host
```bash
# Criar arquivo de configuração
sudo nano /etc/nginx/sites-available/checklist
```

**Conteúdo do arquivo de configuração:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name seu-dominio.com www.seu-dominio.com;
    root /var/www/checklist/public;
    index index.php index.html index.htm;

    # Redirecionar para HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name seu-dominio.com www.seu-dominio.com;
    root /var/www/checklist/public;
    index index.php index.html index.htm;

    # Configuração SSL (será atualizada com Certbot)
    ssl_certificate /etc/letsencrypt/live/seu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seu-dominio.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    # Cabeçalhos de segurança
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Configuração de upload
    client_max_body_size 100M;
    client_body_timeout 60s;
    client_header_timeout 60s;

    # Logs
    access_log /var/log/nginx/checklist.access.log;
    error_log /var/log/nginx/checklist.error.log;

    # Compressão Gzip
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Configuração principal
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Bloquear acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ \.(env|log|conf)$ {
        deny all;
    }

    # Processamento PHP
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_read_timeout 300;
    }

    # Cache de arquivos estáticos
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 8.2 Ativar Site e Reiniciar Nginx
```bash
# Remover site default
sudo rm /etc/nginx/sites-enabled/default

# Habilitar novo site
sudo ln -s /etc/nginx/sites-available/checklist /etc/nginx/sites-enabled/

# Testar configuração
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx

# Verificar status
sudo systemctl status nginx
```

---

## 9. Configuração do SSL

### 9.1 Instalar Certbot
```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Verificar instalação
certbot --version
```

### 9.2 Obter Certificado SSL
```bash
# Obter certificado (substitua seu-dominio.com)
sudo certbot --nginx -d seu-dominio.com -d www.seu-dominio.com

# Seguir instruções:
# - Informar email
# - Aceitar termos
# - Optar por compartilhar email (opcional)
# - Escolher redirecionamento HTTPS (recomendado)
```

### 9.3 Configurar Renovação Automática
```bash
# Testar renovação automática
sudo certbot renew --dry-run

# Verificar agendamento
sudo systemctl list-timers | grep certbot

# Agendar renovação manualmente (se necessário)
sudo crontab -e
# Adicionar linha:
# 0 12 * * * /usr/bin/certbot renew --quiet
```

---

## 10. Configuração de Filas e Tarefas Agendadas

### 10.1 Instalar e Configurar Redis
```bash
# Instalar Redis
sudo apt install redis-server -y

# Configurar Redis
sudo nano /etc/redis/redis.conf

# Modificar as seguintes linhas:
# bind 127.0.0.1 ::1
# requirepass sua_senha_redis (opcional)
# maxmemory 256mb
# maxmemory-policy allkeys-lru

# Reiniciar Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server

# Testar Redis
redis-cli ping
```

### 10.2 Configurar Supervisor para Filas
```bash
# Instalar Supervisor
sudo apt install supervisor -y

# Criar arquivo de configuração para worker
sudo nano /etc/supervisor/conf.d/checklist-worker.conf
```

**Conteúdo do arquivo checklist-worker.conf:**
```ini
[program:checklist-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/checklist/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/checklist/storage/logs/worker.log
stopwaitsecs=3600
```

### 10.3 Configurar Scheduler
```bash
# Criar arquivo de configuração para scheduler
sudo nano /etc/supervisor/conf.d/checklist-scheduler.conf
```

**Conteúdo do arquivo checklist-scheduler.conf:**
```ini
[program:checklist-scheduler]
process_name=%(program_name)s
command=php /var/www/checklist/artisan schedule:run
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/checklist/storage/logs/scheduler.log
```

### 10.4 Ativar Configurações do Supervisor
```bash
# Recarregar configurações do Supervisor
sudo supervisorctl reread

# Ativar novos processos
sudo supervisorctl update

# Iniciar processos
sudo supervisorctl start checklist-worker:*
sudo supervisorctl start checklist-scheduler:*

# Verificar status
sudo supervisorctl status

# Verificar logs
sudo tail -f /var/www/checklist/storage/logs/worker.log
```

---

## 11. Otimizações de Desempenho

### 11.1 Otimizações Laravel
```bash
# Navegar para o diretório do projeto
cd /var/www/checklist

# Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Otimizar Composer
composer dump-autoload --optimize --classmap-authoritative
```

### 11.2 Configurar PHP-FPM
```bash
# Editar configuração do PHP-FPM
sudo nano /etc/php/8.2/fpm/pool.d/www.conf

# Modificar estas configurações:
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 11.3 Configurar PHP.ini
```bash
# Editar arquivo php.ini
sudo nano /etc/php/8.2/fpm/php.ini

# Modificar estas configurações:
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
upload_max_filesize = 100M
post_max_size = 100M
max_file_uploads = 20

# Habilitar OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=0
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.load_comments=1

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 11.4 Configurar Nginx para Performance
```bash
# Editar configuração principal do Nginx
sudo nano /etc/nginx/nginx.conf

# Modificar worker_processes e worker_connections:
worker_processes auto;
worker_connections 1024;

# Adicionar no bloco http:
keepalive_timeout 65;
keepalive_requests 100;

# Reiniciar Nginx
sudo systemctl restart nginx
```

---

## 12. Backup Automatizado

### 12.1 Criar Script de Backup
```bash
# Criar diretório de backup
sudo mkdir -p /var/backups/checklist

# Criar script de backup
sudo nano /usr/local/bin/backup-checklist.sh
```

**Conteúdo do script backup-checklist.sh:**
```bash
#!/bin/bash

# Configurações
BACKUP_DIR="/var/backups/checklist"
PROJECT_DIR="/var/www/checklist"
DB_NAME="checklist_prod"
DB_USER="checklist_user"
DB_PASS="SENHA_FORTE"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7

# Criar diretório de backup do dia
BACKUP_DAY_DIR="$BACKUP_DIR/$(date +%Y%m%d)"
mkdir -p "$BACKUP_DAY_DIR"

echo "Iniciando backup em: $(date)"

# Backup do banco de dados
echo "Fazendo backup do banco de dados..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DAY_DIR/database_$DATE.sql.gz"

# Backup dos arquivos do projeto
echo "Fazendo backup dos arquivos do projeto..."
tar -czf "$BACKUP_DAY_DIR/files_$DATE.tar.gz" -C "$PROJECT_DIR" .

# Backup do arquivo .env
cp "$PROJECT_DIR/.env" "$BACKUP_DAY_DIR/env_$DATE.backup"

# Remover backups antigos
echo "Removendo backups antigos..."
find "$BACKUP_DIR" -type d -mtime +$RETENTION_DAYS -exec rm -rf {} +

# Compactar tudo em um arquivo único
cd "$BACKUP_DIR"
tar -czf "checklist_backup_$DATE.tar.gz" "$(date +%Y%m%d)/"
rm -rf "$(date +%Y%m%d)/"

echo "Backup concluído em: $(date)"
echo "Arquivo: checklist_backup_$DATE.tar.gz"

# Enviar notificação (opcional)
# echo "Backup do Checklist concluído com sucesso" | mail -s "Backup Checklist" admin@dominio.com
```

### 12.2 Configurar Permissões e Cron
```bash
# Tornar script executável
sudo chmod +x /usr/local/bin/backup-checklist.sh

# Adicionar ao crontab
sudo crontab -e

# Adicionar linha para backup diário às 2:00
0 2 * * * /usr/local/bin/backup-checklist.sh >> /var/log/backup-checklist.log 2>&1

# Criar arquivo de log
sudo touch /var/log/backup-checklist.log
sudo chmod 644 /var/log/backup-checklist.log
```

---

## 13. Monitoramento e Logs

### 13.1 Configurar Log Rotation
```bash
# Criar configuração de log rotation
sudo nano /etc/logrotate.d/checklist
```

**Conteúdo do arquivo:**
```
/var/www/checklist/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
```

### 13.2 Monitorar Logs em Tempo Real
```bash
# Logs do Laravel
tail -f /var/www/checklist/storage/logs/laravel.log

# Logs do Nginx
tail -f /var/log/nginx/checklist.error.log
tail -f /var/log/nginx/checklist.access.log

# Logs do PHP-FPM
tail -f /var/log/php8.2-fpm.log

# Logs do Supervisor
tail -f /var/log/supervisor/supervisord.log

# Logs do Worker
tail -f /var/www/checklist/storage/logs/worker.log
```

### 13.3 Comandos Úteis de Verificação
```bash
# Verificar status de todos os serviços
sudo systemctl status nginx php8.2-fpm mysql redis-server supervisor

# Verificar uso de memória e CPU
htop
free -h
df -h

# Verificar conexões ativas
netstat -tulpn | grep :80
netstat -tulpn | grep :443

# Verificar processos do Laravel
ps aux | grep artisan
```

---

## 14. Solução de Problemas

### 14.1 Problemas Comuns

#### Erro 502 Bad Gateway
```bash
# Verificar se PHP-FPM está rodando
sudo systemctl status php8.2-fpm

# Verificar socket do PHP-FPM
ls -la /var/run/php/php8.2-fpm.sock

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### Erro de Permissão
```bash
# Resetar permissões
sudo chown -R www-data:www-data /var/www/checklist
sudo chmod -R 755 /var/www/checklist
sudo chmod -R 775 /var/www/checklist/storage
sudo chmod -R 775 /var/www/checklist/bootstrap/cache
```

#### Erro de Conexão com Banco
```bash
# Testar conexão com MySQL
mysql -u checklist_user -p checklist_prod

# Verificar se MySQL está rodando
sudo systemctl status mysql

# Reiniciar MySQL
sudo systemctl restart mysql
```

#### Erro de Memória
```bash
# Aumentar limite de memória PHP
sudo nano /etc/php/8.2/fpm/php.ini
# Modificar: memory_limit = 512M

# Reiniciar PHP-FPM
sudo systemctl restart php8.2-fpm
```

### 14.2 Comandos de Manutenção
```bash
# Limpar caches Laravel
cd /var/www/checklist
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Otimizar novamente
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Reiniciar serviços
sudo systemctl restart nginx php8.2-fpm mysql redis-server supervisor

# Verificar filas
php artisan queue:failed
php artisan queue:retry all
```

### 14.3 Atualizações Futuras
```bash
# Fazer backup antes de atualizar
/usr/local/bin/backup-checklist.sh

# Atualizar código
cd /var/www/checklist
git pull origin main

# Atualizar dependências
composer install --optimize-autoloader --no-dev
npm install
npm run build

# Executar migrações
php artisan migrate --force

# Limpar e otimizar
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Reiniciar serviços
sudo systemctl restart php8.2-fpm supervisor
```

---

## ✅ Verificação Final

### Lista de Verificação de Implantação

- [ ] Sistema Ubuntu atualizado
- [ ] Firewall UFW configurado
- [ ] Pilha LEMP instalada (Nginx, MySQL, PHP)
- [ ] Node.js e Composer instalados
- [ ] FFmpeg e Ghostscript instalados
- [ ] Banco de dados criado e configurado
- [ ] Código do projeto clonado
- [ ] Dependências instaladas (PHP e Node)
- [ ] Arquivo .env configurado
- [ ] Chave da aplicação gerada
- [ ] Migrações executadas
- [ ] Permissões configuradas
- [ ] Nginx configurado
- [ ] Certificado SSL instalado
- [ ] Redis configurado
- [ ] Filas e scheduler configurados
- [ ] Otimizações aplicadas
- [ ] Backup automatizado configurado
- [ ] Monitoramento configurado

### Testes Finais

```bash
# Testar acesso ao site
curl -I https://seu-dominio.com

# Testar funcionalidades básicas
curl -X GET https://seu-dominio.com/api/health

# Verificar status dos serviços
sudo systemctl status nginx php8.2-fpm mysql redis-server supervisor

# Verificar logs de erros
tail -f /var/www/checklist/storage/logs/laravel.log
```

---

## 🎉 Conclusão

Sua aplicação API Checklist está agora completamente implantada e configurada em produção no Ubuntu Server! 

### Próximos Passos Recomendados:

1. **Monitoramento**: Configure ferramentas de monitoramento como Nagios, Zabbix ou Prometheus
2. **CI/CD**: Implemente pipelines automatizados com GitHub Actions ou GitLab CI
3. **CDN**: Configure uma CDN como Cloudflare para melhor performance
4. **Load Balancer**: Para alta disponibilidade, configure balanceamento de carga
5. **Segurança**: Implemente WAF, hardening adicional e monitoramento de segurança

### Manutenção Contínua:

- Mantenha o sistema atualizado (`sudo apt update && sudo apt upgrade`)
- Monitore logs regularmente
- Verifique backups diariamente
- Mantenha certificados SSL renovados
- Monitore performance e recursos do servidor

Para suporte ou dúvidas, consulte os logs do sistema e a documentação oficial do Laravel.
