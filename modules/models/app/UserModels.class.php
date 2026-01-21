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
        }

        return $response;
    }



    /**
     * Grant a user access to a model
     */
    public function grant($data){
        global $db, $auth, $userModelsTable, $modelsTable, $usersTable;

        $user_id = check_val($data, 'user_id', null);
        $model_id = check_val($data, 'model_id', null);

        // Validate required fields
        if(empty($user_id) || empty($model_id)){
            return ['error' => 'User ID and Model ID are required'];
        }

        // Verify the model exists and user has permission
        $model = get_element($modelsTable, ['id' => $model_id]);
        if(!$model){
            return ['error' => 'Model not found'];
        }

        // Check if client has permission to grant access to this model
        if($auth->checkRoleType('client') && $model['client_id'] != $auth->clientId()){
            return ['error' => 'Unauthorized to grant access to this model'];
        }

        // Verify user exists
        $user = get_element($usersTable, ['id' => $user_id]);
        if(!$user){
            return ['error' => 'User not found'];
        }

        // Check if grant already exists
        $existing = get_element($userModelsTable, [
            'user_id' => $user_id,
            'model_id' => $model_id
        ]);

        if($existing){
            return ['error' => 'User already has access to this model'];
        }

        // Create the grant
        $grant_data = [
            'user_id' => $user_id,
            'model_id' => $model_id
        ];

        $result = insert_element($userModelsTable, $grant_data);

        if($result){
            return [
                'success' => true,
                'message' => 'User granted access to model successfully',
                'id' => $db->lastInsertId()
            ];
        }else{
            return ['error' => 'Failed to grant access'];
        }
    }
}

?>
