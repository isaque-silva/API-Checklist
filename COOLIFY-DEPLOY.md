# Deploy - API Checklist via Coolify (Docker)

Guia completo para deploy da API Checklist usando **Coolify** com Docker.

---

## Arquitetura do Container

```
┌─────────────────────────────────────────────┐
│  Container: API Checklist (porta 80)        │
│                                             │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐  │
│  │  Nginx   │  │ PHP-FPM  │  │ Supervisor│  │
│  │  :80     │→ │ :9000    │  │           │  │
│  └─────────┘  └──────────┘  │ • queue    │  │
│                              │ • cron     │  │
│  FFmpeg ✓  Ghostscript ✓    └───────────┘  │
│  Redis ext ✓  Imagick ✓                    │
└─────────────────────────────────────────────┘
         ↓                ↓
  ┌────────────┐   ┌────────────┐
  │   MySQL    │   │   Redis    │
  │  (externo) │   │ (opcional) │
  └────────────┘   └────────────┘
```

O container já inclui: **Nginx + PHP 8.2-FPM + Supervisor + Queue Workers + Cron + FFmpeg + Ghostscript**.

---

## Pré-requisitos

- Servidor com **Coolify** instalado e funcionando
- Acesso ao repositório Git (GitHub, GitLab, ou self-hosted)
- Banco de dados **MySQL 8.0+** (pode ser criado no Coolify)

---

## Passo 1: Criar o Banco de Dados no Coolify

1. No painel do Coolify, vá em **Resources → New Resource**
2. Selecione **Database → MySQL**
3. Configure:
   - **Name:** `checklist-mysql`
   - **Version:** `8.0`
   - **Root Password:** (defina uma senha forte)
   - **Database:** `checklist`
   - **User:** `checklist`
   - **Password:** (defina uma senha forte)
4. Clique em **Deploy**
5. Anote o **hostname interno** gerado (ex: `checklist-mysql-xxxxx`)

> **Dica:** O hostname interno é usado para conectar o app ao banco dentro da rede do Coolify. Ele aparece na seção "Connection" do recurso MySQL.

---

## Passo 2: Criar o Redis no Coolify (Opcional)

Se quiser usar Redis para cache e filas (recomendado):

1. **Resources → New Resource → Database → Redis**
2. Configure:
   - **Name:** `checklist-redis`
   - **Version:** `7`
3. Deploy e anote o hostname interno

---

## Passo 3: Criar a Aplicação no Coolify

### 3.1 Adicionar o recurso

1. Vá em **Resources → New Resource → Application**
2. Selecione a origem:
   - **GitHub/GitLab:** conecte sua conta e selecione o repositório
   - **Public Repository:** cole a URL se for público
   - **Private Repository (self-hosted):** Use "Git Repository (self-hosted)"

### 3.2 Configurar a origem

- **Repository:** URL do repositório
- **Branch:** `dev` (ou a branch de produção)
- **Build Pack:** Selecione **Dockerfile**
- **Dockerfile Location:** `/Dockerfile` (raiz do projeto)

### 3.3 Configurar o domínio

