<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

use App\Checklist;
use QueryHelper;

class ChecklistController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

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
            'items.due'
        ];

        $metaFields = [
            'id',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by',
            'items.id',
            'items.checklist_id'
        ];

        $resultField = QueryHelper::selectField($selectableFields, $selectFields);

        $checklistFields = array_merge( $metaFields, $resultField);

        return $checklistFields;
    }

    public function _addFilterQuery(Model $model, $requestQuery) {
        dd($requestQuery);
    }

    public function list (Request $request) {
        $isIncludeItems = $request->query('include') === 'items';
        $pageLimit = $request->query('page')['limit'] ?? 10;
        $pageOffset = $request->query('page')['offset'] ?? 0;
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
            'links' => [
                'first' => $request->url() . '?page[limit]=' . $pageLimit . '&page[offset]=0',
                'last' => $request->url() . '?page[limit]=' . $pageLimit . '&page[offset]=' .
                    (intdiv($checklistTotal - 1, $pageLimit)) * $pageLimit,
                'next' => (intdiv($checklistTotal - 1, $pageLimit) * $pageLimit >= $pageOffset + $pageLimit) ?
                    ($request->url() . '?page[limit]=' . $pageLimit . '&page[offset]=' . ($pageOffset + $pageLimit)) : null,
                'prev' => ($pageOffset - $pageLimit >= 0) ?
                    ($request->url() . '?page[limit]=' . $pageLimit . '&page[offset]=' . ($pageOffset - $pageLimit)) : null,
            ],
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
        
        $checklistTotal = Checklist::count();
        $checklist = Checklist::find($checklistId);

        if ($isIncludeItems){
            $checklist = $checklist->with($itemsFields);
        }

        $checklist = $checklist->first();

        if(!$checklist) {
            return errorJson(404, "Not Found");
        }

        return response()->json([
            'data' => [
                'type' => 'checklists',
                'id' => $checklist->id,
                'attributes' => $checklist
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
