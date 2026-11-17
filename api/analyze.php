<?php
// =============================================
//  ACESSIIDOSO - Gemini Vision API Proxy (stable)
// =============================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/config.php';

if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_GEMINI_API_KEY_HERE') {
    echo json_encode(['error' => 'API_KEY_NOT_SET', 'message' => 'Configure sua chave da API Gemini em api/config.php']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['image'])) {
    echo json_encode(['error' => 'NO_IMAGE', 'message' => 'Nenhuma imagem recebida.']);
    exit;
}

$base64Image = $input['image'];
$mimeType    = isset($input['mimeType']) ? $input['mimeType'] : 'image/jpeg';

// ---- Prompt robusto ----
$prompt = "Voce e um farmaceutico especializado. Analise a receita medica na imagem e extraia TODOS os medicamentos.\n"
    . "Retorne SOMENTE um array JSON valido (sem texto extra, sem markdown, sem ```).\n"
    . "Cada item do array deve ter EXATAMENTE estas chaves (valores em portugues):\n"
    . "name, category, prescription, indication, dosage, scheduleHours, contraindications, sideEffects, food\n"
    . "Regras:\n"
    . "- scheduleHours: numero inteiro (ex: 8 para 'de 8 em 8 horas'). Use 8 se nao souber.\n"
    . "- prescription: exatamente 'Necessaria' ou 'Nao necessaria'\n"
    . "- Se a receita estiver manuscrita ou digital, leia da mesma forma\n"
    . "- Se nao souber um campo, use 'Nao identificado'\n"
    . "- Mesmo que seja apenas 1 medicamento, retorne dentro de um array []\n"
    . "Exemplo de saida: [{\"name\":\"Amoxicilina 500mg\",\"category\":\"Antibiotico\",\"prescription\":\"Necessaria\",\"indication\":\"Infeccoes bacterianas\",\"dosage\":\"1 capsula de 8 em 8 horas\",\"scheduleHours\":8,\"contraindications\":\"Alergia a penicilina\",\"sideEffects\":\"Diarreia\",\"food\":\"Pode tomar com ou sem alimento\"}]";

// ---- Modelos disponiveis (com cotas separadas no plano gratuito) ----
$models = [
    ['model' => 'gemini-3.8-flash', 'api' => 'v1beta'],
    ['model' => 'gemini-3.5-flash-lite', 'api' => 'v1beta']
];

// ---- Tenta sem responseMimeType primeiro (mais compativel) ----
function buildRequest($prompt, $base64Image, $mimeType, $forceJson = false) {
    $config = [
        'temperature'     => 0.1,
        'maxOutputTokens' => 4096,
    ];
    if ($forceJson) {
        $config['responseMimeType'] = 'application/json';
    }
    return [
        'contents' => [[
            'parts' => [
                ['text' => $prompt],
                ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Image]]
            ]
        ]],
        'generationConfig' => $config
    ];
}

function tryModel($apiUrl, $requestBody) {
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($requestBody));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        90);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    return ['raw' => $raw, 'httpCode' => $httpCode, 'curlErr' => $curlErr];
}

$response  = null;
$lastError = null;

foreach ($models as $entry) {
    $apiUrl = 'https://generativelanguage.googleapis.com/' . $entry['api']
            . '/models/' . $entry['model']
            . ':generateContent?key=' . GEMINI_API_KEY;

    // Primeira tentativa: sem forcar JSON (mais compativel)
    $result = tryModel($apiUrl, buildRequest($prompt, $base64Image, $mimeType, false));
    if ($result['raw'] && $result['httpCode'] === 200) {
        $decoded = json_decode($result['raw'], true);
        // Verifica se e um erro da API (modelo nao existe, etc.)
        if (!isset($decoded['error'])) {
            $response = $result['raw'];
            file_put_contents(__DIR__ . '/debug_raw.txt', $result['raw']);
            break;
        }
        $lastError = $decoded['error'];
        continue;
    }

    // Segunda tentativa com forceJson (apenas para modelos que suportam)
    $result2 = tryModel($apiUrl, buildRequest($prompt, $base64Image, $mimeType, true));
    if ($result2['raw'] && $result2['httpCode'] === 200) {
        $decoded2 = json_decode($result2['raw'], true);
        if (!isset($decoded2['error'])) {
            $response = $result2['raw'];
            file_put_contents(__DIR__ . '/debug_raw.txt', $result2['raw']);
            break;
        }
    }

    $lastError = ['httpCode' => $result['httpCode'], 'response' => substr($result['raw'], 0, 500)];
}