Em **Settings → Domain:**
- Adicione seu domínio: `checklist.seudominio.com`
- Ative **HTTPS** (Coolify gera SSL automaticamente via Let's Encrypt)

### 3.4 Configurar a porta

Em **Settings → Ports Exposes:**
- Port: `80`

---

## Passo 4: Variáveis de Ambiente

Vá em **Environment Variables** e adicione todas as variáveis abaixo.

### Obrigatórias

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `APP_NAME` | `Checklist APP` | Nome da aplicação |
| `APP_ENV` | `production` | Ambiente |
| `APP_KEY` | *(deixe vazio)* | Gerado automaticamente no entrypoint |
| `APP_DEBUG` | `false` | Desativar debug em produção |
| `APP_URL` | `https://checklist.seudominio.com` | URL completa com HTTPS |
| `APP_TIMEZONE` | `America/Sao_Paulo` | Fuso horário |
| `APP_LOCALE` | `pt_BR` | Idioma |
| `APP_FALLBACK_LOCALE` | `pt_BR` | Idioma fallback |
| `APP_FAKER_LOCALE` | `pt_BR` | Locale do Faker |
| `DB_CONNECTION` | `mysql` | Driver do banco |
| `DB_HOST` | `checklist-mysql-xxxxx` | Hostname interno do MySQL no Coolify |
| `DB_PORT` | `3306` | Porta do MySQL |
| `DB_DATABASE` | `checklist` | Nome do banco |
| `DB_USERNAME` | `checklist` | Usuário do banco |
| `DB_PASSWORD` | `sua_senha_aqui` | Senha do banco |
| `RUN_MIGRATIONS` | `true` | Rodar migrations no deploy |
| `RUN_SEEDERS` | `true` | Rodar seeders (mude para `false` após o primeiro deploy) |

### Cache e Filas

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `QUEUE_CONNECTION` | `database` | Driver de filas (`database` ou `redis`) |
| `CACHE_STORE` | `file` | Driver de cache (`file` ou `redis`) |
| `CACHE_PREFIX` | `checklist` | Prefixo do cache |
| `SESSION_DRIVER` | `file` | Driver de sessão |
| `SESSION_LIFETIME` | `120` | Tempo de sessão em minutos |
| `BROADCAST_CONNECTION` | `log` | Driver de broadcast |
| `FILESYSTEM_DISK` | `local` | Disco de arquivos |

### Redis (se criou no passo 2)

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `REDIS_HOST` | `checklist-redis-xxxxx` | Hostname interno do Redis |
| `REDIS_PORT` | `6379` | Porta do Redis |
| `REDIS_PASSWORD` | *(vazio se sem senha)* | Senha do Redis |
| `QUEUE_CONNECTION` | `redis` | Trocar para redis |
| `CACHE_STORE` | `redis` | Trocar para redis |

### E-mail (SMTP)

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `MAIL_MAILER` | `smtp` | Driver de e-mail |
| `MAIL_HOST` | `smtp.gmail.com` | Servidor SMTP |
| `MAIL_PORT` | `587` | Porta SMTP |
| `MAIL_USERNAME` | `seu@email.com` | Usuário SMTP |
| `MAIL_PASSWORD` | `sua_senha_app` | Senha de app |
| `MAIL_ENCRYPTION` | `tls` | Criptografia |
| `MAIL_FROM_ADDRESS` | `seu@email.com` | Remetente |
| `MAIL_FROM_NAME` | `Checklist APP` | Nome do remetente |
| `MAIL_TIMEOUT` | `120` | Timeout em segundos |

### Logging

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `LOG_CHANNEL` | `stack` | Canal de log |
| `LOG_STACK` | `single` | Stack de log |
| `LOG_LEVEL` | `error` | Nível mínimo de log |

### Segurança

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `BCRYPT_ROUNDS` | `12` | Rounds do Bcrypt |
| `PHP_CLI_SERVER_WORKERS` | `4` | Workers do PHP CLI |

---

## Passo 5: Volumes Persistentes

Em **Storages → Add Volume**, adicione:

| Source (host) | Destination (container) | Descrição |
|---------------|-------------------------|-----------|
| `/data/coolify/checklist/storage` | `/var/www/html/storage/app` | Arquivos enviados pelos usuários |

> Isso garante que uploads não sejam perdidos quando o container é recriado.

---

## Passo 6: Deploy

1. Clique em **Deploy** no Coolify
2. Acompanhe os logs de build
3. O entrypoint vai automaticamente:
   - Gerar a `APP_KEY` (se não definida)
   - Criar o storage link
   - Rodar as migrations
   - Rodar os seeders (se `RUN_SEEDERS=true`)
   - Cachear configurações, rotas e views
   - Iniciar Nginx + PHP-FPM + Queue Workers + Cron

---

## Passo 7: Após o Primeiro Deploy

### 7.1 Alterar RUN_SEEDERS

Após o primeiro deploy bem-sucedido, mude a variável:
- `RUN_SEEDERS` → `false`

Isso evita que os seeders rodem novamente em deploys futuros.

### 7.2 Credenciais do Super Admin

O seeder cria automaticamente um super admin:

| Campo | Valor |
|-------|-------|
| Email | `admin@checklist.com` |
| Senha | `admin123` |

**Altere a senha imediatamente após o primeiro login.**

### 7.3 Testar a API

```bash
# Login com super admin
curl -X POST https://checklist.seudominio.com/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@checklist.com","password":"admin123"}'

# Usar o token retornado
curl https://checklist.seudominio.com/api/v1/auth/me \
  -H 'Authorization: Bearer SEU_TOKEN_AQUI'
```

### 7.4 Criar primeiro cliente

```bash
TOKEN="seu_token_aqui"

curl -X POST https://checklist.seudominio.com/api/v1/clients \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"name":"Minha Empresa","document":"12345678000199","email":"contato@empresa.com"}'
```

---

## Comandos Úteis

Acesse o terminal do container pelo Coolify (**Terminal** tab) ou via SSH:

```bash
# Ver logs da aplicação
tail -f /var/www/html/storage/logs/laravel.log

# Ver status dos workers
supervisorctl status

# Reiniciar workers (após mudanças em Jobs)
supervisorctl restart queue-worker:*

# Rodar migrations manualmente
php /var/www/html/artisan migrate --force

# Rodar seeders manualmente
php /var/www/html/artisan db:seed --force

# Limpar caches
php /var/www/html/artisan optimize:clear

# Verificar saúde
curl http://localhost/up
```

---

## Troubleshooting

### Container não inicia

1. Verifique os logs de build no Coolify
2. Verifique se o MySQL está acessível pelo hostname interno
3. Teste a conexão: `php artisan db:monitor`

### Erro 502 Bad Gateway

O PHP-FPM pode não ter iniciado. Verifique:
```bash
supervisorctl status php-fpm
cat /var/log/supervisor/supervisord.log
```

### Migrations falham

Verifique se o banco existe e as credenciais estão corretas:
```bash
php /var/www/html/artisan db:show
```

### Uploads não funcionam

Verifique o volume persistente e permissões:
```bash
ls -la /var/www/html/storage/app/
```

### E-mails não enviam

Verifique as credenciais SMTP e os logs do worker:
```bash
tail -f /var/www/html/storage/logs/worker.log
```

### Fila parada

```bash
supervisorctl restart queue-worker:*
```

---

## Teste Local com Docker Compose

Para testar localmente antes de enviar para o Coolify:

```bash
# Build e iniciar todos os serviços
docker compose up -d --build

# Ver logs
docker compose logs -f app

# Acessar: http://localhost

# Parar tudo
docker compose down

# Parar e remover volumes (limpa dados)
docker compose down -v
```

---

## Estrutura dos Arquivos Docker

```
├── Dockerfile              # Build multi-stage (Node + PHP + Nginx)
├── .dockerignore           # Arquivos ignorados no build
├── docker-compose.yml      # Para teste local
└── docker/
    ├── nginx.conf          # Configuração do Nginx
    ├── php.ini             # Configurações otimizadas do PHP
    ├── www.conf            # Pool PHP-FPM
    ├── supervisord.conf    # Gerenciador de processos
    └── entrypoint.sh       # Script de inicialização
```
