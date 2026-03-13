# Guia de Implantação em Produção - Windows Server com IIS

Este guia fornece instruções detalhadas para colocar o projeto API Checklist em produção em um servidor Windows com IIS.

## Pré-requisitos

- Windows Server 2019 ou superior
- PHP 8.1 ou superior para Windows
- Composer 2.0 ou superior
- MySQL 8.0 ou SQL Server
- Node.js 16.x ou superior e NPM
- Git para Windows
- Internet Information Services (IIS) 10 ou superior
- URL Rewrite Module 2.1 para IIS
- PHP Manager para IIS
- Certificado SSL (recomendado)

## 1. Configuração do Servidor Windows

### 1.1 Instalar o IIS
1. Abra o **Gerenciador do Servidor**
2. Clique em **Adicionar funções e recursos**
3. Selecione **Instalação baseada em função ou recurso**
4. Selecione o servidor e clique em **Avançar**
5. Marque **Servidor Web (IIS)** e clique em **Avançar**
6. Nas funcionalidades, adicione:
   - .NET Extensibility 4.7+
   - ASP.NET 4.7+
   - Extensibilidade do .NET 4.7+
   - ISAPI Extensions
   - ISAPI Filters
   - Console de Gerenciamento do IIS
   - Autenticação do Windows
   - Compactação de Conteúdo Dinâmico
   - Logs de Diagnóstico
   - Log de HTTP
   - Monitor de Solicitações
   - Conteúdo Estático
   - Documento Padrão
   - Erros de HTTP
   - Redirecionamento de HTTP
   - Mapeamento de Tipos de Mídia
   - Módulos HTTP
   - Compressão de Conteúdo Estático
   - Autorização baseada em URL
   - Filtragem de Solicitações
7. Conclua o assistente de instalação

### 1.2 Instalar o PHP para Windows
1. Baixe o PHP 8.1+ para Windows (Thread Safe) do site oficial
2. Extraia o conteúdo para `C:\PHP`
3. Renomeie `php.ini-development` para `php.ini`
4. Edite o `php.ini` e descomente as extensões necessárias:
   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=pdo_sqlsrv
   extension=sqlsrv
   ```
5. Adicione o PHP ao PATH do sistema
6. Teste a instalação com `php -v`

### 1.3 Instalar o PHP Manager para IIS
1. Baixe o PHP Manager para IIS do site oficial
2. Execute o instalador e siga as instruções

### 1.4 Instalar o URL Rewrite Module
1. Baixe o URL Rewrite Module 2.1 para IIS
2. Execute o instalador e siga as instruções

### 1.5 Configurar o Banco de Dados
1. Instale o MySQL Server para Windows ou use um servidor existente
2. Crie um banco de dados chamado `checklist_prod`
3. Crie um usuário com permissões adequadas
4. Teste a conexão com o banco de dados

## 2. Implantação do Código

### 2.1 Clonar o repositório
1. Abra o **Prompt de Comando** como administrador
2. Navegue até a pasta onde o site será hospedado (geralmente `C:\inetpub\`)
3. Execute:
   ```cmd
   git clone https://seu-repositorio.git checklist
   cd checklist
   ```

### 2.2 Instalar as dependências do PHP
1. Abra o **Prompt de Comando** como administrador
2. Navegue até a pasta do projeto
3. Execute:
   ```cmd
   composer install --optimize-autoloader --no-dev
   ```

### 2.3 Instalar as dependências do Node e compilar os assets
1. Instale o Node.js para Windows
2. Abra o **Prompt de Comando** como administrador
3. Navegue até a pasta do projeto
4. Execute:
   ```cmd
   npm install
   npm run prod
   ```

## 3. Configuração do Ambiente

### 3.1 Configurar o arquivo .env
1. Copie o arquivo `.env.example` para `.env`
2. Edite o arquivo `.env` com as configurações do seu ambiente:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://seu-dominio.com
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=checklist_prod
   DB_USERNAME=seu_usuario
   DB_PASSWORD=sua_senha_segura
   
   MAIL_MAILER=smtp
   MAIL_HOST=seu-smtp
   MAIL_PORT=587
   MAIL_USERNAME=seu-email@dominio.com
   MAIL_PASSWORD=sua-senha
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=seu-email@dominio.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

### 3.2 Gerar chave da aplicação
1. Abra o **Prompt de Comando** como administrador
2. Navegue até a pasta do projeto
3. Execute:
   ```cmd
   php artisan key:generate
   ```

### 3.3 Executar migrações e seeders
1. No **Prompt de Comando** como administrador, na pasta do projeto:
   ```cmd
   php artisan migrate --force
   php artisan db:seed --force
   ```

### 3.4 Criar link simbólico para armazenamento
```cmd
php artisan storage:link
```

### 3.5 Configurar permissões
1. Clique com o botão direito na pasta do projeto
2. Vá em **Propriedades** > **Segurança**
3. Adicione o usuário `IIS_IUSRS` com permissão de **Leitura e Execução**
4. Para as pastas `storage` e `bootstrap/cache`, dê permissões de **Modificação** ao usuário `IUSR` e `IIS_IUSRS`

## 4. Configuração do IIS

### 4.1 Criar um novo site no IIS
1. Abra o **Gerenciador do IIS**
2. Clique com o botão direito em **Sites** e selecione **Adicionar Site**
3. Preencha os campos:
   - Nome do site: `Checklist`
   - Caminho físico: `C:\inetpub\checklist\public`
   - Vincular: Especifique a porta (80 para HTTP, 443 para HTTPS)
   - Nome do host: `seu-dominio.com`
4. Clique em **OK**

### 4.2 Configurar o FastCGI para PHP
1. No **Gerenciador do IIS**, selecione o servidor
2. Abra **Mapeamento de Aplicativo**
3. Clique em **Configurar Mapeamentos de Scripts**
4. Adicione um novo mapeamento:
   - Caminho do executável: `C:\PHP\php-cgi.exe`
   - Extensão: `.php`
   - Verbo: `GET,HEAD,POST`
   - Nome: `PHP_via_FastCGI`

### 4.3 Configurar o web.config
Crie ou edite o arquivo `web.config` na pasta `public` com o seguinte conteúdo:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="Imported Rule 1" stopProcessing="true">
                    <match url="^" ignoreCase="false" />
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php" appendQueryString="true" />
                </rule>
            </rules>
        </rewrite>
        <httpErrors errorMode="Detailed" />
        <directoryBrowse enabled="false" />
    </system.webServer>
</configuration>
```

