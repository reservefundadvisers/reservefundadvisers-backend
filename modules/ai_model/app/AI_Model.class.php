<?php

class AI_Model
{
    private $module_name = 'AI_Model';
    private $assistantId;
    private $apiKey;

    public function __construct()
    {
        $this->assistantId = getenv('OPENAI_ASSISTANT_ID') ?: ($_ENV['OPENAI_ASSISTANT_ID'] ?? '');
        $this->apiKey     = getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? '');

        rfa_create_log('[AI_MODEL][INIT] Assistant ID: ' . ($this->assistantId ?: 'NOT SET'));
        rfa_create_log('[AI_MODEL][INIT] API Key: ' . ($this->apiKey ? 'SET' : 'NOT SET'));

        if (!$this->assistantId || !$this->apiKey) {
            rfa_create_log('[AI_MODEL][INIT][FAIL] Missing OpenAI ENV variables');
        }
    }

    public function process($cmd, $data)
    {
        if ($cmd !== 'save') {
            return send_json_response(false, 400, 'Invalid command');
        }

        return $this->save($data);
    }

    /* =====================================================
       SAVE – FULL AI PIPELINE
    ===================================================== */
    public function save($data)
    {
        global $auth, $aiDocumentsTable;
        $isChatOnly = isset($data['is_admin_chat_only']) ? $data['is_admin_chat_only'] : 0;

        rfa_create_log('[AI_MODEL][STEP 1][START] Processing PDF');

        /* ---------- STEP 1: LOCAL PDF UPLOAD ---------- */
        if (empty($_FILES)) {
            rfa_create_log('[AI_MODEL][STEP 1][FAIL] No PDF uploaded');
            return send_json_response(false, 400, 'PDF file required');
        }

        $upload = $this->handle_pdf_upload($_FILES, 'uploads/ai');

        if (!$upload['success']) {
            rfa_create_log('[AI_MODEL][STEP 1][FAIL] ' . $upload['message']);
            return send_json_response(false, 400, $upload['message']);
        }

        rfa_create_log('[AI_MODEL][STEP 1][SUCCESS] PDF stored at ' . $upload['path']);

        /* ---------- STEP 2: DB RECORD ---------- */
        $docData = [
            'user_id'  => $auth->uid(),
            'pdf_path' => $upload['path'],
            'status'   => 'processing',
            'is_admin_chat_only' => $isChatOnly
        ];

        // Only add 'client_id' if it exists
        if (!empty($data['client_id'])) {
            $docData['client_id'] = $data['client_id'];
        }

        $docId = save_element($aiDocumentsTable, $docData);

        if (!$docId) {
            rfa_create_log('[AI_MODEL][STEP 2][FAIL] DB insert failed');
            return send_json_response(false, 500, 'DB insert failed');
        }

        rfa_create_log("[AI_MODEL][STEP 2][SUCCESS] Document ID: {$docId}");

        /* ---------- STEP 3: OPENAI FILE UPLOAD ---------- */
        rfa_create_log('[AI_MODEL][STEP 3][START] Uploading file to OpenAI');

        $file = $this->uploadFileToOpenAI($upload['path']);

        if (empty($file['id'])) {
            return $this->fail($docId, 'OpenAI file upload failed');
        }

        rfa_create_log('[AI_MODEL][STEP 3][SUCCESS] OpenAI file_id: ' . $file['id']);

        /* ---------- STEP 4: CREATE THREAD + RUN ---------- */
        rfa_create_log('[AI_MODEL][STEP 4][START] Creating thread + run');

        $run = $this->openaiRequest(
            'POST',
            'https://api.openai.com/v1/threads/runs',
            [
                'assistant_id' => $this->assistantId,
                'thread' => [
                    'messages' => [[
                        'role' => 'user',
                        'content' => [[
                            'type' => 'text',
                            'text' => 'Read the attached PDF and extract all required fields exactly per instructions.'
                        ]],
                        'attachments' => [[
                            'file_id' => $file['id'],
                            'tools' => [['type' => 'file_search']]
                        ]]
                    ]]
                ]
            ]
        );

        if (empty($run['id']) || empty($run['thread_id'])) {
            return $this->fail($docId, 'Thread/Run creation failed');
        }

        rfa_create_log("[AI_MODEL][STEP 4][SUCCESS] run_id={$run['id']} thread_id={$run['thread_id']}");

        update_element($aiDocumentsTable, [
            'assistant_id'   => $this->assistantId,
            'openai_file_id' => $file['id'],
            'thread_id'     => $run['thread_id'],
            'run_id'        => $run['id'],
            'run_payload'   => json_encode($run)
        ], ['id' => $docId]);


        /* ---------- STEP 5: WAIT FOR COMPLETION ---------- */
        rfa_create_log('[AI_MODEL][STEP 5][START] Polling run status');

        $result = $this->waitForResult($run['thread_id'], $run['id']);

        if ($result === null) {
            return $this->fail($docId, 'AI extraction failed');
        }

        rfa_create_log('[AI_MODEL][STEP 5][SUCCESS] JSON extracted');

        /* ---------- STEP 6: SAVE RESULT ---------- */
        update_element($aiDocumentsTable, [
            'extracted_json' => json_encode($result, JSON_UNESCAPED_UNICODE),
            'status'         => 'completed',
            'completed_at'   => date('Y-m-d H:i:s')
        ], ['id' => $docId]);

        rfa_create_log('[AI_MODEL][DONE] Document processed successfully');

        return send_json_response(true, 201, 'PDF processed successfully', [
            'document_id' => $docId
        ]);
    }

    /* =====================================================
       POLLING
    ===================================================== */
    private function waitForResult(string $threadId, string $runId): ?array
    {
        for ($i = 1; $i <= 40; $i++) {
            sleep(1);

            $run = $this->openaiRequest(
                'GET',
                "https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}"
            );

            rfa_create_log("[AI_MODEL][STEP 5][WAIT {$i}] status={$run['status']}");

            if ($run['status'] === 'completed') {
                $messages = $this->openaiRequest(
                    'GET',
                    "https://api.openai.com/v1/threads/{$threadId}/messages"
                );

                foreach ($messages['data'] as $msg) {
                    if ($msg['role'] === 'assistant') {
                        return json_decode($msg['content'][0]['text']['value'], true);
                    }
                }
            }

            if (in_array($run['status'], ['failed', 'cancelled', 'expired'])) {
                rfa_create_log('[AI_MODEL][STEP 5][FAIL] Run ended with status ' . $run['status']);
                return null;
            }
        }

        rfa_create_log('[AI_MODEL][STEP 5][FAIL] Timeout waiting for run');
        return null;
    }

    /* =====================================================
       FAILURE HANDLER
    ===================================================== */
    private function fail($docId, string $reason)
    {
        global $aiDocumentsTable;

        update_element($aiDocumentsTable, ['status' => 'failed'], ['id' => $docId]);
        rfa_create_log('[AI_MODEL][FAIL] ' . $reason);

        return send_json_response(false, 500, $reason);
    }

    /* =====================================================
       FILE HANDLING
    ===================================================== */
    private function handle_pdf_upload($fileInput, $dir)
    {
        $file = $fileInput[array_key_first($fileInput)];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error'];
        }

        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            return ['success' => false, 'message' => 'Only PDF allowed'];
        }

        $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
        $path = $root . trim($dir, '/') . '/';
        if (!is_dir($path)) mkdir($path, 0777, true);

        $name = uniqid('pdf_', true) . '.pdf';
        move_uploaded_file($file['tmp_name'], $path . $name);

        return ['success' => true, 'path' => '/' . trim($dir, '/') . '/' . $name];
    }

    /* =====================================================
       OPENAI HELPERS
    ===================================================== */
    private function uploadFileToOpenAI(string $publicPath): array
    {
        $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $publicPath;

        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_POSTFIELDS => [
                'purpose' => 'assistants',
                'file' => new CURLFile($fullPath)
            ]
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    private function openaiRequest($method, $url, $payload = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60
        ]);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }
}
