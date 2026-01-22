<?php

class UserModels
{  
    private $module_name = 'models';
    
    function __construct()
    {
    }

    public function process($cmd, $data)
    {
        $response = "";

        switch($cmd){
            case 'grant': $response = $this->grant($data); break;
            case 'revoke': $response = $this->revoke($data); break;
            case 'get': $response = $this->list($data); break;
            case 'get_users': $response = $this->get_users_list($data); break;
        }

        return $response;
    }

    public function get_users_list() {
        
    }

    public function list($data) {
        global $db, $auth, $userModelsTable, $modelsTable, $usersTable;

        $user_id = check_val($data, 'user_id', null);

        // Validate required fields
        if(empty($user_id)){
            send_json_response(false, 400, 'Missing required fields');
        }

        // Verify user exists
        $userModels = get_element($userModelsTable, ['id' => $user_id]);
        if(!$userModels){
            send_json_response(false, 400, 'User not found');
        }

        send_json_response(true, 200, 'Success', $userModels);
    }

    /**
     * Grant a user access to a model
     */
    public function grant($data){
        global $db, $auth, $userModelsTable, $modelsTable, $usersTable;

        $user_id = check_val($data, 'user_id', null);
        $model_id = check_val($data, 'model_id', null);
        $client_id = check_val($data, 'client_id', null);

        // Validate required fields
        if(empty($user_id) || empty($model_id)){
            send_json_response(false, 400, 'Missing required fields');
        }

        // Verify the model exists and user has permission
        $model = get_element($modelsTable, ['id' => $model_id]);
        if(!$model){
            send_json_response(false, 400, 'Model not found');
        }

        // Check if client has permission to grant access to this model
        if($auth->checkRoleType('client') && $model['client_id'] != $auth->clientId()){
            send_json_response(false, 403, 'You do not have permission to grant access to this model');
        }

        // Verify user exists
        $user = get_element($usersTable, ['id' => $user_id]);
        if(!$user){
           send_json_response(false, 400, 'User not found');
        }

        // Check if grant already exists
        $existing = get_element($userModelsTable, [
            'user_id' => $user_id,
            'model_id' => $model_id
        ]);

        if($existing){
            send_json_response(false, 400, 'User already has access to this model');
        }

        // Create the grant
        $grant_data = [
            'id' => generate_id(),
            'user_id' => $user_id,
            'model_id' => $model_id,
            'client_id' => $client_id
        ];

        $result = save_element($userModelsTable, $grant_data);

        if($result){
           send_json_response(true, 200, 'Access granted successfully');
        }else{
            send_json_response(false, 500, 'Failed to grant access');
        }
    }

    public function revoke($data){
        global $db, $auth, $userModelsTable, $modelsTable, $usersTable;

        $user_id = check_val($data, 'user_id', null);
        $model_id = check_val($data, 'model_id', null);

        // Validate required fields
        if(empty($user_id) || empty($model_id)){
            send_json_response(false, 400, 'Missing required fields');
        }

        // Verify the model exists and user has permission
        $model = get_element($modelsTable, ['id' => $model_id]);
        if(!$model){
            send_json_response(false, 400, 'Model not found');
        }

        // Check if client has permission to grant access to this model
        if($auth->checkRoleType('client') && $model['client_id'] != $auth->clientId()){
            send_json_response(false, 403, 'You do not have permission to grant access to this model');
        }

        // Verify user exists
        $user = get_element($usersTable, ['id' => $user_id]);
        if(!$user){
           send_json_response(false, 400, 'User not found');
        }

        // Check if grant already exists
        $existing = get_element($userModelsTable, [
            'user_id' => $user_id,
            'model_id' => $model_id
        ]);

        if(!$existing){
            send_json_response(false, 400, 'User does not have access to this model');
        }

        $result = $db->delete($userModelsTable, [
            'user_id'  => $user_id,
            'model_id' => $model_id
        ]);

        if ($result === true) {
            send_json_response(true, 200, 'Access revoked successfully');
        } else {
            send_json_response(false, 500, 'Failed to revoke access');
        }

    }
}

?>
