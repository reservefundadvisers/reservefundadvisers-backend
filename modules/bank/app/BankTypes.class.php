<?php

class BankTypes
{

    private $module_name = 'BankTypes';

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
        global $bankTypesTable;

        $bank_types = get_elements($bankTypesTable, [], '*');
        if(!$bank_types) return send_json_response(false, 400, $this->errors['not_found']);

        return send_json_response(true, 200, $this->success['sucess'], ['bank_types' => $bank_types]);
    }


    public function edit($data)
    {
      return true;
    }

    public function save($data)
    {
        global $auth, $bankTypesTable;

        $checkFor = ['name'];

        $res = check_missing($checkFor, $data, $this->errors);
        if ($res !== true) {
            $res['message'] = 'Missing required fields !';
             return send_json_response(false, 400, null, [ $res ]);
        }

        // check if bank type already exists
        if (exists($bankTypesTable, ['name' => $data['name']])) {
            $error = $this->errors['name_exists'];
            return send_json_response(false, 400, $error);
        }

        $id = generate_id();
        $data['id'] = $id;

        $bank_type_id = save_element($bankTypesTable, $data);

        if ($bank_type_id === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['sucess'], ['id' => $bank_type_id]);
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
        'name_exists' => "Name already exists !",
        'save' => "Error saving bank type !",
        "fn" => "<b>First Name</b> invalid !",
        "ln" => "<b>Last Name</b> invalid !",
        "username" => "<b>Username</b> invalid !",
        "username_exists" => "<b>Username</b> already exists !",
        "password" => "<b>Password</b> invalid !",
        "role" => "<b>Role</b> invalid !",
        "self" => "Operation not allowed on this user",
        "password_short" => "<b>Password</b> must be at least 6 characters !",
        "not_found" => "User profile not found !"
    ];

    private $tr = [
        "admin" => "administrator",
        "manager" => "manager"
    ];

    private $success = [
        'sucess' => 'Success !'
    ];
}
