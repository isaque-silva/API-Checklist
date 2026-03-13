@php use Picqer\Barcode\BarcodeGeneratorPNG; @endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Checklist Finalizado - PDF</title>
    <style>
        @page {
            margin: 80px 25px 50px 25px;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            background: #fff;
            font-size: 13px;
            margin: 0;
            padding: 0;
            position: relative;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 0 !important;
        }
        th, td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .divider {
            border-bottom: 2px solid #bdbdbd;
            height: 2px;
        }
        .section-title {
            background: #ededed;
            font-weight: bold;
            padding: 4px 6px;
            border-bottom: 1px solid #bdbdbd;
            font-size: 14px;
            text-transform: uppercase;
        }
        .obs {
            color: #222;
            font-size: 12px;
        }
        .gray-bg {
            background: #ededed;
        }

        @page {
            margin-bottom: 50px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>
<body style="margin: 0; padding: 0;">
    <table style="width:100%; font-family: Arial, sans-serif; font-size:11px; margin:0; padding:0; border-collapse:collapse;">
        <tr>
            <!-- Logotipo e texto lateral -->
            <td style="vertical-align:top; width:70%; padding:0; margin:0;">
                <table style="width:100%; border-collapse:collapse; margin:0; padding:0; border-spacing:0;">
                    <tr>
                        <td rowspan="5" style="vertical-align:bottom; padding:0 15px 0 0; margin:0; white-space:nowrap;">
                            @php
                                $logoPath = public_path('images/logo.png');
                                $logoData = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
                            @endphp
                            <div style="line-height:1.5; margin:0;">
                                @if(file_exists($logoPath))
                                    <img src="{{ $logoData }}" alt="Logo INLOG" style="height: 70px; width: auto;">
                                @endif
                            </div>
                        </td>
                        <td style="font-weight:bold; margin:0; padding:0; white-space:nowrap; line-height:1.3; font-size: 12px;">TRANSPARE TRANSPORTES ARMAZÉNS GERAIS LTDA</td>
                    </tr>
                    <tr>
                        <td style="margin:0; padding:0; white-space:nowrap; font-size:10.5px; line-height:1.3;">RUA JOSE GERALDINO BITTENCOURT, S/N - PEDRA DE AMOLAR</td>
                    </tr>
                    <tr>
                        <td style="margin:0; padding:0; white-space:nowrap; font-size:10.5px; line-height:1.3;">CEP: 88324-360 - ILHOTA/SC</td>
                    </tr>
                    <tr>
                        <td style="margin:0; padding:0; white-space:nowrap; font-size:10.5px; line-height:1.3;">CNPJ: 06.009.235/0003-92 IE: 257711538 &nbsp;&nbsp;&nbsp; Fone: 47 3343 7864</td>
                    </tr>
                </table>
            </td>

            <!-- Código de barras e checklist -->
            <td style="text-align:right; vertical-align:bottom; width:30%; padding:0; margin:0;">
                <div style="text-align:right; margin:0; padding:0; line-height:1.3;">
                    @php
                        $barcodeNumber = (string)($application->number ?? '0');
                        $barcodeHtml = '';
                        
                        // Verifica se a classe do gerador de código de barras está disponível
                        if (class_exists('Picqer\\Barcode\\BarcodeGeneratorPNG')) {
                            try {
                                $generator = new Picqer\Barcode\BarcodeGeneratorPNG();
                                $barcode = $generator->getBarcode($barcodeNumber, $generator::TYPE_CODE_128, 2, 40);
                                $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcode);
                                $barcodeHtml = '<img src="' . $barcodeBase64 . '" alt="Código de Barras: ' . e($barcodeNumber) . '" style="height:40px; margin:0; padding:0; display:block;">';
                            } catch (\Exception $e) {
                                // Em caso de erro, mostra apenas o número
                                $barcodeHtml = '<div style="font-size:12px; color:red; margin:5px 0;">' . e($barcodeNumber) . '</div>';
                            }
                        } else {
                            // Se a biblioteca não estiver disponível, mostra apenas o número
                            $barcodeHtml = '<div style="font-size:12px; color:red; margin:5px 0;">' . e($barcodeNumber) . '</div>';
                        }
                    @endphp
                    {!! $barcodeHtml !!}
                    <div style="font-size:12px; font-weight:bold; margin:0; padding:0; line-height:1.3;">CHECKLIST Nr. {{ $barcodeNumber }}</div>
                </div>
            </td>
        </tr>
    </table>
    <div style="border-bottom: 1px solid #000;"></div>
    <table style="width:100%; font-size:12px; border-collapse:collapse;">
        <tr>
            <!-- Coluna da esquerda -->
            <td style="vertical-align: top; width:50%;">
                <table style="width:100%; font-size:12px; margin:0; padding:0;">
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Data:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ \Carbon\Carbon::parse($application->created_at)->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Checklist Nr:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ $application->number ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Checklist:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ $application->checklist->title ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Coluna da direita -->
            <td style="vertical-align: top; width:50%;">
                <table style="width:100%; font-size:12px; margin:0; padding:0;">
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Início:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ \Carbon\Carbon::parse($application->applied_at)->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Término:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ \Carbon\Carbon::parse($application->completed_at)->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width:100px; padding:0; margin:0;">Executado por:</td>
                        <td style="text-align:left; padding:0 0 0 4px; margin:0;">
                            {{ $application->user->name ?? $application->updated_by ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <div style="border-bottom: 1px solid #000;"></div>
    <table style="width:100%; font-size:12px; border-collapse:collapse; margin:10px 0 0 0; padding:0; border-spacing:0;">
        @foreach($application->checklist->areas as $area)
            <tr style="background:#ededed;">
                <td colspan="3" style="font-weight:bold; font-size:14px; padding:6px 4px; text-transform:uppercase; text-align:center;">{{ $area->title }}</td>
            </tr>
            @foreach($area->items as $item)
    @php
        $answer = $application->answers->firstWhere('checklist_item_id', $item->id);
        $type = $item->type->name ?? null;
    @endphp
    <tr style="background: {{ $loop->even ? '#fefeff' : '#f0f9fe' }};">
        <td style="font-weight:bold; width:38%;">{{ $item->name }}</td>
        <td style="width:40%;">
            @if ($answer)
                @switch($type)
                    @case('texto')
                        <span>{{ $answer->response_text ?? '—' }}</span>
                        @break
                    @case('numero')
                        @php
                            $formattedNumber = $answer->response_number !== null 
                                ? number_format($answer->response_number, 4, ',', '.') 
                                : '—';
                        @endphp
                        <span>{{ $formattedNumber }}</span>
                        @break
                    @case('data')
                        @php
                            $dateFormat = 'd/m/Y'; // Formato padrão
                            
                            // Verifica se o item tem uma máscara definida
                            if ($item->relationLoaded('mask') && $item->mask && $item->mask->label) {
                                $mask = strtolower($item->mask->label);
                                
                                // Mapeia as máscaras para os formatos do Carbon
                                $formatMap = [
                                    'dd/mm/aaaa' => 'd/m/Y',
                                    'hh:mm' => 'H:i',
                                    'mm/aaaa' => 'm/Y',
                                    'hh:mm:ss' => 'H:i:s',
                                    'dd/mm/yyyy hh:mm' => 'd/m/Y H:i',
                                    'dd/mm/yyyy hh:mm:ss' => 'd/m/Y H:i:s',
                                ];
                                
                                if (array_key_exists($mask, $formatMap)) {
                                    $dateFormat = $formatMap[$mask];
                                }
                            }
                            
                            $formattedDate = $answer->response_datetime 
                                ? \Carbon\Carbon::parse($answer->response_datetime)->format($dateFormat)
                                : '—';
                        @endphp
                        <span>{{ $formattedDate }}</span>
                        @break
                    @case('selecao')
                        @if(isset($answer->options) && $answer->options->where('is_selected', 1)->count())
                            @foreach($answer->options->where('is_selected', 1) as $option)
                                @php
                                    $sel = \App\Models\Chk\SelectionOption::find($option->option_id);
                                @endphp
                                @if($sel)
                                    <div style="margin: 2px 0;">{{ $sel->value }}</div>
                                @endif
                            @endforeach
                        @else
                            {{ $answer->selectedOption->value ?? '—' }}
                        @endif
                        @break
                    @case('avaliativo')
                        @if(isset($answer->options) && $answer->options->where('type', 'evaluative')->where('is_selected', 1)->count())
                            {{ $answer->options->where('type', 'evaluative')->where('is_selected', 1)->map(function($option) {
                                $itemEvalOpt = \App\Models\Chk\ItemEvalOption::find($option->option_id);
                                if ($itemEvalOpt && $itemEvalOpt->eval_option_id) {
                                    $evalOpt = \App\Models\Chk\EvalOption::find($itemEvalOpt->eval_option_id);
                                    return $evalOpt ? $evalOpt->option_value : null;
                                }
                                return null;
                            })->filter()->implode(', ') }}
                        @else
                            {{ $answer->evalOption->option_value ?? '—' }}
                        @endif
                        @break
                    @default
                        <span>{{ $answer->response_text ?? $answer->response_number ?? '—' }}</span>
                @endswitch
            @else
                <span>—</span>
            @endif
        </td>
        <td style="width:22%; text-align:right; font-size:11px; color:#888;">{{ $answer->observation ?? '.' }}</td>
    </tr>
@endforeach
        @endforeach
    </table>
    @php
        // Função para renderizar os anexos
        function renderAttachments($attachments, $itemName) {
            $output = '';
            
            if (count($attachments) > 0) {
                $output .= '<div style="font-weight: bold; margin: 12px 0 4px 0;">';
                $output .= e($itemName);
                $output .= '</div>';
                $output .= '<div style="margin-bottom: 12px; font-size: 0;">';
                
                foreach ($attachments as $attachment) {
                    $filePath = is_array($attachment) ? $attachment['file_path'] : $attachment->file_path;
                    $isImage = preg_match('/\.(jpg|jpeg|png|gif)$/i', $filePath);
                    $imagePath = public_path('storage/' . ltrim($filePath, '/'));
                    $imageBase64 = null;
                    
                    if ($isImage && file_exists($imagePath)) {
                        $imageData = @file_get_contents($imagePath);
                        if ($imageData !== false) {
                            $mimeType = mime_content_type($imagePath);
                            $imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        }
                    }
                    
                    $output .= '<div style="display: inline-block; width: 48%; margin: 1%; vertical-align: top; font-size: 12px;">';
                    
                    if ($imageBase64) {
                        $fileUrl = env('APP_URL') . '/storage/' . ltrim($filePath, '/');
                        $output .= '<div style="display: flex; justify-content: center; align-items: center; height: 220px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 4px; overflow: hidden;">';
                        $output .= '<a href="' . e($fileUrl) . '" target="_blank" style="display: block; max-width: 100%; max-height: 100%;">';
                        $output .= '<img src="' . e($imageBase64) . '" alt="Anexo" ';
                        $output .= 'style="max-width: 100%; max-height: 200px; width: auto; height: auto; display: block; margin: 0 auto; object-fit: scale-down;">';
                        $output .= '</a>';
                        $output .= '</div>';
                    } else {
                        $output .= '<div style="font-size: 11px; color: #999; text-align: center; padding: 20px 0; background: #f9f9f9; border: 1px dashed #ddd; border-radius: 4px;">';
                        $output .= 'Arquivo não é uma imagem ou não pôde ser carregado.';
                        $output .= '</div>';
                    }
                    
                    $fileName = is_array($attachment) ? 
                        ($attachment['name'] ?? basename($filePath)) : 
                        ($attachment->name ?? basename($filePath));
                    
                    $output .= '<div style="font-size: 10px; color: #666; word-break: break-all; text-align: center;">';
                    $output .= e($fileName);
                    $output .= '</div>';
                    $output .= '</div>'; // Fecha div do anexo
                }
                
                $output .= '</div>'; // Fecha div dos anexos
            }
            
            return $output;
        }
    @endphp

    @php
        // Função para agrupar anexos por item
        function groupAttachmentsByItem($answers) {
            $grouped = [];
            
            foreach ($answers as $answer) {
                $itemId = $answer->checklist_item_id;
                $itemName = $answer->item->name ?? 'Item ' . $itemId;
                
                if (!isset($grouped[$itemId])) {
                    $grouped[$itemId] = [
                        'name' => $itemName,
                        'attachments' => [],
                        'options' => []
                    ];
                }
                
                // Adiciona anexos diretos da resposta
                if ($answer->attachments->isNotEmpty()) {
                    $grouped[$itemId]['attachments'] = array_merge(
                        $grouped[$itemId]['attachments'],
                        $answer->attachments->toArray()
                    );
                }
                
                // Adiciona anexos das opções de resposta
                if ($answer->options->isNotEmpty()) {
                    foreach ($answer->options as $option) {
                        $selectionOption = $option->option ?? null;
                        if ($selectionOption && $option->attachments->isNotEmpty()) {
                            $optionName = $selectionOption->value ?? 'Opção ' . $option->id;
                            
                            if (!isset($grouped[$itemId]['options'][$option->id])) {
                                $grouped[$itemId]['options'][$option->id] = [
                                    'name' => $optionName,
                                    'attachments' => []
                                ];
                            }
                            
                            $grouped[$itemId]['options'][$option->id]['attachments'] = array_merge(
                                $grouped[$itemId]['options'][$option->id]['attachments'],
                                $option->attachments->toArray()
                            );
                        }
                    }
                }
            }
            
            return $grouped;
        }
        
        // Agrupa os anexos por item
        $groupedAttachments = [];
        if (isset($application->answers) && $application->answers->isNotEmpty()) {
            $groupedAttachments = groupAttachmentsByItem($application->answers);
        }
    @endphp

    @if(!empty($groupedAttachments))
        <div class="section-title" style="margin: 24px 0 6px 0; text-align: center; font-weight: bold; font-size: 14px;">Anexos</div>
        
        @foreach($groupedAttachments as $itemId => $itemData)
            @php
                $hasAttachments = !empty($itemData['attachments']) || !empty($itemData['options']);
            @endphp
            
            @if($hasAttachments)
                {{-- Anexos diretos do item --}}
                @if(!empty($itemData['attachments']))
                    {!! renderAttachments(collect($itemData['attachments']), $itemData['name']) !!}
                @endif
                
                {{-- Anexos das opções --}}
                @if(!empty($itemData['options']))
                    @foreach($itemData['options'] as $optionId => $optionData)
                        @if(!empty($optionData['attachments']))
                            @php
                                $optionName = $itemData['name'] . ' - ' . $optionData['name'];
                            @endphp
                            {!! renderAttachments(collect($optionData['attachments']), $optionName) !!}
                        @endif
                    @endforeach
                @endif
            @endif
        @endforeach
    @endif
    <div style="width:100%; text-align:right; font-size:10px; color:#888; margin-top:18px;">
        Gerado automaticamente por Inlog Checklist — {{ date('d/m/Y H:i') }}
    </div>
    
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->get_font("Arial");
            $size = 10;
            $pdf->page_text(
                $pdf->get_width() - 35 - $fontMetrics->get_text_width(str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], ['1', '1'], $text), $font, $size),
                $pdf->get_height() - 24,
                $text,
                $font,
                $size,
                array(0, 0, 0)
            );
        }
    </script>

</body>
</html>