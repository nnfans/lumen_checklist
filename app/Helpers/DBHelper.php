<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class DBHelper {

    private function __construct() {}

    public static function decorateFilter(Builder $builder, $field, $value, $operator) {
        if ($operator === 'in' || $operator === '!in') {
            $values = explode(',', $value);
            if (count($values) < 1) return $builder;

            if ($operator === 'in') {
                $builder->whereIn($field, $value);
            } else {
                $builder->whereNotIn($field, $value);
            }
        } else {
            $dbOperator = '';
            switch($operator){
                case 'like':
                    $dbOperator = 'LIKE';
                    break;
                case '!like':
                    $dbOperator = 'NOT LIKE';
                    break;
                case 'is':
                    $dbOperator = '=';
                    break;
                case '!is':
                    $dbOperator = '!=';
                    break;
            }
            if ($dbOperator !== '') {

                if ($operator === 'like' || $operator === '!like') {
                    $value = str_replace('*', '%', $value);
                }

                $builder->where($field, $dbOperator, $value);
            }
        }
    }

}