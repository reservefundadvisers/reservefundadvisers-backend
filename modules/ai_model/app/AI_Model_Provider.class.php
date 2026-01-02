<?php

class AI_Model_Provider
{
    private $module_name = 'ai_model_provider';

    public function __construct() {}

    public function process($cmd, $data)
    {
        switch ($cmd) {
            case 'get':
                return $this->list($data);
            case 'edit':
                return $this->edit($data);
            case 'delete':
                return $this->delete($data);
            case 'publish':
                return $this->publish($data);
            case 'save_model_id':
                return $this->save_model_id($data);
            case 'delete':
                return $this->delete($data);
            case 'conversation':
                return $this->storeConversation($data);
            case 'get_conversation':
                return $this->getConversation($data);
            case 'store_conversation_pdf':
                return $this->storeConversationPDF($data);
            default:
                send_json_response(false, 400, 'Invalid Command');
                break;
        }
        return null;
    }

    /**
     * List AI documents with pagination & filters
     * rejected models NEVER returned to FE
     */
    public function list($data)
    {
        global $aiDocumentsTable, $usersTable;

        $data = is_array($data) ? $data : [];

        /* ================= SINGLE RECORD ================= */
        if (is_valid($data, 'id')) {

            $conds = [
                'id' => $data['id'],
                '!admin_approval_status' => 'rejected'
            ];

            // fetch single record safely using get_elements
            $record = get_elements(
                $aiDocumentsTable,
                $conds,
                "$aiDocumentsTable.id,
                $aiDocumentsTable.user_id,
                $aiDocumentsTable.model_id,
                $aiDocumentsTable.client_id,
                $aiDocumentsTable.thread_id,
                $aiDocumentsTable.status,
                $aiDocumentsTable.admin_approval_status,
                $aiDocumentsTable.pdf_path,
                $aiDocumentsTable.ai_chat_pdfs  ,
                $aiDocumentsTable.extracted_json",
                "LIMIT 1"
            );

            // get first element if exists, else empty
            $record = !empty($record) ? $record[0] : [];

            return send_json_response(true, 200, 'Success', ['data' => $record]);
        }

        /* ================= LIST ================= */
        $conds = [];
        $isAdminChatOnly = isset($data['is_admin_chat_only']) ? $data['is_admin_chat_only'] : 0;

        /* ================= FILTER ================= */
        if ($isAdminChatOnly) {
            // When is_admin_chat_only is true, only filter by that field
            // Skip admin_approval_status filters, but still exclude rejected
            $conds['is_admin_chat_only'] = 1;
            $conds['!admin_approval_status'] = 'rejected';
        } else {
            // Default behavior: is_admin_chat_only = false
            // Apply normal admin_approval_status filters
            $conds['is_admin_chat_only'] = 0;

            if (is_valid($data, 'admin_approval_status')) {

                $statuses = $data['admin_approval_status'];
                if (!is_array($statuses)) {
                    $statuses = [$statuses];
                }

                // allowed values
                $valid = ['pending', 'complete', 'in_review'];
                $statuses = array_values(array_intersect($statuses, $valid));

                if (!empty($statuses)) {

                    // ALWAYS use raw SQL
                    $conds['raw'] =
                        "$aiDocumentsTable.admin_approval_status IN ('" .
                        implode("','", $statuses) .
                        "') AND $aiDocumentsTable.admin_approval_status != 'rejected'";
                }
            }

            /* if no filter provided, still exclude rejected */
            if (!isset($conds['raw'])) {
                $conds['raw'] = "$aiDocumentsTable.admin_approval_status != 'rejected' AND $aiDocumentsTable.status = 'completed'";
            }
        }

        /* pagination setup */
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $size = isset($data['size']) ? max(1, intval($data['size'])) : 20;
        $offset = ($page - 1) * $size;

        /* total count */
        $count = get_element($aiDocumentsTable, $conds, "COUNT(id) AS count");
        $total = isset($count['count']) ? intval($count['count']) : 0;

        /* last page */
        $last_page = $size > 0 ? (int)ceil($total / $size) : 1;

        /* fetch paginated data */
        $results = get_elements(
            "$aiDocumentsTable 
            LEFT JOIN $usersTable 
                ON $usersTable.id = $aiDocumentsTable.user_id",
            $conds,
            "
            $aiDocumentsTable.id,
            $aiDocumentsTable.user_id,
            $aiDocumentsTable.status,
            $aiDocumentsTable.pdf_path,
            $aiDocumentsTable.admin_approval_status,
            $aiDocumentsTable.is_admin_chat_only,

            $usersTable.fn AS user_first_name,
            $usersTable.ln  AS user_last_name,
            $usersTable.email      AS user_email
            ",
            " ORDER BY $aiDocumentsTable.row_id DESC LIMIT $offset, $size"
        );

        /* professional pagination response */
        return send_json_response(true, 200, 'Success', [
            'data'         => $results,
            'total'        => $total,
            'per_page'     => $size,
            'current_page' => $page,
            'last_page'    => $last_page,
            'has_more'     => $page < $last_page
        ]);
    }

