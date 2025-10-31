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
        global $auth, $bankDetailsTables;

        if (!$auth->islogged()) {
            return send_json_response(false, 401, 'Unauthorized');
        }

        $user_id = $auth->uid();
        $conds = ['user_id' => $user_id];

        if (isset($data['bank_id'])) {
            $conds['bank_id'] = $data['bank_id'];
        }

        $bank_details = get_elements($bankDetailsTables, $conds, '*');
        if (!$bank_details) {
            return send_json_response(false, 400, $this->errors['not_found']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['bank_details' => $bank_details]);
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

        if (!$auth->islogged()) {
            return send_json_response(false, 401, 'Unauthorized');
        }

        $user_id = $auth->uid();

        if (empty($data['bank_id']) || empty($data['type_id'])) {
            return send_json_response(false, 400, 'bank_id and type_id are required');
        }

        if (!exists($banksTable, ['id' => $data['bank_id']])) {
            return send_json_response(false, 400, 'Bank not found');
        }

        // Expected fields
        $field_names = ['duration', 'interest', 'minimum_amount', 'remarks'];

        // Determine number of rows
        $total_rows = isset($data['duration']) ? count($data['duration']) : 0;
        if ($total_rows == 0) {
            return send_json_response(false, 400, 'No deposit entries found');
        }

        $inserted_groups = [];

        // Loop through each row (deposit entry)
        for ($i = 0; $i < $total_rows; $i++) {

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
