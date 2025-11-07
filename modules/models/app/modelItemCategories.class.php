<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class modelItemCategories
{

    private $module_name = 'modelItemCategories';

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
        global $modelItemCategoriesTable;

        $conds = [];
        if(!empty($data['id'])) {
            $conds['id'] = $data['id'];
        }

        $results = get_elements($modelItemCategoriesTable, $conds, '*');

        return send_json_response(true, 200, 'Categories retrieved successfully', [ 'data' => $results]);
    }

    public function edit($data)
    {
        global $modelItemCategoriesTable;

        if (!is_valid($data, 'id')) {
            return send_json_response(false, 400, 'ID is required');
        }

        // Check if category exists
        $category = get_element($modelItemCategoriesTable, ['id' => $data['id']]);
        if (!$category) {
            return send_json_response(false, 404, $this->errors['not_found']);
        }

        // Prepare update data
        $categoryData = ['id' => $data['id']];
        if (is_valid($data, 'name')) {
            $categoryData['name'] = trim($data['name']);
        }
        // Add other fields if needed, e.g., if (is_valid($data, 'other_field')) { $categoryData['other_field'] = $data['other_field']; }

        // Perform update
        $result = save_element($modelItemCategoriesTable, $categoryData);

        if ($result === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, 'Category updated successfully', ['data' => $result]);
    }

    public function save($data)
    {
        global $modelItemCategoriesTable;

        if (!is_valid($data, 'name')) {
            return send_json_response(false, 400, 'Name is required');
        }

        $categoryData = [
            'name' => trim($data['name'])
        ];

        if (is_valid($data, 'id')) {
            // Update existing
            $categoryData['id'] = $data['id'];
        }

        $result = save_element($modelItemCategoriesTable, $categoryData);

        if ($result === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, $this->success['success'], [ 'data' => $result]);
    }

    public function delete($data)
    {
        global $modelItemCategoriesTable, $modelItemsTable;

        if (!is_valid($data, 'id')) {
            return send_json_response(false, 400, 'ID is required');
        }

        $category = get_element($modelItemCategoriesTable, ['id' => $data['id']]);
        if (!$category) {
            return send_json_response(false, 404, $this->errors['not_found']);
        }

        // Check for associated items
        $associatedItems = get_elements($modelItemsTable, ['item_category_id' => $data['id']]);
        if (!empty($associatedItems)) {
            return send_json_response(false, 400, 'Cannot delete category with associated items');
        }

        $result = delete_elements_by_id($modelItemCategoriesTable, $data['id']);

        if ($result === false) {
            return send_json_response(false, 500, $this->errors['save']);
        }

        return send_json_response(true, 200, 'Category deleted successfully' , [ 'data' => $data['id']]);
    }

    public function set($data)
    {
        global $modelItemCategoriesTable;

        if (!is_valid($data, 'id')) {
            return send_json_response(false, 400, 'ID is required');
        }

        $set_property = set_property($modelItemCategoriesTable, $data);

        return send_json_response(true, 200, $this->success['success'], $set_property);
    }

    private $errors = [
        "not_found" => "Data not found !",
        'save' => 'Unable to save data !',
    ];

    private $success = [
        'success' => 'Success !'
    ];
}