    /**
     * Admin can update ONLY admin_approval_status
     */
    public function edit($data)
    {
        global $aiDocumentsTable;

        if (!is_valid($data, 'id') || !is_valid($data, 'admin_approval_status')) return send_json_response(false, 400, 'Invalid request');

        $allowed = ['pending', 'complete', 'in_review', 'rejected'];
        if (!in_array($data['admin_approval_status'], $allowed)) return send_json_response(false, 400, 'Invalid status');

        $update = set_property(
            $aiDocumentsTable,
            [
                'id' => $data['id'],
                'admin_approval_status' => $data['admin_approval_status']
            ]
        );

        if (!$update) return send_json_response(false, 500, 'Failed to update');

        return send_json_response(true, 200, 'Success', ['message' => 'Updated successfully']);
    }

    /**
     * Admin can delete an AI document record
     */
    public function delete($data)
    {
        global $aiDocumentsTable;

        if (!is_valid($data, 'id')) return send_json_response(false, 400, 'Invalid request');

        $trans_started = start_transaction();

        $delete = delete_elements_by_id($aiDocumentsTable, $data['id']);

        if ($trans_started) end_transaction();

        if (!$delete) return send_json_response(false, 500, 'Failed to delete');

        return send_json_response(true, 200, 'Success', ['message' => 'Deleted successfully']);
    }

    /**
     * Publish AI Model (dummy function for now)
     */
    public function publish($data)
    {
        global $aiDocumentsTable, $modelsTable;

        if (!is_valid($data, 'id') || !is_valid($data, 'publish') || !is_valid($data, 'user_id') || !is_valid($data, 'model_id')) return send_json_response(false, 400, 'Invalid request');
        $status = ['complete', 'in_review'];

        if ($data['publish'] == true) {

            $modelId = $data['model_id'];
            $modelExists = get_elements(
                $modelsTable,
                ['id' => $modelId],
                "COUNT(id) AS count"
            );

            if (isset($modelExists[0]['count']) && $modelExists[0]['count'] == 0) {
                return send_json_response(false, 400, 'Model ID does not exist');
            }else{
                //update model active status to 1
                $updateModel = set_property(
                    $modelsTable,
                    [
                        'id' => $data['model_id'],
                        'active' => 1
                    ]
                );
            }

            // perform publish actions here (e.g., make model live)
            $update = set_property(
                $aiDocumentsTable,
                [
                    'id' => $data['id'],
                    'admin_approval_status' => $status[0]
                ]
            );

            $userName = $this->getUserName($data['user_id']);
            $userEmail = $this->getUserEmail($data['user_id']);
            $modelName = $this->getModelName($data['model_id']);
            $accessUrl = $_ENV['FRONTEND_URL'] ?? '';

            if ($update) {
                $template = emailTemplatePublishModel($userName, $modelName, $accessUrl);
                $subject = $template['subject'] ?? '';
                $body = $template['body'] ?? '';

                if (empty($userEmail) || empty($subject) || empty($body)) {
                    return send_json_response(false, 500, 'Failed to send notification email');
                }

                $res = sendOtpEmail($userEmail, $subject, $body);

                if ($res) {
                    return send_json_response(true, 200, 'Success', ['message' => 'Published successfully']);
                } else {
                    return send_json_response(false, 500, 'Failed to send notification email');
                }
            } else {
                return send_json_response(false, 500, 'Failed to publish');
            }
        } else {
            // perform unpublish actions here (e.g., take model offline)
            $update = set_property(
                $aiDocumentsTable,
                [
                    'id' => $data['id'],
                    'admin_approval_status' => $status[1]
                ]
            );

            if ($update) {
                return send_json_response(true, 200, 'Success', ['message' => 'Unpublished successfully']);
            } else {
                return send_json_response(false, 500, 'Failed to unpublish');
            }
        }
    }

