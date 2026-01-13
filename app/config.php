<?php

require('server.php');

// Dynamic CORS origin handling
$allowed_origins = [
    'http://localhost:5173',
    'http://localhost:5174',
    'https://absd.frontend.reservefundadvisors.com',
    'http://absd.frontend.reservefundadvisors.com',
    rtrim('https://absd.frontend.reservefundadvisors.com', '/'),  // Add production domains as needed
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
    header("Vary: Origin");
} else {
    // Fallback for development - be cautious with this in production
    header("Access-Control-Allow-Origin: *");
    // Note: Cannot use credentials with wildcard
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Auth-Session, auth-session, cookie");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(); // Allow preflight to succeed, but do not block real requests
}
  
// ------------------------
// Auth Configuration :
// ------------------------

$auth_conf['site_name'] = $app_name; // Name of website to appear in emails
$auth_conf['email_from'] = "anass.wakrim@gmail.com"; // Email FROM address for Auth emails (Activation, password reset...)
$auth_conf['max_attempts'] = 5; // INT : Max number of attempts for login before user is locked out
$auth_conf['base_url'] = $base_url; // URL to Auth Class installation root WITH trailing slash
$auth_conf['session_duration'] = "+1 month"; // Amount of time session lasts for. Only modify if you know what you are doing ! Default = +1 month
$auth_conf['security_duration'] = "+30 minutes"; // Amount of time to lock a user out of Auth Class afetr defined number of attempts.
$auth_conf['session_auth_duration'] = "+15 minutes"; // Amount of time to lock a user out of Auth Class afetr defined number of attempts.

$auth_conf['salt_1'] = "fr_2FD4Fd8-99"; // Salt #1 for password encryption
$auth_conf['salt_2'] = "Ss96er58-f!"; // Salt #2 for password encryption

$loc = "en"; // Language of Auth Class output : en / fr


// ------------------------
// roles :
// ------------------------

$roles = ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'client_user', ];
$client_roles = ['company_admin', 'company_user', 'client_admin', 'client_user'];

// roles human readable names
$roles_name['company_admin'] = 'administrator';
$roles_name['company_user'] = 'user';
$roles_name['client_admin'] = 'administrator';
$roles_name['client_user'] = 'user';
$roles_name['manager'] = 'manager';
$roles_name['admin'] = 'superuser';

// pages allowed for each role
$roles_pages['company_admin'] = [ 'simulation', 'models', 'client_users', 'clients' ];
$roles_pages['company_user'] = [ 'simulation' ];
$roles_pages['client_admin'] = [ 'simulation', 'models', 'client_users' ];
$roles_pages['client_user'] = [ 'simulation' ];
$roles_pages['manager'] =  [ 'clients', 'models', 'simulation' ];
$roles_pages['admin'] =  [  'clients', 'models', 'users', 'ltim', 'simulation' ];

// role badge color
$roles_badge['company_admin']  =  'w3-teal';
$roles_badge['company_user']  =  'w3-brown';
$roles_badge['client_admin']  =  'w3-pink';
$roles_badge['client_user']  =  'w3-green';
$roles_badge['manager'] =  'w3-deep-orange';
$roles_badge['admin']   =  'w3-purple';

// role landing page
$roles_landing['company_admin'] = 'index';
$roles_landing['company_user'] = 'index';
$roles_landing['client_admin'] = 'index';
$roles_landing['client_user'] = 'index';
$roles_landing['manager'] = 'index';
$roles_landing['admin'] = 'index';


// page name to url
$pages =   [
    
    'index'     => $base_web.'/index.php',
    'login'     => $base_web.'/login.php',
    'auth_validate'     => $base_web.'/auth_validate.php',
    
    // client
    'client_admin'  => $base_web.'/models.php',
    'client_user'  => $base_web.'/simulation.php',
    'client_users'  => $base_web.'/client_users.php',

    // admin    
    'clients'  => $base_web.'/clients.php',
    'models'  => $base_web.'/models.php',
    'users'  => $base_web.'/users.php',
    'ltim'  => $base_web.'/ltim.php',
    
    // all
    'simulation'  => $base_web.'/simulation.php',
];


