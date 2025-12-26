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
            default:
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
        global $aiDocumentsTable;

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
                $aiDocumentsTable.status,
                $aiDocumentsTable.admin_approval_status,
                $aiDocumentsTable.pdf_path,
                $aiDocumentsTable.extracted_json",
                "LIMIT 1"
            );

            // get first element if exists, else empty
            $record = !empty($record) ? $record[0] : [];

            return send_json_response(true, 200, 'Success', ['data' => $record]);
        }

        /* ================= LIST ================= */
        $conds = [];

        /* ================= FILTER ================= */
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
            $conds['raw'] = "$aiDocumentsTable.admin_approval_status != 'rejected'";
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
            $aiDocumentsTable,
            $conds,
            "$aiDocumentsTable.id,
            $aiDocumentsTable.user_id,
            $aiDocumentsTable.status,
            $aiDocumentsTable.pdf_path,
            $aiDocumentsTable.admin_approval_status",
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
        global $aiDocumentsTable;

        if (!is_valid($data, 'id') || !is_valid($data, 'publish') || !is_valid($data, 'user_id') || !is_valid($data, 'model_id')) return send_json_response(false, 400, 'Invalid request');
        $status = ['complete', 'in_review'];

        if ($data['publish'] == true) {
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