    public function storeConversation($data)
    {
        global $aiDocumentsTable;

        if (!is_valid($data, 'id')) {
            return send_json_response(false, 400, 'Invalid request');
        }

        $docId = $data['id'];

        // Fetch existing conversation
        $existingConversationJson = get_element(
            $aiDocumentsTable,
            ['id' => $docId],
            'conversation'
        );

        $conversation = [];

        if (!empty($existingConversationJson)) {
            if (is_string($existingConversationJson)) {
                $decoded = json_decode($existingConversationJson, true);
                $conversation = is_array($decoded) ? $decoded : [];
            } elseif (is_array($existingConversationJson)) {
                $conversation = $existingConversationJson;
            }
        }


        // Append new question
        if (!empty($data['question'])) {
            $conversation[] = [
                'role' => 'user',
                'content' => $data['question'],
                'created_at' => time()
            ];
        }

        // Append new answer
        if (!empty($data['answer'])) {
            $conversation[] = [
                'role' => 'assistant',
                'content' => $data['answer'],
                'created_at' => time()
            ];
        }

        if (empty($conversation)) {
            return send_json_response(false, 400, 'No conversation data');
        }

        $update = set_property(
            $aiDocumentsTable,
            [
                'id' => $docId,
                'conversation' => json_encode($conversation)
            ]
        );

        if ($update) {
            return send_json_response(true, 200, 'Success', [
                'message' => 'Conversation appended successfully'
            ]);
        }

        return send_json_response(false, 500, 'Failed to save conversation');
    }

    /**
     * Retrieve a single AI document record's conversation
     *
     * @param array $data - an associative array containing the AI document record ID
     *
     * @return array - a JSON response containing the conversation data
     */
    public function getConversation($data) {
        global $aiDocumentsTable;

          /* ================= SINGLE RECORD ================= */
        if (is_valid($data, 'id')) {

            $conds = [
                'id' => $data['id'],
                '!admin_approval_status' => 'rejected'
            ];

            // fetch single record safely using get_elements
            $record = get_elements(
                $aiDocumentsTable,
                $conds,
                "$aiDocumentsTable.id,
                $aiDocumentsTable.user_id,
                $aiDocumentsTable.conversation",
                "LIMIT 1"
            );

            // get first element if exists, else empty
            $record = !empty($record) ? $record[0] : [];

            return send_json_response(true, 200, 'Success', ['data' => $record]);
        }

    }

    /**
     * Saves a model ID against an AI document record
     *
     * @param array $data - an associative array containing the AI document record ID and model ID
     *
     * @return array - a JSON response containing the result of the operation
     *
     * @throws Exception - throws an exception if the model ID does not exist
     */
    public function save_model_id($data)
    {
        global $aiDocumentsTable, $modelsTable;

        if (!is_valid($data, 'id') || !is_valid($data, 'model_id')) return send_json_response(false, 400, 'Invalid request');

        $modelId = $data['model_id'];
        $modelExists = get_elements(
            $modelsTable,
            ['id' => $modelId],
            "COUNT(id) AS count"
        );

        if (isset($modelExists[0]['count']) && $modelExists[0]['count'] == 0) {
            return send_json_response(false, 400, 'Model ID does not exist');
        }

        $update = set_property(
            $aiDocumentsTable,
            [
                'id' => $data['id'],
                'model_id' => $data['model_id'],
                'admin_approval_status' => 'in_review'
            ]
        );

        if (!$update) return send_json_response(false, 500, 'Failed to update model ID');

        return send_json_response(true, 200, 'Success', ['message' => 'Model ID saved successfully']);
    }

