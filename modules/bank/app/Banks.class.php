<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Banks
{

    private $module_name = 'Banks';

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
        global $banksTable, $auth;

        $conds = [];
        if ($auth->islogged()) {
            $user_id = $auth->uid();
            $conds['raw'] = "is_public = 1 OR user_id = :user_id";
            $conds['user_id'] = $user_id;
        } else {
            $conds['is_public'] = true;
        }

        $banks = get_elements($banksTable, $conds, '*');
        if (!$banks){
            return send_json_response(false, 400, $this->errors['not_found']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['banks' => $banks]);
    }

    public function edit($data)
    {
        return true;
    }

    public function save($data)
    {
        global $auth, $bankTypesTable, $usersTable, $banksTable, $upload_dir_banks, $bankUsersTable;

        // $user_id = $data["user_id"];
        $user_id = $data['user_id'] ? $data['user_id'] : '';

        if (!empty($data["type_id"]) && isset($data["type_id"])) {
            $type_id = $data["type_id"] ? $data["type_id"] : '';
        }

        // Handle the uploaded file
        if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileType = $_FILES['file']['type'];

            // Validate file type (Excel or CSV)
            $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/csv'];
            if (!in_array($fileType, $allowedTypes) && !preg_match('/\.(xlsx?|csv)$/i', $fileName)) {
                return send_json_response(false, 400, $this->errors['file_type']);
            }

            try {
                // Load the file
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                // Initialize array to store the parsed data
                $bank_data = [];
                for ($i = 1; $i < count($rows); $i++) {
                    $cells = $rows[$i];

                    // Skip empty rows or rows with insufficient data
                    if (count($cells) < 10 || (empty(trim($cells[0])) && empty(trim($cells[1])))) continue;

                    // Extract the data for each row matching the updated table columns
                    $bank = [
                        'bank_name' => trim($cells[0]),
                        'bank_address' => trim($cells[1]),
                        'bank_address_2' => trim($cells[2] ?? ''),
                        'bank_city' => trim($cells[3] ?? ''),
                        'bank_state' => trim($cells[4] ?? ''),
                        'bank_zip' => trim($cells[5] ?? ''),
                        'contact_person' => trim($cells[6] ?? ''),
                        'contact_person_phone' => trim($cells[7] ?? ''),
                        'contact_person_email' => trim($cells[8] ?? ''),
                        'contact_person_designation' => trim($cells[9] ?? ''),
                        'media' => null,
                    ];

                    // Validate the bank data (ensure bank_name is not empty)
                    if (!empty($bank['bank_name'])) {
                        $bank_data[] = $bank;
                    }
                }

                // // Validate bank type and user
                // if (!exists($bankTypesTable, ['id' => $type_id])) {
                //     return send_json_response(false, 400, 'Bank type not found!');
                // }
                if (!exists($usersTable, ['id' => $user_id])) {
                    return send_json_response(false, 400, 'User not found!');
                }

                // Save the extracted bank data
                foreach ($bank_data as $bank) {

                    // Prepare the bank data to be saved
                    $bank_id = generate_id();
                    $bank['id'] = $bank_id;
                    $bank['user_id'] = $user_id;
                    //$bank['type_id'] = $type_id;

                    // Save the bank information to the database
                    $result = save_element($banksTable, $bank);

                    if ($result === false) {
                        return send_json_response(false, 500, 'Failed to save bank data');
                    }
                }

                // If successful, return the success response
                return send_json_response(true, 200, 'Successfully saved bank data', ['data' => $bank_data]);
            } catch (Exception $e) {
                return send_json_response(false, 500, 'Error processing file: ' . $e->getMessage());
            }
        } else {

            $checkFor = ['user_id', 'bank_name', 'bank_address', 'contact_person', 'contact_person_phone', 'contact_person_email', 'contact_person_designation'];

            if(!empty($_FILES['media'])){
                $image_upload = handle_file_upload($_FILES['media'], $upload_dir_banks);
                // Upload the file
                if ($image_upload['success'] === true) {
                    $image_path = $image_upload['path'];
                    $data['media'] = $image_path;
                }
            }

            $res = check_missing($checkFor, $data, $this->errors);
            if ($res !== true) {
                $res['message'] = 'Missing required fields !';
                return send_json_response(false, 400, null, [$res]);
            }

            if (isset($type_id)) {
                // check if bank type already exists
                if (!exists($bankTypesTable, ['id' => $data['type_id']])) {
                    $error = 'Bank type not found !';
                    return send_json_response(false, 400, $error);
                }
            }

            // check if user exists
            if (!exists($usersTable, ['id' => $data['user_id']])) {
                $error = 'User not found !';
                return send_json_response(false, 400, $error);
            }

            $contact_person_country_code = isset($data['contact_person_country_code']) ? $data['contact_person_country_code'] : '';
            if (!is_numeric($contact_person_country_code)) {
                return send_json_response(false, 400, 'Country code is not valid.');
            }

            $bank_id = generate_id();
            $data['id'] = $bank_id;
            if (isset($type_id)) {
                $data['type_id'] = $type_id;
            }

            $bank_id = save_element($banksTable, $data);

            if ($bank_id === false) {
                return send_json_response(false, 500, $this->errors['save']);
            }

            $user_bank = save_element($bankUsersTable, [
                'bank_id' => $bank_id,
                'user_id' => $user_id,
            ]);

            return send_json_response(true, 200, $this->success['sucess'], ['id' => $bank_id]);
        }

        // If no file was uploaded or the file has an error
        return send_json_response(false, 400, 'File upload error');
    }

    public function delete($data)
    {
        global $banksTable;

        $bank_id = $data['id'];
        $bank_id = delete_elements_by_id($banksTable, $bank_id);

        if ($bank_id === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['id' => $bank_id]);
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
