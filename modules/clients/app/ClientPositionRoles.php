<?php

class ClientPositionRoles
{
    function __construct() {}
    public function process($cmd, $data)
    {

        $response = "";

        switch ($cmd) {
            case 'gets':
                $response = $this->list($data);
                break;
            case 'edit':
                $response = $this->edit($data);
                break;
            case 'saves':
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
        global $auth, $clientPositionRolesTable, $clientPositionsTable;

        if (is_valid($data, 'id')) {

            $conds['id'] = $data['id'];
            $position_roles_by_id  = get_elements($clientPositionRolesTable, $conds, "*", "ORDER BY row_id ASC");

            return send_json_response(true, 200, $this->success['sucess'], ['position_roles' => $position_roles_by_id]);
        } else {

            if (!is_valid($data, 'client_position_id')) {
                $all_position_roles = get_elements($clientPositionRolesTable, [], "*", "ORDER BY row_id ASC");
                return send_json_response(true, 200, $this->success['sucess'], ['position_roles' => $all_position_roles]);
            }

            if (exists($clientPositionsTable, ['id' => $data['client_position_id']])) {
                $conds['*client_position_id'] = $data['client_position_id'];
                $conds['raw'] = ('client_position_id = :client_position_id OR client_position_id IS NULL');
                $position_roles_by_position_id = get_elements($clientPositionRolesTable, $conds, "*", "ORDER BY row_id ASC");

                return send_json_response(true, 200, $this->success['sucess'], ['position_roles' => $position_roles_by_position_id]);
            }
        }

        return send_json_response(false, 400, $this->errors['not_allowed']);
    }

    public function edit($data)
    {
        global $clientPositionRolesTable;

        $response = true;

        return $response;
    }

    public function save($data)
    {
        global  $clientPositionsTable, $clientPositionRolesTable;

        $checkFor = ['value'];

        if (!is_valid($data, 'client_position_id')) {
            return send_json_response(false, 400, $this->errors['not_allowed']);
        }

        if (!exists($clientPositionsTable, ['id' => $data['client_position_id']])) {
            return send_json_response(false, 400, $this->errors['invalid_position_id']);
        }

        $ret = check_missing($checkFor, $data, $this->errors);
        if ($ret !== true) return $ret;

        $data['value'] = trim($data['value']);

        if (exists($clientPositionRolesTable, ['value' => trim($data['value']), 'client_position_id' => $data['client_position_id']])) {
            return send_json_response(false, 400, $this->errors['value_exists']);
        }

        $ret_id = save_element($clientPositionRolesTable, $data);

        if ($ret_id === false) {
            return send_json_response(false, 400, $this->errors['something_wrong']);
        } else {
            return send_json_response(true, 200, $this->success['saved'], ['position_role_id' => $ret_id]);
        }
    }

    public function delete($data)
    {
        global $clientPositionRolesTable;

        $response = true;

        return $response;
    }

    public function set($data)
    {
        global $clientPositionRolesTable;

        $response = true;

        return $response;
    }

    private $errors = [
        "not_allowed" => "Unauthorized Access",
        "value" => "Position role invalid !",
        "value_exists" => "This Value already exists !",
        "invalid_position_id" => "Invalid Position ID !",
        "something_wrong" => "Something went wrong !"
    ];
    private $success = [
        "sucess" => "Success !",
        "saved" => "Position role value saved !",
        "deleted" => "Position role value deleted !",
        "updated" => "Position role value updated !"
    ];
}
