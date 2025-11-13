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
            case 'get_cd':
                $response = $this->list_cd($data);
                break;
            case 'get_hys':
                $response = $this->list_hys($data);
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
            default:
                $response = send_json_response(false, 400, 'Invalid Command');
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
            return send_json_response(true, 200, $this->success['no_record'], ['data' => []]);
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
                    // // ];
                    // $row = [];

                    // if (!empty($fields['duration'])) {
                    //     $row['duration'] = $fields['duration'];
                    // }
                    // if (!empty($fields['interest'])) {
                    //     $row['interest'] = $fields['interest'];
                    // }
                    // if (!empty($fields['minimum_amount'])) {
                    //     $row['minimum_amount'] = $fields['minimum_amount'];
                    // }
                    // if (!empty($fields['withdrawal_terms'])) {
                    //     $row['withdrawal_terms'] = $fields['withdrawal_terms'];
                    // }
                    // if (!empty($fields['panalty'])) {
                    //     $row['panalty'] = $fields['panalty'];
                    // }
                    //  if (!empty($fields['is_demand_deposit'])) {
                    //     $row['is_demand_deposit'] = $fields['is_demand_deposit'];
                    // }
                    // if (!empty($fields['remarks'])) {
                    //     $row['remarks'] = $fields['remarks'];
                    // }

                    // Define default structure by type
                    if (strtolower($type_name) === 'high yield saving' || strtolower($type_name) === 'hyd') {
                        $defaultFields = [
                            'group_id' => $group_id,
                            'interest' => null,
                            'minimum_amount' => null,
                            'is_demand_deposit' => null,
                            'remarks' => null
                        ];
                    } elseif (strtolower($type_name) === 'certificate of deposit' || strtolower($type_name) === 'cd') {
                        $defaultFields = [
                            'group_id' => $group_id,
                            'duration' => null,
                            'interest' => null,
                            'minimum_amount' => null,
                            'panalty' => null,
                            'remarks' => null
                        ];
                    } else {
                        // Fallback: just include what exists
                        $defaultFields = [];
                    }

                    // Fill in values from $fields (even if 0)
                    foreach ($defaultFields as $key => $val) {
                        if (isset($fields[$key])) {
                            $defaultFields[$key] = $fields[$key];
                        }
                    }

                    $grouped[$bank_id][$type_name][] = $defaultFields;
                }
            }
        }

        // if (isset($data['type'])) {
        //     $filter_type = strtolower($data['type']);
        //     foreach ($grouped as $bank_id => &$types) {
        //         foreach ($types as $type_name => $groups) {
        //             $type_lower = strtolower($type_name);
        //             $matches = false;
        //             if ($filter_type === 'cd' && ($type_lower === 'certificate of deposit' || $type_lower === 'cd')) {
        //                 $matches = true;
        //             } elseif ($filter_type === 'hys' && ($type_lower === 'high yield saving' || $type_lower === 'hys')) {
        //                 $matches = true;
        //             }
        //             if (!$matches) {
        //                 unset($types[$type_name]);
        //             }
        //         }
        //     }
        // }

        return send_json_response(true, 200, $this->success['sucess'], ['data' => $grouped]);
    }

    public function list_cd($data)
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

        // Use JOIN to fetch bank_details with type_name, filter for CD types
        $join = "LEFT JOIN $bankTypesTable bt ON bd.type_id = bt.id";
        $select = "bd.bank_id, bd.group_id, bd.field_name, bd.field_value, bt.name AS type_name";
        $bank_ids_flat = array_column($bank_ids, 'bank_id');
        $bank_ids_str = implode("','", $bank_ids_flat);
        $extra = "WHERE bd.bank_id IN ('$bank_ids_str') AND LOWER(bt.name) IN ('certificate of deposit', 'cd') ORDER BY bd.bank_id, bd.group_id";
        $bank_details = get_elements_join($bankDetailsTables . " bd", [], $join, $select, $extra);

        if (!$bank_details) {
            return send_json_response(true, 200, $this->success['no_record'], ['data' => []]);
        }

        // Group bank_details by bank_id, then group_id (since type is fixed)
        $grouped = [];
        $temp = [];
        foreach ($bank_details as $detail) {
            $bank_id = $detail['bank_id'];
            $group_id = $detail['group_id'];

            if (!isset($temp[$bank_id])) {
                $temp[$bank_id] = [];
            }
            if (!isset($temp[$bank_id][$group_id])) {
                $temp[$bank_id][$group_id] = [];
            }
            $temp[$bank_id][$group_id][$detail['field_name']] = $detail['field_value'];
        }

        // Convert to table rows for CD, flattened without bank_id
        $grouped = [];
        foreach ($temp as $bank_id => $groups) {
            foreach ($groups as $group_id => $fields) {
                $defaultFields = [
                    'group_id' => $group_id,
                    'duration' => null,
                    'interest' => null,
                    'minimum_amount' => null,
                    'panalty' => null,
                    'remarks' => null
                ];

                // Fill in values from $fields (even if 0)
                foreach ($defaultFields as $key => $val) {
                    if (isset($fields[$key])) {
                        $defaultFields[$key] = $fields[$key];
                    }
                }

                $grouped[] = $defaultFields;
            }
        }

        return send_json_response(true, 200, $this->success['sucess'], ['data' => $grouped]);
    }

    public function list_hys($data)
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

        // Use JOIN to fetch bank_details with type_name, filter for HYS types
        $join = "LEFT JOIN $bankTypesTable bt ON bd.type_id = bt.id";
        $select = "bd.bank_id, bd.group_id, bd.field_name, bd.field_value, bt.name AS type_name";
        $bank_ids_flat = array_column($bank_ids, 'bank_id');
        $bank_ids_str = implode("','", $bank_ids_flat);
        $extra = "WHERE bd.bank_id IN ('$bank_ids_str') AND LOWER(bt.name) IN ('high yield saving', 'hys') ORDER BY bd.bank_id, bd.group_id";
        $bank_details = get_elements_join($bankDetailsTables . " bd", [], $join, $select, $extra);

        if (!$bank_details) {
            return send_json_response(true, 200, $this->success['no_record'], ['data' => []]);
        }

        // Group bank_details by bank_id, then group_id (since type is fixed)
        $grouped = [];
        $temp = [];
        foreach ($bank_details as $detail) {
            $bank_id = $detail['bank_id'];
            $group_id = $detail['group_id'];

            if (!isset($temp[$bank_id])) {
                $temp[$bank_id] = [];
            }
            if (!isset($temp[$bank_id][$group_id])) {
                $temp[$bank_id][$group_id] = [];
            }
            $temp[$bank_id][$group_id][$detail['field_name']] = $detail['field_value'];
        }

        // Convert to table rows for HYS, flattened without bank_id
        $grouped = [];
        foreach ($temp as $bank_id => $groups) {
            foreach ($groups as $group_id => $fields) {
                $defaultFields = [
                    'group_id' => $group_id,
                    'interest' => null,
                    'minimum_amount' => null,
                    'is_demand_deposit' => null,
                    'remarks' => null
                ];

                // Fill in values from $fields (even if 0)
                foreach ($defaultFields as $key => $val) {
                    if (isset($fields[$key])) {
                        $defaultFields[$key] = $fields[$key];
                    }
                }

                $grouped[] = $defaultFields;
            }
        }

        return send_json_response(true, 200, $this->success['sucess'], ['data' => $grouped]);
    }

    public function edit($data)
    {
        global $auth, $bankDetailsTables;

        if (empty($data['bank_id']) || empty($data['type_id'])) {
            return send_json_response(false, 400, 'bank_id and type_id are required');
        }

        if (empty($data['group_id'])) {
            return send_json_response(false, 400, 'group_id is required');
        }

        // Determine fields per type
        if ($data['type'] === 'cd') {
            $field_names = ['duration', 'interest', 'minimum_amount', 'panalty', 'remarks'];
        } elseif ($data['type'] === 'hys') {
            $field_names = ['interest', 'minimum_amount', 'is_demand_deposit', 'remarks'];
        } else {
            return send_json_response(false, 400, 'Invalid type value');
        }

        $group_ids = $data['group_id'];
        if (!is_array($group_ids)) {
            $group_ids = [$group_ids];
        }

        $updated = 0;
        $inserted = 0;

        // Loop through each group (row)
        foreach ($group_ids as $i => $group_id) {

            // Load existing records for this group
            $existing_rows = get_elements($bankDetailsTables, [
                'bank_id' => $data['bank_id'],
                'type_id' => $data['type_id'],
                'group_id' => $group_id
            ]);

            // Skip if group not found
            if (empty($existing_rows)) {
                continue;
            }

            foreach ($field_names as $field) {
                if (!isset($data[$field][$i])) continue;

                $value = trim((string)$data[$field][$i]);

                $existing_field = array_filter($existing_rows, function ($row) use ($field) {
                    return $row['field_name'] === $field;
                });

                if (!empty($existing_field)) {
                    // Update existing field
                    $existing_field = array_values($existing_field)[0];
                    $update_result = update_element(
                        $bankDetailsTables,
                        ['field_value' => $value],
                        ['id' => $existing_field['id']]
                    );

                    if ($update_result !== false) {
                        $updated++;
                    }
                } else {
                    // Insert if new field missing
                    $insert_data = [
                        'id' => generate_id(),
                        'bank_id' => $data['bank_id'],
                        'type_id' => $data['type_id'],
                        'group_id' => $group_id,
                        'field_name' => $field,
                        'field_value' => $value
                    ];

                    $insert_result = save_element($bankDetailsTables, $insert_data);
                    if ($insert_result !== false) {
                        $inserted++;
                    }
                }
            }
        }

        if ($updated === 0 && $inserted === 0) {
            return send_json_response(false, 400, 'No fields were updated or inserted');
        }

        return send_json_response(true, 200, 'Bank details updated successfully', [
            'updated' => $updated,
            'inserted' => $inserted,
            'total_groups' => count($group_ids)
        ]);
    }

    public function save($data)
    {
        global $auth, $bankDetailsTables, $banksTable;

        if (!isset($data['type']) || empty($data['type'])) {
            return send_json_response(false, 400, 'type is required');
        }

        if (empty($data['bank_id']) || empty($data['type_id'])) {
            return send_json_response(false, 400, 'bank_id and type_id are required');
        }

        if (!exists($banksTable, ['id' => $data['bank_id']])) {
            return send_json_response(false, 400, 'Bank not found');
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
                // Load spreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                if (count($rows) < 2) {
                    return send_json_response(false, 400, 'No data rows found in the file');
                }

                // Detect the actual header row (skip empty/comment rows)
                $headerRowIndex = null;
                foreach ($rows as $i => $r) {
                    $joined = strtolower(implode(' ', $r));
                    if (strpos($joined, 'interest') !== false) {
                        $headerRowIndex = $i;
                        break;
                    }
                }

                if ($headerRowIndex === null) {
                    return send_json_response(false, 400, 'Header row not found in file');
                }

                $headers = array_map('trim', $rows[$headerRowIndex]);

                // Normalize headers to internal field names
                $headerMap = [
                    'duration (in months)' => 'duration',
                    'apy interest (%)' => 'interest',
                    'minimum amount ($)' => 'minimum_amount',
                    'early withdrawl (%)' => 'panalty',
                    'demand deposit (boolean)' => 'is_demand_deposit',
                    'remarks' => 'remarks'
                ];

                $normalizedHeaders = [];
                foreach ($headers as $header) {
                    $key = strtolower(trim($header));
                    $normalizedHeaders[] = $headerMap[$key] ?? strtolower(str_replace(' ', '_', $key));
                }

                // Data rows start after the header
                $dataRows = array_slice($rows, $headerRowIndex + 1);

                // Expected columns based on type
                if ($data['type'] === 'cd') {
                    $expectedHeaders = ['duration', 'interest', 'minimum_amount', 'panalty', 'remarks'];
                } elseif ($data['type'] === 'hys') {
                    $expectedHeaders = ['interest', 'minimum_amount', 'is_demand_deposit', 'remarks'];
                } else {
                    return send_json_response(false, 400, 'Invalid type value');
                }

                // Verify expected headers exist
                foreach ($expectedHeaders as $col) {
                    if (!in_array($col, $normalizedHeaders)) {
                        return send_json_response(false, 400, "Missing column: $col");
                    }
                }

                $inserted_groups = [];

                // Loop through all data rows
                foreach ($dataRows as $i => $r) {
                    $row = array_combine($normalizedHeaders, $r);
                    if (!$row) continue;

                    // Skip completely empty rows
                    if (empty(array_filter($row))) continue;

                    // Validation per type
                    $checkFor = ($data['type'] === 'cd')
                        ? ['duration', 'interest']
                        : ['interest'];

                    foreach ($checkFor as $required_field) {
                        if (!isset($row[$required_field]) || trim($row[$required_field]) === '') {
                            return send_json_response(false, 400, "Missing required field '{$required_field}' for row " . ($i + 1));
                        }
                    }

                    $group_id = generate_id();

                    // Save each valid field
                    foreach ($expectedHeaders as $field) {
                        $field_value = trim($row[$field] ?? '');
                        if ($field_value === '') continue;

                        $detail_data = [
                            'id' => generate_id(),
                            'bank_id' => $data['bank_id'],
                            'type_id' => $data['type_id'],
                            'group_id' => $group_id,
                            'field_name' => $field,
                            'field_value' => $field_value
                        ];

                        $result = save_element($bankDetailsTables, $detail_data);
                        if ($result === false) {
                            return send_json_response(false, 500, "Error saving row {$i}");
                        }
                    }

                    $inserted_groups[] = $group_id;
                }

                // Success response
                return send_json_response(true, 200, 'File data saved successfully', [
                    'bank_id' => $data['bank_id'],
                    'type_id' => $data['type_id'],
                    'total_groups' => count($inserted_groups),
                    'group_ids' => $inserted_groups
                ]);
            } catch (Exception $e) {
                return send_json_response(false, 500, 'Error processing file: ' . $e->getMessage());
            }
        } else {
            if ($data['type'] === 'cd') {
                $checkFor = ['duration', 'interest'];
                if (isset($data['withdrawal_terms'])) {
                    unset($data['withdrawal_terms']);
                }
            } else if ($data['type'] === 'hys') {
                $checkFor = ['interest'];
            } else {
                return send_json_response(false, 400, 'Invalid type value');
            }

            $res = check_missing($checkFor, $data, $this->errors);
            if ($res !== true) {
                $res['message'] = 'Missing required fields !';
                return send_json_response(false, 400, null, [$res]);
            }

            // Expected fields
            $field_names = ['duration', 'interest', 'minimum_amount', 'withdrawal_terms', 'panalty', 'is_demand_deposit', 'remarks'];

            // Determine number of rows
            $total_rows = isset($data['interest']) ? count($data['interest']) : 0;
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
    }

    public function delete($data)
    {
        global $auth, $bankDetailsTables;

        if (empty($data['bank_id']) || empty($data['type_id'])) {
            return send_json_response(false, 400, 'bank_id and type_id are required');
        }

        // If group_id is passed, handle selective delete
        if (!empty($data['group_id'])) {
            $groupIds = is_array($data['group_id']) ? $data['group_id'] : [$data['group_id']];

            $existing = get_elements($bankDetailsTables, [
                'bank_id' => $data['bank_id'],
                'type_id' => $data['type_id']
            ]);

            if (empty($existing)) {
                return send_json_response(false, 404, 'No records found for given bank/type');
            }

            $errors = [];
            foreach ($groupIds as $gid) {
                $gid = addslashes($gid);
                $condQuery = "bank_id = '" . addslashes($data['bank_id']) . "' AND type_id = '" . addslashes($data['type_id']) . "' AND group_id = '$gid'";
                $res = delete_elements_by_cond($bankDetailsTables, $condQuery);
                if (!empty($res)) {
                    $errors = array_merge($errors, $res);
                }
            }

            if (!empty($errors)) {
                return send_json_response(false, 500, 'Error deleting some group(s)', ['errors' => $errors]);
            }

            return send_json_response(true, 200, 'Selected group(s) deleted successfully', [
                'deleted_groups' => $groupIds
            ]);
        }


        // --- If group_id is NOT passed: delete all records for bank_id + type_id ---
        $existing = get_elements($bankDetailsTables, [
            'bank_id' => $data['bank_id'],
            'type_id' => $data['type_id']
        ]);

        if (empty($existing)) {
            return send_json_response(false, 404, 'No records found for this bank and type');
        }

        // Manually form WHERE clause for both bank_id and type_id
        $condQuery = "bank_id = '" . addslashes($data['bank_id']) . "' AND type_id = '" . addslashes($data['type_id']) . "'";
        $delete_result = delete_elements_by_cond($bankDetailsTables, $condQuery);

        if (!empty($delete_result)) {
            return send_json_response(false, 500, 'Error deleting some records', [
                'errors' => $delete_result
            ]);
        }

        return send_json_response(true, 200, 'All records for this bank and type deleted successfully', [
            'bank_id' => $data['bank_id'],
            'type_id' => $data['type_id']
        ]);
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
        'sucess' => 'Success !',
        'no_record' => 'No records found !'
    ];
}