### 4.4 Configurar o PHP Manager
1. No **Gerenciador do IIS**, clique no seu site
2. Abra o **PHP Manager**
3. Clique em "Check phpinfo()" para verificar se o PHP está funcionando corretamente
4. Configure as extensões necessárias no PHP Manager

### 4.5 Configurar Pool de Aplicativos
1. No **Gerenciador do IIS**, selecione **Pools de Aplicativos**
2. Clique com o botão direito no pool do seu site e selecione **Configurações Básicas**
3. Altere a versão do .NET CLR para "Sem Código Gerenciado"
4. Defina o Modo de Pipeline Gerenciado como "Integrado"
5. Clique em **OK**

## 5. Configuração das Filas no Windows

### 5.1 Usando o Agendador de Tarefas do Windows
1. Abra o **Agendador de Tarefas**
2. Clique em **Criar Tarefa**
3. Na aba **Geral**:
   - Nome: `Checklist Queue Worker`
   - Marque "Executar com privilégios mais altos"
4. Na aba **Gatilhos**:
   - Clique em **Novo**
   - Selecione "Ao iniciar"
   - Marque "Ativada"
5. Na aba **Ações**:
   - Clique em **Novo**
   - Ação: "Iniciar um programa"
   - Programa/script: `C:\PHP\php.exe`
   - Adicionar argumentos: `C:\inetpub\checklist\artisan queue:work --tries=3 --timeout=90`
   - Iniciar em: `C:\inetpub\checklist`
6. Na aba **Configurações**:
   - Desmarque "Parar a tarefa se ela durar mais de..."
   - Marque "Se a tarefa não for agendada para ser executada novamente, exclua-a depois de..."
   - Marque "Executar tarefa assim que for agendada"
   - Marque "Reiniciar a cada 1 hora"

### 5.2 Usando um arquivo em lote (alternativa)
1. Crie um arquivo `start-worker.bat` na pasta do projeto:
   ```batch
   @echo off
   :start
   C:\PHP\php.exe C:\inetpub\checklist\artisan queue:work --tries=3 --timeout=90
   timeout /t 10
   goto start
   ```
2. Crie um atalho para este arquivo na pasta de Inicialização do Windows

### 5.3 Verificando as filas
1. Para verificar o status das filas:
   ```cmd
   cd C:\inetpub\checklist
   php artisan queue:listen
   ```
2. Para processar as filas em segundo plano:
   ```cmd
   start /B php C:\inetpub\checklist\artisan queue:work --daemon
   ```

## 6. Configuração do Agendador de Tarefas no Windows

