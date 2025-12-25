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
            case 'pubslish':
                return $this->publish($data);
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
        if (!is_valid($data, 'id') || !is_valid($data, 'admin_approval_status') || !is_valid($data, 'user_id')) return send_json_response(false, 400, 'Invalid request');

        $allowed = ['complete'];
        if (!in_array($data['admin_approval_status'], $allowed)) return send_json_response(false, 400, 'Invalid status');

        return send_json_response(true, 200, 'Success', ['message' => 'Published successfully']);
    }

}
