<?php
// includes/AIService.php

class AIService {
    private $api_url;
    private $api_key;
    private $model;
    private $temperature;
    private $max_tokens;
    
    public function __construct() {
        // Usar as constantes definidas no database.php
        $this->api_url = AI_API_URL;
        $this->api_key = AI_API_KEY;
        $this->model = AI_MODEL;
        $this->temperature = AI_TEMPERATURE;
        $this->max_tokens = AI_MAX_TOKENS;
    }
    
    public function gerarPDI($dados) {
        $prompt = $this->montarPrompt($dados);
        
        $response = $this->chamarAPI($prompt);
        
        if ($response && isset($response['choices'][0]['message']['content'])) {
            return json_decode($response['choices'][0]['message']['content'], true);
        }
        
        return null;
    }
    
    private function montarPrompt($dados) {
        $competencias_texto = "";
        foreach ($dados['competencias'] as $c) {
            $competencias_texto .= "- {$c['nome']}: {$c['media']}%\n";
        }
        
        $prompt = "
Você é um especialista em RH e desenvolvimento de carreira. Analise os dados do colaborador abaixo e gere um PDI personalizado.

COLABORADOR:
- Nome: {$dados['nome']}
- Cargo Atual: {$dados['cargo']}
- Departamento: {$dados['departamento']}
- Tempo de Empresa: {$dados['tempo_empresa']} meses

AVALIAÇÕES (médias por competência):
{$competencias_texto}

Com base nestes dados, gere um plano de desenvolvimento no seguinte formato JSON:
{
  \"analise_geral\": \"breve análise do perfil do colaborador\",
  \"pontos_fortes\": [\"lista de competências com nota >= 80%\"],
  \"areas_desenvolvimento\": [
    {
      \"competencia\": \"nome\",
      \"nivel_atual\": 0,
      \"justificativa\": \"por que desenvolver\",
      \"metas\": [
        {
          \"titulo\": \"título da meta SMART\",
          \"descricao\": \"descrição detalhada\",
          \"criterio_sucesso\": \"como medir\",
          \"prazo_sugerido\": \"YYYY-MM-DD\"
        }
      ],
      \"acoes\": {
        \"pratica\": \"ação de experiência prática (70%)\",
        \"relacionamento\": \"ação de mentoria/feedback (20%)\",
        \"educacao\": \"ação de curso/leitura (10%)\"
      }
    }
  ],
  \"resumo_executivo\": \"parágrafo resumindo o plano\"
}

Responda APENAS com o JSON, sem comentários adicionais.";
        
        return $prompt;
    }
    
    private function chamarAPI($prompt) {
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente especializado em RH que responde apenas em JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->max_tokens
        ];
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key,
            'HTTP-Referer: ' . SITE_URL,
            'X-Title: ' . SITE_NAME
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return json_decode($response, true);
        }
        
        error_log("Erro na API de IA: $httpCode - $response");
        return null;
    }
}
?>
