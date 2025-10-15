<?php

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
        global $banksTable;
        
        $banks = get_elements($banksTable, [], '*');
        if(!$banks) return send_json_response(false, 400, $this->errors['not_found']);

        return send_json_response(true, 200, $this->success['sucess'], ['bank types' => $banks]);
    }


    public function edit($data)
    {
        return true;
    }

    public function save($data)
    {
        global $auth, $bankTypesTable, $usersTable, $banksTable;

        $checkFor = ['user_id', 'type_id','bank_name', 'bank_address', 'contact_person', 'contact_person_phone', 'contact_person_email', 'contact_person_designation', 'duration_in_months', 'interest_rate', 'minimum_amount', 'remarks'];

        $res = check_missing($checkFor, $data, $this->errors);
        if ($res !== true) {
            $res['message'] = 'Missing required fields !';
             return send_json_response(false, 400, null, [ $res ]);
        }

        // check if bank type already exists
        if (!exists($bankTypesTable, ['id' => $data['type_id']])) {
            $error = 'Bank type not found !';
            return send_json_response(false, 400, $error);
        }

        // check if user exists
        if (!exists($usersTable, ['id' => $data['user_id']])) {
            $error = 'User not found !';
            return send_json_response(false, 400, $error);
        }

        $bank_id = generate_id();
        $data['id'] = $bank_id;

        $bank_id = save_element($banksTable, $data);

        if ($bank_id === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['id' => $bank_id]);
    }

    public function delete($data)
    {
        return true;
    }

    public function set($data)
    {
        return true;
    }

    private $errors = [
        "not_found" => "Data not found !",
        'save' => 'Unable to save data !',
    ];

    private $success = [
        'sucess' => 'Success !'
    ];
}