if (!$response) {
    echo json_encode([
        'error'   => 'ALL_MODELS_FAILED',
        'message' => 'Nao foi possivel conectar a API Gemini. Verifique sua chave e conexao.',
        'debug'   => $lastError
    ]);
    exit;
}

$geminiData = json_decode($response, true);

if (isset($geminiData['error'])) {
    echo json_encode([
        'error'   => 'GEMINI_API_ERROR',
        'message' => isset($geminiData['error']['message']) ? $geminiData['error']['message'] : 'Erro da API Gemini.',
    ]);
    exit;
}

// ---- Extrai o texto da resposta ----
$rawText = null;
if (isset($geminiData['candidates'][0]['content']['parts'][0]['text'])) {
    $rawText = $geminiData['candidates'][0]['content']['parts'][0]['text'];
}

if (!$rawText && isset($geminiData['candidates'][0]['finishReason'])) {
    $reason = $geminiData['candidates'][0]['finishReason'];
    if ($reason === 'SAFETY') {
        echo json_encode(['error' => 'SAFETY_BLOCK', 'message' => 'Imagem bloqueada por filtros de seguranca. Tente outra foto.']);
        exit;
    }
}

if (!$rawText && isset($geminiData['promptFeedback']['blockReason'])) {
    echo json_encode(['error' => 'PROMPT_BLOCKED', 'message' => 'Prompt bloqueado: ' . $geminiData['promptFeedback']['blockReason']]);
    exit;
}

if (!$rawText) {
    echo json_encode(['error' => 'EMPTY_RESPONSE', 'message' => 'A IA nao retornou texto. Tente novamente com uma foto mais clara.']);
    exit;
}

// ---- Limpa e extrai o JSON da resposta ----
$rawText = trim($rawText);

// Remove markdown code fences
$rawText = preg_replace('/^```(?:json)?\s*/im', '', $rawText);
$rawText = preg_replace('/\s*```$/im', '', $rawText);
$rawText = trim($rawText);

// Tenta extrair array JSON se houver texto extra
if (substr($rawText, 0, 1) !== '[' && substr($rawText, 0, 1) !== '{') {
    // Busca primeiro array JSON completo
    if (preg_match('/(\[[\s\S]*\])/m', $rawText, $m)) {
        $rawText = $m[1];
    } elseif (preg_match('/(\{[\s\S]*\})/m', $rawText, $m)) {
        $rawText = $m[1];
    }
}
$rawText = trim($rawText);

$medData = json_decode($rawText, true);

// Se veio como objeto unico, envolve em array
if (is_array($medData) && isset($medData['name'])) {
    $medData = [$medData];
}

// Se veio como objeto com chave "medications"
if (is_array($medData) && isset($medData['medications']) && is_array($medData['medications'])) {
    $medData = $medData['medications'];
}

if (!$medData || !is_array($medData) || count($medData) === 0) {
    echo json_encode([
        'error'   => 'PARSE_ERROR',
        'message' => 'Nao foi possivel interpretar os dados da receita. Tente uma foto mais nitida.',
        'rawText' => substr($rawText, 0, 300)
    ]);
    exit;
}

// ---- Normaliza os campos ----
$fields = ['name', 'category', 'prescription', 'indication', 'dosage', 'contraindications', 'sideEffects', 'food'];
foreach ($medData as &$med) {
    foreach ($fields as $f) {
        if (empty($med[$f])) $med[$f] = 'Nao identificado';
    }
    $med['scheduleHours'] = isset($med['scheduleHours']) ? max(1, (int)$med['scheduleHours']) : 8;
    if ($med['scheduleHours'] === 0) $med['scheduleHours'] = 8;
}
unset($med);

echo json_encode(['success' => true, 'medications' => $medData]);
?>
