<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist Finalizado</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.09);
            padding: 32px 32px 24px 32px;
        }
        .header {
            background: #1a237e;
            color: #fff;
            padding: 24px 0 16px 0;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .header img {
            height: 48px;
            margin-bottom: 8px;
        }
        .title {
            font-size: 1.7em;
            margin-bottom: 0.5em;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 28px 0 24px 0;
        }
        .info-table th, .info-table td {
            text-align: left;
            padding: 10px 12px;
        }
        .info-table th {
            background: #f4f6fa;
            color: #1a237e;
            width: 180px;
            font-weight: 600;
        }
        .info-table td {
            background: #f9fafc;
        }
        .footer {
            margin-top: 32px;
            color: #999;
            font-size: 0.95em;
            text-align: center;
        }
        .cta {
            display: inline-block;
            margin: 24px 0 0 0;
            padding: 12px 28px;
            background: #1a237e;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1em;
            letter-spacing: 0.03em;
            transition: background 0.2s;
        }
        .cta:hover {
            background: #3949ab;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Se quiser, adicione o logo aqui -->
            <img src="https://inlog.com.br/wp-content/uploads/2022/07/logo-inlog.png" alt="Inlog Logo">
            <div class="title">Checklist Finalizado com Sucesso</div>
        </div>
        <p style="font-size:1.13em; margin-top:28px;">Olá,</p>
        <p style="font-size:1.13em;">O checklist <strong>{{ $application->checklist->title ?? '-' }}</strong> foi finalizado e está disponível para consulta.</p>
        <table class="info-table">
            <tr>
                <th>Status da aplicação</th>
                <td style="color:#388e3c;font-weight:600;">{{ ucfirst($application->status) }}</td>
            </tr>
            <tr>
                <th>Data de conclusão</th>
                <td>{{ \Carbon\Carbon::parse($application->completed_at)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <th>Número da aplicação</th>
                <td>{{ $application->numero_formatado ?? $application->number }}</td>
            </tr>
            <tr>
                <th>Checklist</th>
                <td>{{ $application->checklist->title ?? '-' }}</td>
            </tr>
        </table>
        <a class="cta" href="#">Visualizar Checklist</a>
        <div class="footer">
            Atenciosamente,<br>
            <strong>Equipe Inlog</strong><br>
            <span style="font-size:0.9em; color:#bbb;">Este é um e-mail automático. Por favor, não responda.</span>
        </div>
    </div>
</body>
</html>
