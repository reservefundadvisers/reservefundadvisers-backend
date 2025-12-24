<?php

class AI_Model
{
    private $module_name = 'AI_Model';

    function __construct() {}

    public function process($cmd, $data)
    {
        $response = "";

        switch ($cmd) {
            case 'get':
                $response = $this->get($data);
                break;
            case 'save':
                $response = $this->save($data);
                break;
            case 'set':
                $response = $this->set($data);
                break;
            case 'delete':
                $response = $this->delete($data);
                break;
        }

        return $response;
    }

    /* =====================================================
       SAVE – Upload PDF & create DB record
    ===================================================== */
    public function save($data)
    {
        global $auth, $aiDocumentsTable;

        if (empty($_FILES)) {
            return send_json_response(false, 400, 'PDF file is required');
        }

        // Use existing helper (PDF-specific wrapper)
        $upload = $this->handle_pdf_upload($_FILES, 'uploads/ai');

        if ($upload['success'] === false) {
            return send_json_response(false, 400, $upload['message']);
        }

        $save_data = [
            'user_id'  => $auth->uid(),
            'pdf_path' => $upload['path'],
            'status'   => 'uploaded'
        ];
        rfa_create_log(print_r($save_data, true), 'AI Model - Save Data');

        $id = save_element($aiDocumentsTable, $save_data);
        rfa_create_log("Inserted ID: $id", 'AI Model - Save ID');
        if ($id === false) {
            return send_json_response(false, 500, 'Error saving AI document');
        }

        return send_json_response(true, 201, 'PDF uploaded successfully', [
            'document_id' => $id
        ]);
    }

    /* =====================================================
       SET – Send PDF to OpenAI Assistant
    ===================================================== */
    public function set($data)
    {
        global $aiDocumentsTable;

        if (empty($data['id'])) {
            return send_json_response(false, 400, 'Document ID is required');
        }

        $id = trim($data['id']);

        if (!exists($aiDocumentsTable, ['id' => $id])) {
            return send_json_response(false, 404, 'Document not found');
        }

        $doc = get_elements($aiDocumentsTable, ['id' => $id])[0];

        // 1. Upload file to OpenAI
        $file = $this->uploadFileToOpenAI($doc['pdf_path']);

        if (empty($file['id'])) {
            return send_json_response(false, 500, 'Failed to upload PDF to AI');
        }

        // 2. Create thread
        $thread = $this->openaiRequest(
            'POST',
            'https://api.openai.com/v1/threads',
            [
                'messages' => [[
                    'role' => 'user',
                    'content' => 'Extract structured JSON from this PDF.',
                    'attachments' => [[
                        'file_id' => $file['id']
                    ]]
                ]]
            ]
        );

        // 3. Run assistant
        $run = $this->openaiRequest(
            'POST',
            "https://api.openai.com/v1/threads/{$thread['id']}/runs",
            [
                'assistant_id' => getenv('OPENAI_ASSISTANT_ID')
            ]
        );

        $update = update_element(
            $aiDocumentsTable,
            [
                'assistant_id' => getenv('OPENAI_ASSISTANT_ID'),
                'thread_id'    => $thread['id'],
                'run_id'       => $run['id'],
                'status'       => 'processing'
            ],
            ['id' => $id]
        );

        if ($update === false) {
            return send_json_response(false, 500, 'Error updating AI document');
        }

        return send_json_response(true, 200, 'AI processing started');
    }

    /* =====================================================
       GET – Poll status & store extracted JSON
    ===================================================== */
    public function get($data)
    {
        global $aiDocumentsTable;

        if (empty($data['id'])) {
            return send_json_response(false, 400, 'Document ID is required');
        }

        $id = trim($data['id']);

        if (!exists($aiDocumentsTable, ['id' => $id])) {
            return send_json_response(false, 404, 'Document not found');
        }

        $doc = get_elements($aiDocumentsTable, ['id' => $id])[0];

        $run = $this->openaiRequest(
            'GET',
            "https://api.openai.com/v1/threads/{$doc['thread_id']}/runs/{$doc['run_id']}"
        );

        if ($run['status'] !== 'completed') {
            return send_json_response(true, 200, 'Processing', [
                'status' => $run['status']
            ]);
        }

        $messages = $this->openaiRequest(
            'GET',
            "https://api.openai.com/v1/threads/{$doc['thread_id']}/messages"
        );

        $json = $messages['data'][0]['content'][0]['text']['value'];

        update_element(
            $aiDocumentsTable,
            [
                'extracted_json' => $json,
                'status' => 'completed'
            ],
            ['id' => $id]
        );

        return send_json_response(true, 200, 'Completed', json_decode($json, true));
    }

    /* =====================================================
       DELETE
    ===================================================== */
    public function delete($data)
    {
        global $aiDocumentsTable;

        if (empty($data['id'])) {
            return send_json_response(false, 400, 'ID is required');
        }

        if (!exists($aiDocumentsTable, ['id' => $data['id']])) {
            return send_json_response(false, 404, 'Document not found');
        }

        delete_elements_by_cond(
            $aiDocumentsTable,
            'id = :id',
            ['id' => $data['id']]
        );

        return send_json_response(true, 200, 'AI document deleted');
    }

    /* =====================================================
       INTERNAL HELPERS
    ===================================================== */

    private function handle_pdf_upload($fileInput, $dir)
    {
        // Minimal wrapper aligned with your helper style
        $file = $fileInput[array_key_first($fileInput)];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Upload error'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            return ['success' => false, 'message' => 'Only PDF allowed'];
        }

        $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
        $path = $root . trim($dir, '/') . '/';

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $name = uniqid('pdf_', true) . '.pdf';
        move_uploaded_file($file['tmp_name'], $path . $name);

        return [
            'success' => true,
            'path' => '/' . trim($dir, '/') . '/' . $name
        ];
    }

    private function uploadFileToOpenAI($publicPath)
    {
        $fullPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $publicPath;

        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . getenv('OPENAI_API_KEY')
            ],
            CURLOPT_POSTFIELDS => [
                'purpose' => 'assistants',
                'file' => new CURLFile($fullPath)
            ]
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }

    private function openaiRequest($method, $url, $payload = null)
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
                'Content-Type: application/json'
            ]
        ]);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true);
    }
}