//-----------------
// Database Tables
//-----------------


    
// db table name used for this script


$usersTable = 'users';
$clientsTable = 'clients';
$clientPositionsTable = 'client_positions';
$clientPositionRolesTable = 'client_position_roles';

$modelsTable = 'models';
$modelItemsTable = 'model_items';
$modelItemCategoriesTable = 'model_item_categories';
$simActualTable = 'simulation_actual';
$simSplitsTable = 'simulation_splits';
$simDeficitTable = 'simulation_deficit';
$simSplitsLTIMTable = 'simulation_splits_ltim';
$simDeficitLTIMTable = 'simulation_deficit_ltim';
$simRulesTable = 'simulation_rules';
$simVersionTable = 'simulation_versions';
$simMonthlyItems = 'simulation_item_monthly';

$banksTable = 'banks';
$bankTypesTable = 'bank_type';
$bankDetailsTables = 'bank_details';
$bankUsersTable = 'user_banks';

$configTable = 'config';
$checkoutsTable = 'checkouts';

$aiDocumentsTable = 'ai_documents';

// OpenAI Configuration - These will be loaded by AI_Model class
$assistantId = '';
$apiKey = '';

//File upload directory
$upload_dir_association = 'uploads/associations/';
$upload_dir_banks = 'uploads/banks/';

$investment_strategies = [  'cd'=>['name'=>'Certificat Of Deposit (CD)', 'show_model'=>1, 'hold'=>1, 'opt'=>['pd', 'pm']],
                            // 'cdars'=>['name'=>'CD Account Registry Service (CDARS)', 'show_model'=>1, 'hold'=>1, 'opt'=>['pd', 'pm']],
                            'tb'=>['name'=>'Treasury Bonds (T-Bonds)', 'show_model'=>1, 'hold'=>1, 'opt'=>['pd', 'pm']],
                            'smp'=>['name'=>'Simple Intrest', 'show_model'=>0, 'end_of_year'=>0],
                            'bbp'=>['name' => 'Bank Intrest Income', 'end_of_year'=>0]];