    /**
     * Stores a PDF file against an AI document record
     *
     * @param array $data - an associative array containing the AI document record ID and optional file_key_id
     *
     * @return array - a JSON response containing the result of the operation
     *
     * @throws Exception - throws an exception if the AI document record ID does not exist or if the PDF file is missing
     */
    public function storeConversationPDF($data)
    {
        global $aiDocumentsTable;

        if (!is_valid($data, 'id')) {
            return send_json_response(false, 400, 'Invalid request');
        }

        $docId = $data['id'];

        $conds = [
            'id' => $docId,
            '!admin_approval_status' => 'rejected'
        ];


        $doc = get_elements($aiDocumentsTable, $conds);
        if (empty($doc)) {
            return send_json_response(false, 404, 'Document not found');
        }

        $doc = $doc[0];

        if (empty($_FILES)) {
            return send_json_response(false, 400, 'PDF file required');
        }

        /* ---------- UPLOAD FILES ---------- */
        $upload = $this->handle_pdf_upload($_FILES, 'uploads/ai/chat');

        if (!$upload['success']) {
            return send_json_response(false, 400, $upload['message']);
        }

        $paths = $upload['path']; // indexed array
        $fileIds = $data['file_key_id'] ?? [];
        $fileNames = $data['file_name'] ?? [];

        /* ---------- BUILD NEW ENTRIES ---------- */
        $newEntries = [];

        foreach ($paths as $index => $path) {
            $newEntries[] = [
                'path'        => $path,
                'file_key_id' => $fileIds[$index] ?? null,
                'file_name' => $fileNames[$index] ?? null
            ];
        }

        $existing = [];

        if (!empty($doc['ai_chat_pdfs'])) {
            $decoded = json_decode($doc['ai_chat_pdfs'], true);

            if (is_array($decoded)) {
                foreach ($decoded as $item) {

                    // Old format: string path
                    if (is_string($item)) {
                        $existing[] = [
                            'path'        => $item,
                            'file_key_id' => null,
                            'file_name' => null
                        ];
                    }

                    // New format: object
                    elseif (is_array($item) && isset($item['path'])) {
                        $existing[] = [
                            'path'        => $item['path'],
                            'file_key_id' => $item['file_key_id'] ?? null,
                            'file_name' => $item['file_name'] ?? null  
                        ];
                    }
                }
            }
        }

        // Prevent duplicates (by path OR file_key_id)
        $merged = array_values(array_reduce(
            array_merge($existing, $newEntries),
            function ($carry, $item) {
                $key = $item['file_key_id'] ?: $item['path'];
                $carry[$key] = $item;
                return $carry;
            },
            []
        ));

        /* ---------- SAVE ---------- */
        update_element(
            $aiDocumentsTable,
            [
                'ai_chat_pdfs' => json_encode($merged, JSON_UNESCAPED_SLASHES),
                'updated_at'   => date('Y-m-d H:i:s')
            ],
            ['id' => $docId]
        );

        return send_json_response(true, 200, 'PDFs stored successfully', [
            'count' => count($merged)
        ]);
    }

    /**
     * Handles a PDF file upload and saves it to a specified directory.
     *
     * @param array $fileInput The file input data, either as a single file or an array of files.
     * @param string $dir The directory to save the uploaded file to.
     *
     * @return array An associative array containing the success status, a public path to the uploaded file (if successful), and a message.
     */
    private function handle_pdf_upload($fileInput, $dir)
    {
        $file = $fileInput[array_key_first($fileInput)];

        $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';
        $basePath = $root . trim($dir, '/') . '/';

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $paths = [];

        $isMultiple = is_array($file['name']);
        $count = $isMultiple ? count($file['name']) : 1;

        for ($i = 0; $i < $count; $i++) {

            $error = $isMultiple ? $file['error'][$i] : $file['error'];

            if ($error !== UPLOAD_ERR_OK) {
                return [
                    'success' => false,
                    'message' => 'Upload error (code: ' . $error . ')'
                ];
            }

            $originalName = $isMultiple ? $file['name'][$i] : $file['name'];
            $tmpName      = $isMultiple ? $file['tmp_name'][$i] : $file['tmp_name'];

            if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf') {
                return ['success' => false, 'message' => 'Only PDF allowed'];
            }

            $filename = uniqid('pdf_', true) . '.pdf';
            $fullPath = $basePath . $filename;

            if (!move_uploaded_file($tmpName, $fullPath)) {
                return ['success' => false, 'message' => 'File move failed'];
            }

            $paths[] = '/' . trim($dir, '/') . '/' . $filename;
        }

        return [
            'success' => true,
            'path'    => $paths
        ];
    }

    /**
     * Retrieves the full name of a user with the given ID.
     *
     * @param int $userId The ID of the user
     * @return string The full name of the user, or 'User' if not found
     */
    private function getUserName($userId)
    {
        $userDetails = get_elements('users', ['id' => $userId]);
        $user = $userDetails[0] ?? [];
        $userFullName = $user['fn'] . ' ' . $user['ln'];

        return $userFullName ?? 'User';
    }

    /**
     * Returns the name of a model given its ID.
     * If the model does not exist, returns 'Your AI Model'.
     *
     * @param int $modelId The ID of the model.
     *
     * @return string The name of the model.
     */
    private function getModelName($modelId)
    {
        global $modelsTable;

        $conds = ['id' => $modelId];

        $modelName = get_elements(
            $modelsTable,
            $conds,
            "$modelsTable.name",
            "LIMIT 1"
        );

        $modelName = !empty($modelName) ? $modelName[0]['name'] : 'Your AI Model';
        return $modelName;
    }

    /**
     * Returns the email address of the user with the given ID.
     *
     * @param int $userId The ID of the user
     * @return string The email address of the user, or an empty string if not found
     */
    private function getUserEmail($userId)
    {
        $userDetails = get_elements('users', ['id' => $userId]);
        $user = $userDetails[0] ?? [];
        $userEmail = $user['email'] ?? '';

        return $userEmail;
    }
}
