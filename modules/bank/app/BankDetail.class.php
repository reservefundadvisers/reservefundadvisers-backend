<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class BankDetail
{

    private $module_name = 'BankDetail';

    function __construct() {}

    public function process($cmd, $data)
    {
        $response = "";

        switch ($cmd) {
            case 'get':
                $response = $this->list($data);
                break;
            case 'edit':
                $response = $this->edit($data);
                break;
            case 'save':
                $response = $this->save($data);
                break;
            case 'delete':
                $response = $this->delete($data);
                break;
            case 'set':
                $response = $this->set($data);
                break;
        }

        return $response;
    }

    public function list($data)
    {
        global $auth, $bankDetailsTables, $banksTable, $bankUsersTable, $bankTypesTable;

        $conds = [];

        $user_id = $auth->uid();

        if (!empty($user_id)) {
            $bank_ids = get_elements($bankUsersTable, ['user_id' => $user_id], 'bank_id');
        }

        if (isset($data['bank_id']) && !empty($data['bank_id'])) {
            $bank_ids = [['bank_id' => $data['bank_id']]];
        } else {
            if (!empty($bank_ids)) {
                $conds['bank_id'] = array_column($bank_ids, 'bank_id');
            }
        }

        // Use JOIN to fetch bank_details with type_name
        $join = "LEFT JOIN $bankTypesTable bt ON bd.type_id = bt.id";
        $select = "bd.bank_id, bd.group_id, bd.field_name, bd.field_value, bt.name AS type_name";
        $bank_ids_flat = array_column($bank_ids, 'bank_id');
        $bank_ids_str = implode("','", $bank_ids_flat);
        $extra = "WHERE bd.bank_id IN ('$bank_ids_str') ORDER BY bd.bank_id, bd.group_id";
        $bank_details = get_elements_join($bankDetailsTables . " bd", [], $join, $select, $extra);

        if (!$bank_details) {
            return send_json_response(false, 400, $this->errors['not_found']);
        }

        // Group bank_details by bank_id, then type_name, then pivot to table format
        $grouped = [];
        $temp = [];
        foreach ($bank_details as $detail) {
            $bank_id = $detail['bank_id'];
            $type_name = $detail['type_name'] ?? 'Unknown';
            $group_id = $detail['group_id'];

            if (!isset($temp[$bank_id])) {
                $temp[$bank_id] = [];
            }
            if (!isset($temp[$bank_id][$type_name])) {
                $temp[$bank_id][$type_name] = [];
            }
            if (!isset($temp[$bank_id][$type_name][$group_id])) {
                $temp[$bank_id][$type_name][$group_id] = [];
            }
            $temp[$bank_id][$type_name][$group_id][$detail['field_name']] = $detail['field_value'];
        }

        // Convert to table rows
        foreach ($temp as $bank_id => $types) {
            $grouped[$bank_id] = [];
            foreach ($types as $type_name => $groups) {
                $grouped[$bank_id][$type_name] = [];
                foreach ($groups as $group_id => $fields) {
                    // $grouped[$bank_id][$type_name][] = [
                    //     'duration' => $fields['duration'] ?? '',
                    //     'interest' => $fields['interest'] ?? '',
                    //     'minimum_amount' => $fields['minimum_amount'] ?? '',
                    //     'withdrawal_terms' => $fields['withdrawal_terms'] ?? '',
                    //     'remarks' => $fields['remarks'] ?? ''
                    // ];
                    $row = [];

                    if (!empty($fields['duration'])) {
                        $row['duration'] = $fields['duration'];
                    }
                    if (!empty($fields['interest'])) {
                        $row['interest'] = $fields['interest'];
                    }
                    if (!empty($fields['minimum_amount'])) {
                        $row['minimum_amount'] = $fields['minimum_amount'];
                    }
                    if (!empty($fields['withdrawal_terms'])) {
                        $row['withdrawal_terms'] = $fields['withdrawal_terms'];
                    }
                    if (!empty($fields['remarks'])) {
                        $row['remarks'] = $fields['remarks'];
                    }

                    $grouped[$bank_id][$type_name][] = $row;
                }
            }
        }

        return send_json_response(true, 200, $this->success['sucess'], ['data' => $grouped]);
    }

    public function edit($data)
    {
        global $auth, $bankDetailsTables, $usersTable;

        if (!$auth->islogged()) {
            return send_json_response(false, 401, 'Unauthorized');
        }

        $user_id = $auth->uid();

        if (!isset($data['id'])) {
            return send_json_response(false, 400, 'ID is required for edit');
        }

        // Check if the record exists and belongs to the user
        $existing = get_element($bankDetailsTables, ['id' => $data['id'], 'user_id' => $user_id]);
        if (!$existing) {
            return send_json_response(false, 404, $this->errors['not_found']);
        }

        // Update the record
        $update_data = [];
        if (isset($data['field_name'])) $update_data['field_name'] = $data['field_name'];
        if (isset($data['field_value'])) $update_data['field_value'] = $data['field_value'];

        if (empty($update_data)) {
            return send_json_response(false, 400, 'No fields to update');
        }

        $result = update_element($bankDetailsTables, $update_data, ['id' => $data['id']]);

        if ($result === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['id' => $data['id']]);
    }

    public function save($data)
    {
        global $auth, $bankDetailsTables, $banksTable;

        if (!isset($data['type']) || empty($data['type'])) {
            return send_json_response(false, 400, 'type is required');
        }

        if ($data['type'] === 'cd') {
            $checkFor = ['duration', 'interest', 'minimum_amount', 'remarks'];
            if (isset($data['withdrawal_terms'])) {
                unset($data['withdrawal_terms']);
            }
        } else if ($data['type'] === 'hys') {
            $checkFor = ['duration', 'interest', 'minimum_amount', 'withdrawal_terms', 'remarks'];
        } else {
            return send_json_response(false, 400, 'Invalid type value');
        }

        $res = check_missing($checkFor, $data, $this->errors);
        if ($res !== true) {
            $res['message'] = 'Missing required fields !';
            return send_json_response(false, 400, null, [$res]);
        }

        if (empty($data['bank_id']) || empty($data['type_id'])) {
            return send_json_response(false, 400, 'bank_id and type_id are required');
        }

        if (!exists($banksTable, ['id' => $data['bank_id']])) {
            return send_json_response(false, 400, 'Bank not found');
        }

        // Expected fields
        $field_names = ['duration', 'interest', 'minimum_amount', 'withdrawal_terms', 'remarks'];

        // Determine number of rows
        $total_rows = isset($data['duration']) ? count($data['duration']) : 0;
        if ($total_rows == 0) {
            return send_json_response(false, 400, 'No deposit entries found');
        }

        $inserted_groups = [];

        // Loop through each row (deposit entry)
        for ($i = 0; $i < $total_rows; $i++) {

            // Per-row required field validation
            foreach ($checkFor as $required_field) {
                if (
                    !isset($data[$required_field][$i]) ||
                    trim($data[$required_field][$i]) === ''
                ) {
                    return send_json_response(false, 400, "Missing required field '{$required_field}' for row " . ($i + 1));
                }
            }

            // Generate a group_id for this deposit row
            $group_id = generate_id();

            foreach ($field_names as $field) {
                if (!isset($data[$field][$i])) continue;

                $field_value = trim($data[$field][$i]);
                if ($field_value === '') continue;

                $detail_data = [
                    'id' => generate_id(),
                    'bank_id' => $data['bank_id'],
                    'type_id' => $data['type_id'],
                    'group_id' => $group_id, // reused for each row
                    'field_name' => $field,
                    'field_value' => $field_value
                ];

                $result = save_element($bankDetailsTables, $detail_data);
                if ($result === false) {
                    return send_json_response(false, 500, 'Error saving bank details');
                }
            }

            $inserted_groups[] = $group_id;
        }

        return send_json_response(true, 200, 'Bank details saved successfully', [
            'bank_id' => $data['bank_id'],
            'type_id' => $data['type_id'],
            'total_groups' => count($inserted_groups),
            'group_ids' => $inserted_groups
        ]);
    }

    public function delete($data)
    {
        global $auth, $bankDetailsTables;

        if (!$auth->islogged()) {
            return send_json_response(false, 401, 'Unauthorized');
        }

        $user_id = $auth->uid();

        if (!isset($data['id'])) {
            return send_json_response(false, 400, 'ID is required for delete');
        }

        // Check if the record exists and belongs to the user
        $existing = get_element($bankDetailsTables, ['id' => $data['id'], 'user_id' => $user_id]);
        if (!$existing) {
            return send_json_response(false, 404, $this->errors['not_found']);
        }

        $result = delete_elements_by_id($bankDetailsTables, $data['id']);

        if (!empty($result)) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['id' => $data['id']]);
    }

    public function set($data)
    {
        return true;
    }

    private $errors = [
        "not_found" => "Data not found !",
        'save' => 'Unable to save data !',
        'file_type' => 'Invalid file type. Only Excel (.xlsx, .xls) and CSV files are allowed.',
    ];

    private $success = [
        'sucess' => 'Success !'
    ];
}