### 6.1 Usando o Agendador de Tarefas do Windows
1. Abra o **Agendador de Tarefas**
2. Clique em **Criar Tarefa**
3. Na aba **Geral**:
   - Nome: `Checklist Scheduler`
   - Marque "Executar com privilégios mais altos"
4. Na aba **Gatilhos**:
   - Clique em **Novo**
   - Selecione "Diariamente"
   - Repetir a tarefa a cada: 1 minuto
   - Duração: Indefinidamente
   - Marque "Ativada"
5. Na aba **Ações**:
   - Clique em **Novo**
   - Ação: "Iniciar um programa"
   - Programa/script: `C:\PHP\php.exe`
   - Adicionar argumentos: `C:\inetpub\checklist\artisan schedule:run`
   - Iniciar em: `C:\inetpub\checklist`
6. Na aba **Configurações**:
   - Marque "Executar tarefa assim que for agendada"
   - Marque "Reiniciar a cada 1 hora"
   - Marque "Parar a tarefa se ela durar mais de: 1 hora"

### 6.2 Usando um arquivo em lote (alternativa)
1. Crie um arquivo `scheduler.bat` na pasta do projeto:
   ```batch
   @echo off
   :loop
   C:\PHP\php.exe C:\inetpub\checklist\artisan schedule:run
   timeout /t 60 /nobreak
   goto loop
   ```
2. Crie um atalho para este arquivo na pasta de Inicialização do Windows

## 7. Otimizações Finais

### 7.1 Otimizações de Desempenho
1. Abra o **Prompt de Comando** como administrador
2. Navegue até a pasta do projeto (`C:\inetpub\checklist`)
3. Execute os seguintes comandos:
   ```cmd
   rem Cache de configuração
   php artisan config:cache
   
   rem Cache de rotas
   php artisan route:cache
   
   rem Cache de views
   php artisan view:cache
   
   rem Otimização do Composer
   composer dump-autoload --optimize
   
   rem Otimização do carregador automático
   php artisan optimize
   ```

### 7.2 Configurações Adicionais do PHP
1. Edite o arquivo `php.ini` (geralmente em `C:\PHP\php.ini`)
2. Ajuste as seguintes configurações para melhor desempenho:
   ```ini
   ; Aumentar o limite de memória
   memory_limit = 256M
   
   ; Habilitar o cache de opcode
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   opcache.validate_timestamps=0
   opcache.revalidate_freq=0
   
   ; Configurações de upload
   upload_max_filesize = 10M
   post_max_size = 12M
   
   ; Habilitar extensões necessárias
   extension=fileinfo
   extension=gd
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   ```

### 7.3 Configurações do IIS para Desempenho
1. No **Gerenciador do IIS**, selecione o site
2. Abra **Filtragem de Solicitações**
3. Clique em **Definir Limites de Solicitações**
4. Ajuste os valores conforme necessário:
   - Tamanho máximo de conteúdo: 30000000 (30MB)
   - Tamanho máximo da URL: 4096
   - Tamanho máximo da sequência de consulta: 2048

### 7.4 Compactação de Conteúdo
1. No **Gerenciador do IIS**, selecione o servidor
2. Abra **Compactação**
3. Marque "Habilitar compactação de conteúdo estático"
4. Marque "Habilitar compactação de conteúdo dinâmico"
5. Clique em **Aplicar**

## 8. Configuração do Certificado SSL no Windows Server

### 8.1 Usando o Certify The Web (Recomendado)
1. Baixe e instale o **Certify The Web** do site oficial
2. Abra o aplicativo e clique em **New Certificate**
3. Configure o domínio (ex: `seu-dominio.com` e `www.seu-dominio.com`)
4. Selecione o site no IIS
5. Clique em **Request Certificate**
6. Configure a renovação automática nas configurações do aplicativo

### 8.2 Configuração Manual no IIS
1. No **Gerenciador do IIS**, selecione o site
2. Clique em **Associações** no painel de ações à direita
3. Adicione uma nova associação:
   - Tipo: https
   - Porta: 443
   - Certificado SSL: Selecione o certificado SSL instalado
4. Clique em **OK**

### 8.3 Redirecionamento HTTP para HTTPS
1. Instale o **URL Rewrite Module** se ainda não estiver instalado
2. No **Gerenciador do IIS**, selecione o site
3. Abra o **URL Rewrite**
4. Adicione uma nova regra de redirecionamento:
   - Padrão: `(.*)`
   - Condição: `{HTTPS} off`
   - Ação: Redirect
   - URL: `https://{HTTP_HOST}/{R:1}`
   - Redirecionamento permanente: Sim

