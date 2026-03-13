
# 📦 Laravel - Dependências de Compressões de Arquivos

Este documento descreve as ferramentas necessárias para realizar a compressão automática de **vídeos, PDFs, imagens e arquivos de texto** no Laravel de forma local e gratuita.

---

## ✅ Etapa 1: Instalações no Servidor (Windows)

### 🛠️ FFmpeg - Compressão de Vídeos

1. Acesse: https://ffmpeg.org/download.html  
2. Vá para **Windows > builds by Gyan.dev**
3. Baixe o `.zip` da versão `essentials`
4. Extraia o conteúdo em:  
   `C:\Users\<seu-usuario>\ffmpeg\ffmpeg-XXXX-XX-XX-essentials_build`
5. Copie o caminho da pasta `bin`, por exemplo:  
   `C:\Users\anthony.feliciano\ffmpeg\ffmpeg-2025-05-19-git-xxxx\bin`
6. Adicione à variável de ambiente `Path`
7. Teste no CMD:

```
ffmpeg -version
```

---

### 🛠️ Ghostscript - Compressão de PDFs

1. Acesse: https://ghostscript.com/download/gsdnld.html  
2. Baixe e instale a versão para Windows
3. Teste no CMD:

```
gswin64c --version
```

> ⚠️ Em sistemas Linux/Mac, o comando será apenas `gs`

---

### 🛠️ Intervention Image - Compressão de Imagens

Instale via Composer:

```
composer require intervention/image
```

Se usar Laravel <10, adicione manualmente em `config/app.php`:

```php
'providers' => [
    Intervention\Image\ImageServiceProvider::class,
],

'aliases' => [
    'Image' => Intervention\Image\Facades\Image::class,
],
```

---

## ⚙️ Como funciona no Laravel

Laravel detecta o tipo do arquivo e aplica compressão:

| Tipo de Arquivo | Compressão Aplicada          | Ferramenta Usada       |
|-----------------|------------------------------|------------------------|
| Imagens (JPG/PNG) | Redução para 70% de qualidade | Intervention Image     |
| Vídeos (MP4/AVI) | Conversão para H.264 (CRF 28) | FFmpeg                 |
| PDFs             | Otimização visual leve        | Ghostscript (gswin64c) |
| CSV/XML/TXT      | Compactação GZIP              | Função `gzencode()`    |

---

## ✅ Finalize

Após instalar as ferramentas, você poderá usar seu controller Laravel para comprimir arquivos automaticamente ao serem enviados no checklist.
