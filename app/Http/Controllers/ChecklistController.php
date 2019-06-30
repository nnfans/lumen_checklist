<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\Eloquent\Builder;

use App\Checklist;
use QueryHelper;
use ResponseHelper;

class ChecklistController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {}

    private function _getFieldByQuery($selectFields) {
        $selectableFields = [
            'object_domain',
            'object_id',
            'description',
            'urgency',
            'due',
            'completed_at',
            'is_completed',
            'items.description',
            'items.due',
            'items.urgency',
            'items.assignee_id',
            'items.task_id',
            'items.is_completed',
            'items.completed_at'
        ];

        $metaFields = [
            'id',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by',
            'items.id',
            'items.checklist_id',
            'items.created_at',
            'items.updated_at',
            'items.created_by',
            'items.updated_by'
        ];

        $resultField = QueryHelper::selectField($selectableFields, $selectFields);

        $checklistFields = array_merge($metaFields, $resultField);

        return $checklistFields;
    }

    public function _addSortQuery (Builder $model, $sortQuery = '') {
        $orderableFields = [
            'object_domain',
            'object_id',
            'description',
            'urgency',
            'due',
            'is_completed',
            'completed_at',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
        ];

        if (strlen($sortQuery) < 1) {
            return $model;
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
                $model->orderBy($sort, $desc ? 'DESC' : 'ASC');
            }
        }

        return $model;
    }

    public function _addFilterQuery(Model $model, $requestQuery) {
        dd($requestQuery);
    }

    public function list (Request $request) {
        $isIncludeItems = $request->query('include') === 'items';
        $pageLimit = abs($request->query('page')['limit'] ?? 10);
        $pageOffset = abs($request->query('page')['offset'] ?? 0);
        $sorts = $request->query('sort') ?? '';
        $fields = $request->query('fields') ?? null;

        $viewedFields = $this->_getFieldByQuery($fields);

        $checklistFields = array_filter($viewedFields, function ($field) {
            return (strpos($field, 'items.') === FALSE);
        });

        $itemsFields = array_filter($viewedFields, function ($field) {
            return (strpos($field, 'items.') !== FALSE);
        });

        $itemsFields = array_reduce($itemsFields, function($before, $after) {
            return $before . substr($after, strlen('items.')) . ',';
        }, 'items:');

        $itemsFields = substr($itemsFields, 0, strlen($itemsFields) - 1);
        
        $checklistTotal = Checklist::count();
        $checklists = Checklist::select($checklistFields)
            ->limit($pageLimit)
            ->offset($pageOffset);

        if ($isIncludeItems){
            $checklists->with($itemsFields);
        }

        $checklists = $this->_addSortQuery($checklists, $sorts);

        $checklists = $checklists->get()->toArray();

        $checklists = array_map(function($checklist) use ($request) {
            $id = $checklist['id'];
            unset($checklist['id']);
            return [
                    'type' => 'checklists',
                    'id' => $id,
                    'attributes' => $checklist,
                    'links' => [
                        'self' => $request->url() . '/' . $id
                    ]
                ];
        }, $checklists);

        return response()->json([
            'meta' => [
                'count' => count($checklists),
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks($request->url(), $checklistTotal, $pageLimit, $pageOffset),
            'data' => $checklists
        ]);
    }

    public function find (Request $request, $checklistId) {
        $isIncludeItems = $request->query('include') === 'items';
        $fields = $request->query('fields') ?? null;

        $viewedFields = $this->_getFieldByQuery($fields);

        $checklistFields = array_filter($viewedFields, function ($field) {
            return (strpos($field, 'items.') === FALSE);
        });

        $itemsFields = array_filter($viewedFields, function ($field) {
            return (strpos($field, 'items.') !== FALSE);
        });

        $itemsFields = array_reduce($itemsFields, function($before, $after) {
            return $before . substr($after, strlen('items.')) . ',';
        }, 'items:');

        $itemsFields = substr($itemsFields, 0, strlen($itemsFields) - 1);

        $checklist = Checklist::select($checklistFields)
            ->where('id', $checklistId);
        
        if ($isIncludeItems){
            $checklist->with($itemsFields);
        }

        $checklist = $checklist->first();

        if(!$checklist) {
            return errorJson(404);
        }

        return response()->json([
            'data' => [
                'type' => 'checklists',
                'id' => $checklist->id,
                'attributes' => $checklist,
                'links' => [
                    'self' => $request->url() . '/' . $checklist->id
                ]
            ]
        ]);

    }

    public function create (Request $request) {
        $requestObject = json_decode($request->getContent(), true);

        $rules = [
            'object_domain' => 'required|max:100',
            'object_id' => 'required|max:50',
            'description' => 'required|max:300',
            'due' => 'date|nullable',
            'urgency' => 'numeric',
            'items' => 'array',
            'items.*' => 'max:300',
            'task_id' => 'numeric|nullable'
        ];
        $attributes = @$requestObject['data']['attributes'];

        if (empty($attributes)){
            return errorJson(400);
        }

        $validator = Validator::make($attributes, $rules);

        if ($validator->passes()) {

            $checklist = new Checklist();

            $checklist->object_domain = $attributes['object_domain'];
            $checklist->object_id = $attributes['object_id'];
            $checklist->description = $attributes['description'];
            if (!empty($attributes['due'])){
                $checklist->due = (new Carbon($attributes['due']));
            }
            if (!empty($attributes['urgency'])){
                $checklist->urgency = $attributes['urgency'];
            }
            $checklist->created_by = Auth::user()->id;
            $checklist->updated_by = Auth::user()->id;
            $checklist->save();

            $checklist->refresh();

            if (count($attributes['items'] ?? []) > 0) {

                $checklistItems = $attributes['items'];
    
                $checklistItems = array_map(function ($checklistItem) use ($checklist, $attributes) {
                    $item = [
                        'checklist_id' => $checklist->id,
                        'description' => $checklistItem,
                        'created_at' => Carbon::now('UTC'),
                        'created_by' => Auth::user()->id,
                        'updated_by' => Auth::user()->id
                    ];
                    if (!empty($attributes['due'])){
                        $item['due'] = new Carbon($attributes['due']);
                    }
                    if (!empty($attributes['urgency'])){
                        $item['urgency'] = $attributes['urgency'];
                    }
                    if (!empty($attributes['task_id'])){
                        $item['task_id'] = $attributes['task_id'];
                    }
                    return $item;
                }, $checklistItems);
    
                DB::table('items')
                    ->insert($checklistItems);
            }

            return response()->json([
                'data' => [
                    'type' => 'checklists',
                    'id' => $checklist->id,
                    'attributes' => $checklist->toArray(),
                    'links'=> [
                        'self' => $request->url() . '/' . $checklist->id
                    ]
                ]
            ]);
        } else {
            return errorJson(400, $validator->errors()->all());
        }
    }
}
