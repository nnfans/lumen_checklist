<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

use App\Item;
use QueryHelper;
use ResponseHelper;
use DBHelper;

class ItemController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    private function _getFieldByQuery($selectFields) {
        $selectableFields = [
            'description',
            'due',
            'urgency',
            'assignee_id',
            'task_id',
            'is_completed',
            'completed_at'
        ];

        $metaFields = [
            'id',
            'checklist_id',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
        ];

        $resultField = QueryHelper::selectField($selectableFields, $selectFields);

        $checklistFields = array_merge($metaFields, $resultField);

        return $checklistFields;
    }

    public function _addSortQuery (Builder $builder, $sortQuery = '') {
        $orderableFields = [
            'description',
            'due',
            'urgency',
            'assignee_id',
            'task_id',
            'is_completed',
            'completed_at',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
        ];

        if (strlen($sortQuery) < 1) {
            return $builder;
        }

        if (strpos($sortQuery, ',') === false) {
            $sorts = [ $sortQuery ];
        } else {
            $sorts = explode(',', $sortQuery);
        }

        foreach($sorts as $sort) {
            $desc = substr($sort, 0, 1) === '-';
            if ($desc){
                $sort = substr($sort, 1);
            }
            if (in_array($sort, $orderableFields)) {
                unset($orderableFields[$sort]);
                $builder->orderBy($sort, $desc ? 'DESC' : 'ASC');
            }
        }

        return $builder;
    }

    public function _addFilterQuery(Builder $builder, $filterQueries) {
        $filterableFields = [
            'description',
            'due',
            'urgency',
            'assignee_id',
            'is_completed',
            'completed_at',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
        ];

        if (!is_array($filterQueries)) {
            return $builder;
        }

        foreach ($filterQueries as $field=>$query) {
            if (!is_array($query)) {
                continue;
            }
            $operator = strtolower(array_key_first($query));

            DBHelper::decorateFilter($builder, $field, $query[$operator], $operator);
        }
        
        return $builder;
    }

    public function list (Request $request, $checklistId) {
        $pageLimit = abs($request->query('page')['limit'] ?? 10);
        $pageOffset = abs($request->query('page')['offset'] ?? 0);
        $sorts = $request->query('sort') ?? '';
        $fields = $request->query('fields') ?? null;
        $filters = $request->query('filter') ?? [];

        $viewedFields = $this->_getFieldByQuery($fields);

        $items = Item::where('checklist_id', $checklistId)
            ->select($viewedFields);
        $this->_addFilterQuery($items, $filters);
        $itemTotal = $items->count();

        $items->limit($pageLimit)
            ->offset($pageOffset);

        $this->_addSortQuery($items, $sorts);

        $items = $items->get()->toArray();

        $items = array_map(function($item) use ($request) {
            $id = $item['id'];
            unset($item['id']);
            return [
                    'type' => 'items',
                    'id' => $id,
                    'attributes' => $item,
                    'links' => [
                        'self' => $request->url() . '/' . $id
                    ]
                ];
        }, $items);

        return response()->json([
            'meta' => [
                'count' => count($items),
                'total' => $itemTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks($request->url(), $itemTotal, $pageLimit, $pageOffset),
            'data' => $items
        ]);
    }
}