<?php

class QueryHelper {

    private function __construct() {}

    public static function selectField($fields, $selected, $selectAllDefault = true) {
        $resultField = [];
        $selectFields = explode(',', $selected);
    
        foreach ($selectFields as $field) {
            if (in_array($field, $fields) && !in_array($selected, $resultField)) {
                array_push($resultField, $field);
            }
        }

        if (!$selected && $selectAllDefault) {
            $resultField = $fields;
        }

        return $resultField;
    }

    public static function getAllFilterQuery($requestQuery) {
        
    }
}
