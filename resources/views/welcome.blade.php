<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Documentação da API - Anexos de Respostas</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      padding: 20px;
      background-color: #f9f9f9;
      color: #333;
    }
    h1, h2, h3 {
      color: #005a9c;
    }
    code, pre {
      background-color: #f1f1f1;
      padding: 10px;
      display: block;
      border-radius: 4px;
      overflow-x: auto;
    }
    .endpoint {
      margin-bottom: 40px;
    }
  </style>
</head>
<body>
  <h1>📦 API de Anexos</h1>

  <h2>🔐 Autenticação</h2>
  <p>Requisições devem conter o token de autenticação Bearer:</p>
  <pre><code>Authorization: Bearer {seu_token}</code></pre>

  <div class="endpoint">
    <h2>📤 POST /attachments/{type}/{referenceId}</h2>
    <p><strong>Descrição:</strong> Envia um anexo relacionado a uma <code>resposta</code> ou <code>opção de resposta</code>.</p>
    <p><strong>Parâmetros de rota:</strong></p>
    <ul>
      <li><code>type</code>: <code>answer</code> ou <code>option</code></li>
      <li><code>referenceId</code>: UUID da resposta ou da opção</li>
    </ul>
    <p><strong>Body (form-data):</strong></p>
    <ul>
      <li><code>file</code>: Arquivo até 100MB (jpg, pdf, mp4, etc.)</li>
    </ul>
    <pre><code>curl -X POST https://api.exemplo.com/attachments/option/UUID \
  -H "Authorization: Bearer {token}" \
  -F "file=@/caminho/arquivo.pdf"</code></pre>
    <h3>Resposta 201</h3>
    <pre><code>{
  "message": "Anexo recebido com sucesso.",
  "attachment": {
    "id": "uuid",
    "file_name": "arquivo.pdf",
    "status": "pending_compression"
  }
}</code></pre>
  </div>

  <div class="endpoint">
    <h2>📥 GET /attachments/{type}/{referenceId}</h2>
    <p><strong>Descrição:</strong> Lista todos os anexos relacionados a uma <code>resposta</code> ou <code>opção de resposta</code>.</p>
    <p><strong>Parâmetros de rota:</strong></p>
    <ul>
      <li><code>type</code>: <code>answer</code> ou <code>option</code></li>
      <li><code>referenceId</code>: UUID de referência</li>
    </ul>
    <h3>Resposta 200</h3>
    <pre><code>{
  "attachments": [
    {
      "id": "uuid",
      "file_name": "foto.jpg",
      "file_path": "attachments/foto.jpg",
      "original_size": 202400,
      "compressed_size": null,
      "status": "pending_compression",
      "url": "https://seusite.com/storage/attachments/foto.jpg",
      "created_by": "usuario",
      "created_at": "2025-06-02 10:30:45"
    }
  ]
}</code></pre>
  </div>

  <div class="endpoint">
    <h2>🔍 GET /attachments/{id}</h2>
    <p><strong>Descrição:</strong> Retorna os detalhes de um anexo, independente da origem.</p>
    <h3>Resposta 200</h3>
    <pre><code>{
  "id": "uuid",
  "file_name": "video.mp4",
  "file_path": "attachments/video.mp4",
  "original_size": 10240000,
  "compressed_size": 4021000,
  "status": "compressed",
  "url": "https://seusite.com/storage/attachments/video.mp4",
  "created_by": "usuario",
  "created_at": "2025-06-02 10:30:45"
}</code></pre>
  </div>

  <div class="endpoint">
    <h2>❌ DELETE /attachments/{id}</h2>
    <p><strong>Descrição:</strong> Remove um anexo do sistema e do armazenamento.</p>
    <h3>Resposta 200</h3>
    <pre><code>{
  "message": "Anexo excluído com sucesso."
}</code></pre>
  </div>

  <div class="endpoint">
    <h2>ℹ️ Observações</h2>
    <ul>
      <li>Use <code>type=option</code> para anexos vinculados a <strong>opções de resposta</strong>.</li>
      <li>Use <code>type=answer</code> para anexos vinculados diretamente a <strong>respostas</strong>.</li>
      <li>As rotas <code>GET /attachments/{id}</code> e <code>DELETE /attachments/{id}</code> funcionam para qualquer tipo de anexo.</li>
    </ul>
  </div>

</body>
</html>