## 9. Backup Automatizado no Windows Server

### 9.1 Usando o Agendador de Tarefas do Windows
1. Crie um script `backup-checklist.bat` na pasta do projeto:
   ```batch
   @echo off
   set BACKUP_DIR=C:\Backup\Checklist
   set DATE=%DATE:/=-%
   set TIME=%TIME::=-%
   set TIMESTAMP=%DATE%_%TIME%
   
   rem Criar diretório de backup se não existir
   if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
   
   rem Fazer backup do banco de dados
   "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe" -u usuario -psenha checklist_prod > "%BACKUP_DIR%\backup_%TIMESTAMP%.sql"
   
   rem Compactar o backup (requer 7-Zip instalado)
   "C:\Program Files\7-Zip\7z.exe" a -tzip "%BACKUP_DIR%\backup_%TIMESTAMP%.zip" "%BACKUP_DIR%\backup_%TIMESTAMP%.sql"
   
   rem Remover o arquivo SQL após compactar
   del "%BACKUP_DIR%\backup_%TIMESTAMP%.sql"
   
   rem Manter apenas os últimos 7 backups
   for /f "skip=7 delims=" %%F in ('dir /b /a-d /o-d "%BACKUP_DIR%\backup_*.zip" 2^>nul') do del "%BACKUP_DIR%\%%F"
   
   echo Backup concluído em %DATE% %TIME% >> "%BACKUP_DIR%\backup.log"
   ```

2. Crie uma tarefa no Agendador de Tarefas para executar o backup diariamente:
   - Abra o **Agendador de Tarefas**
   - Crie uma nova tarefa básica
   - Configure para executar diariamente às 2:00
   - Ação: Iniciar um programa
   - Programa: `C:\caminho\para\backup-checklist.bat`
   - Marque "Executar com privilégios mais altos"

## 10. Monitoramento no Windows Server

### 10.1 Monitoramento do Laravel
1. **Laravel Horizon** (para monitoramento de filas):
   - Instale com `composer require laravel/horizon`
   - Publique os assets com `php artisan horizon:install`
   - Acesse em `https://seu-dominio.com/horizon`

2. **Laravel Telescope** (para depuração em desenvolvimento):
   - Instale com `composer require laravel/telescope --dev`
   - Publique os assets com `php artisan telescope:install`
   - Execute as migrações: `php artisan migrate`
   - Acesse em `https://seu-dominio.com/telescope`

### 10.2 Monitoramento do Windows Server
1. **Monitor de Desempenho do Windows**:
   - Abra o **Monitor de Desempenho** (perfmon.msc)
   - Configure conjuntos de coletores para monitorar:
     - Uso da CPU
     - Memória disponível
     - Disco
     - Rede
     - Processos do IIS

2. **Logs de Eventos do Windows**:
   - Verifique os logs de aplicativo e sistema regularmente
   - Configure alertas para erros críticos

## Solução de Problemas Comuns

### Verificar Logs
1. **Logs do Laravel**:
   - `C:\inetpub\checklist\storage\logs\laravel.log`
   - Use o comando `Get-Content -Path "C:\inetpub\checklist\storage\logs\laravel.log" -Wait` para acompanhar em tempo real

2. **Logs do IIS**:
   - `C:\inetpub\logs\LogFiles\W3SVC1\`
   - Use o **Visualizador de Eventos** para logs detalhados do IIS

### Verificar Permissões
1. Verifique se o usuário `IUSR` e `IIS_IUSRS` têm permissões de leitura/gravação nas pastas:
   - `C:\inetpub\checklist\storage`
   - `C:\inetpub\checklist\bootstrap\cache`

### Limpar Caches
Abra o **Prompt de Comando** como administrador e execute:
```cmd
cd C:\inetpub\checklist
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan optimize
```

## Conclusão

Sua aplicação API Checklist agora está configurada para produção no Windows Server com IIS! Lembre-se de:

1. Manter o Windows Server atualizado
2. Monitorar regularmente os logs e o desempenho
3. Realizar backups periódicos do banco de dados e da aplicação
4. Manter o certificado SSL renovado
5. Acompanhar as atualizações de segurança do Laravel e do PHP

Para atualizações futuras:
1. Faça um backup completo antes de qualquer alteração
2. Teste as alterações em um ambiente de homologação
3. Utilize um sistema de controle de versão (Git) para gerenciar as atualizações
4. Considere implementar um pipeline de CI/CD para automatizar o processo de implantação