$db_table_cols = [ 

                    $usersTable => ['id', 'username', 'password', 'role', 'client_id', 'position_id','position_role_id', 'fn', 'ln', 'email', 'country_code', 'phone', 'address', 'address2', 'city', 'zip', 'state', 'opt-in', 'active', 'invite_token', 'created_at'],
                    $clientsTable => ['id', 'association','association_property_manager_name', 'company', 'company_id','media', 'type', 'company_type', 'email','country_code', 'phone', 'address', 'address2', 'city', 'zip', 'state', 'association_style','active', 'created_at'],
                    $clientPositionsTable => ['row_id', 'id', 'value', 'client_id'], 
                    $clientPositionRolesTable => ['row_id', 'id', 'value', 'client_position_id'],
                    $modelsTable => ['row_id', 'id', 'name', 'client_id', 'housing', 'starting_amount', 'inflation_rate', 'investment_rate_of_return', 'monthly_fees', 'monthly_fees_rate', 'cushion_fund', 'period', 'bank_int_rate', 'bank_rate', 'loan_years', 'fiscal_year', 'inv_strategy', 'annual_sirs_fees','total_reserve_fees_onhand','annual_reserve_fees','total_sirs_fund_onhand' ,'active', 'updated_at', 'created_at'],
                    $modelItemsTable => ['row_id', 'id', 'parent_id', 'model_id', 'name', 'redundancy', 'remaining_life', 'cost', 'estimated_cost', 'actual_cost', 'item_category_id ', 'is_sirs','item_type'],
                    $modelItemCategoriesTable => ['row_id', 'id', 'name'],
                    $simActualTable => ['row_id', 'id', 'item_id', 'model_id', 'redundancy_at', 'actual_cost'],
                    $simSplitsTable => ['row_id', 'id', 'model_id', 'parent_id', 'user_id', 'split_of', 'redundancy_at', 'year', 'cost'],
                    $simDeficitTable => ['row_id', 'id', 'model_id', 'user_id', 'year', 'to', 'data'],
                    $simSplitsLTIMTable => ['row_id', 'id', 'model_id', 'parent_id', 'user_id', 'split_of', 'redundancy_at', 'year', 'cost'],
                    $simDeficitLTIMTable => ['row_id', 'id', 'model_id', 'user_id', 'year', 'to', 'data'],
                    $simRulesTable => ['row_id', 'id', 'model_id', 'user_id', 'rules'],
                    $simVersionTable => ['row_id', 'id', 'model_id', 'user_id', 'name', 'note', 'can_view', 'can_load', 'data', 'created_at'],
                    $simMonthlyItems => ['row_id', 'id', 'model_id', 'item_id', 'occurrence','month', 'year', 'created_by', 'amount'],
                    $configTable => ['row_id', 'id', 'param', 'value'], 
                    $checkoutsTable => ['row_id', 'id', 'order', 'type', 'details', 'status', 'created_at'],

                    $banksTable => ["row_id", "id", "type_id", "user_id","website", "media", "bank_name", "bank_address", "bank_address_2", "bank_city", "bank_state", "bank_zip", "contact_person","contact_person_country_code", "contact_person_phone", "contact_person_email",
                                    "contact_person_designation", "remarks", "is_public", "created_at", "updated_at",],
                    $bankTypesTable => ['row_id', 'id', 'name', 'keyword', 'description', 'active', 'created_at', 'updated_at'],
                    $bankDetailsTables => ['row_id', 'id', 'bank_id', 'user_id' ,'type_id', 'group_id', 'field_name',	'field_value', 'created_at', 'updated_at'],
                    $bankUsersTable => ['row_id', 'id', 'bank_id', 'user_id', 'created_at', 'updated_at'],

                    $aiDocumentsTable => ['row_id', 'id', 'user_id','model_id', 'client_id', 'pdf_path', 'ai_chat_pdfs','assistant_id', 'thread_id', 'run_id', 'openai_file_id', 'extracted_json', 'conversation', 'run_payload', 'error_message', 'created_at', 'completed_at', 'status', 'is_admin_chat_only','admin_approval_status'],
                ];



$roles_menu['admin'] =  [ 
    ["header"=>"apps", "items"=>[
                                        ["ic"=>"chart-area", "txt"=>"simulation", "link"=>"simulation"],
                                    ]],
    ["header"=>"associations", "items"=>[
                                        ["ic"=>"users", "txt"=>"associations", "link"=>"clients"],
                                        ["ic"=>"chart-bar", "txt"=>"models", "link"=>"models"]
                                    ]],
    ["header"=>"settings", "items"=>[
                                        ["ic"=>"users", "txt"=>"assistants", "link"=>"users"],
                                        ["ic"=>"percent", "txt"=>"LTIM Settings", "link"=>"ltim"],
                                    ]]
];

$roles_menu['manager'] =  [ 
    ["header"=>"apps", "items"=>[
                                        ["ic"=>"chart-area", "txt"=>"simulation", "link"=>"simulation"],
                                    ]],
    ["header"=>"clients", "items"=>[
                                        ["ic"=>"users", "txt"=>"clients", "link"=>"clients"],
                                        ["ic"=>"chart-bar", "txt"=>"models", "link"=>"models"]
                                    ]]
];



$roles_menu['client_admin'] =  [ 
    ["header"=>"apps", "items"=>[
                                        ["ic"=>"chart-area", "txt"=>"simulation", "link"=>"simulation"],
                                    ]],
    ["header"=>"clients", "items"=>[
                                        ["ic"=>"chart-bar", "txt"=>"models", "link"=>"models"]
                                    ]],
    ["header"=>"settings", "items"=>[
                                        ["ic"=>"users", "txt"=>"users", "link"=>"client_users"],
                                    ]]
];



$roles_menu['client_user'] =  [ 
    ["header"=>"apps", "items"=>[
                                        ["ic"=>"chart-area", "txt"=>"simulation", "link"=>"simulation"],
                                    ]]
];
?>


