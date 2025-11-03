<?php

class BankUser
{

    private $module_name = 'BankUser';

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
        global $bankUsersTable, $usersTable, $banksTable, $auth;

        $conds = format_conds($data);
        $pagination = format_pagination($data);

        // Restrict to user's own associations unless admin or manager
        $user_role = $auth->role();
        if (!in_array($user_role, ['admin', 'manager'])) {
            $conds['user_id'] = $auth->uid();
        }

        $bank_users = get_elements_join(
            $bankUsersTable,
            $conds,
            "LEFT JOIN $usersTable u ON u.id = $bankUsersTable.user_id
             LEFT JOIN $banksTable b ON b.id = $bankUsersTable.bank_id",
            "$bankUsersTable.*, u.fn, u.ln, u.username, b.bank_name",
            check_val($pagination, 'query')
        );

        if($bank_users === false) return send_json_response(false, 400, $this->errors['not_found']);

        $total = get_last_found_rows();

        return send_json_response(true, 200, $this->success['sucess'], [
            'bank_users' => $bank_users,
            'total' => $total,
            'pagination' => $pagination
        ]);
    }


    public function edit($data)
    {
        global $bankUsersTable, $usersTable, $banksTable;

        if (!isset($data['id'])) {
            return send_json_response(false, 400, 'ID is required');
        }

        $id = trim($data['id']);

        if (empty($id)) {
            return send_json_response(false, 400, 'ID cannot be empty');
        }

        // Check if association exists
        $exists = exists($bankUsersTable, ['id' => $id]);
        if (!$exists) {
            return send_json_response(false, 404, 'Bank-User association not found');
        }

        $bank_user = get_elements_join(
            $bankUsersTable,
            ['id' => $id],
            "LEFT JOIN $usersTable u ON u.id = $bankUsersTable.user_id
             LEFT JOIN $banksTable b ON b.id = $bankUsersTable.bank_id",
            "$bankUsersTable.*, u.fn, u.ln, u.username, b.bank_name"
        );

        if (empty($bank_user)) {
            return send_json_response(false, 404, 'Bank-User association not found');
        }

        return send_json_response(true, 200, $this->success['sucess'], ['bank_user' => $bank_user[0]]);
    }

    public function save($data)
    {
        global $auth, $usersTable, $bankUsersTable;

        if (!isset($data['bank_id']) || !isset($data['user_id'])) {
            return send_json_response(false, 400, 'Bank ID and User ID are required');
        }

        $bank_ids = $data['bank_id'];
        $user_id = trim($data['user_id']);

        if (empty($user_id)) {
            return send_json_response(false, 400, 'User ID cannot be empty');
        }

        // Ensure bank_id is an array
        if (!is_array($bank_ids)) {
            $bank_ids = [$bank_ids];
        }

        // Validate bank_ids are not empty
        $bank_ids = array_filter(array_map('trim', $bank_ids));
        if (empty($bank_ids)) {
            return send_json_response(false, 400, 'At least one Bank ID is required');
        }

        // Check if user exists
        $user_exists = exists($usersTable, ['id' => $user_id]);
        if (!$user_exists) {
            return send_json_response(false, 400, 'User does not exist');
        }

        global $banksTable;
        $errors = [];
        $saved_ids = [];

        foreach ($bank_ids as $bank_id) {
            if (empty($bank_id)) {
                $errors[] = 'Bank ID cannot be empty';
                continue;
            }

            // Check if bank exists
            $bank_exists = exists($banksTable, ['id' => $bank_id]);
            if (!$bank_exists) {
                $errors[] = "Bank with ID $bank_id does not exist";
                continue;
            }

            // Check if association already exists
            $exists = exists($bankUsersTable, ['bank_id' => $bank_id, 'user_id' => $user_id]);
            if ($exists) {
                $errors[] = "Association for Bank ID $bank_id and User ID $user_id already exists";
                continue;
            }

            $save_data = [
                'bank_id' => $bank_id,
                'user_id' => $user_id
            ];

            $result = save_element($bankUsersTable, $save_data);

            if ($result === false) {
                $errors[] = "Error saving association for Bank ID $bank_id";
            } else {
                $saved_ids[] = $result;
            }
        }

        if (!empty($errors)) {
            return send_json_response(false, 400, 'Some associations could not be created', ['errors' => $errors, 'saved_ids' => $saved_ids]);
        }

        return send_json_response(true, 201, 'Bank-User associations created successfully', ['saved_ids' => $saved_ids]);
    }

    public function delete($data)
    {
        global $bankUsersTable;

        if (!isset($data['id'])) {
            return send_json_response(false, 400, 'ID is required');
        }

        $id = trim($data['id']);

        if (empty($id)) {
            return send_json_response(false, 400, 'ID cannot be empty');
        }

        // Check if association exists
        $exists = exists($bankUsersTable, ['id' => $id]);
        if (!$exists) {
            return send_json_response(false, 404, 'Bank-User association not found');
        }

        $result = delete_elements_by_id($bankUsersTable, $id);

        if (!empty($result)) {
            return send_json_response(false, 500, 'Error deleting bank-user association');
        }

        return send_json_response(true, 200, 'Bank-User association deleted successfully');
    }

    public function set($data)
    {
        global $bankUsersTable;

        if (!isset($data['id'])) {
            return send_json_response(false, 400, 'ID is required');
        }

        $id = trim($data['id']);

        if (empty($id)) {
            return send_json_response(false, 400, 'ID cannot be empty');
        }

        // Check if association exists
        $exists = exists($bankUsersTable, ['id' => $id]);
        if (!$exists) {
            return send_json_response(false, 404, 'Bank-User association not found');
        }

        // For set, we might update updated_at or other fields, but since the table has limited fields, perhaps just update timestamp
        $update_data = [
            'updated_at' => time()
        ];

        $result = update_element($bankUsersTable, $update_data, ['id' => $id]);

        if ($result === false) {
            return send_json_response(false, 500, 'Error updating bank-user association');
        }

        return send_json_response(true, 200, 'Bank-User association updated successfully');
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
