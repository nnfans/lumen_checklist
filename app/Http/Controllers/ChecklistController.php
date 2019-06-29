<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

    public function list (Request $request) {
        $isIncludeItems = $request->query('include') === 'items';
        $pageLimit = $request->query('page[limit]') ?? 10;
        $pageOffset = $request->query('page[offset]') ?? 0;
        $fields = $request->query('fields') ?? null;

        $selectableFields = [
            'object_domain',
            'object_id',
            'description',
            'urgency',
            'due',
            'completed_at',
            'is_completed'
        ];

        $viewedFields = [];

        $metaFields = [
            'id',
            'created_at',
            'updated_at',
            'created_by',
            'updated_by'
        ];

        if ( $fields !== null ){
            $selectFields = explode(',', $fields);
            foreach ($selectFields as $field) {
                if (in_array($field, $selectableFields) && !in_array($field, $viewedFields)) {
                    array_push($viewedFields, $fields);
                }
            }
        }

        if (count($viewedFields) < 1) {
            $viewedFields = $selectableFields;
        }

        $checklistFields = array_merge($viewedFields, $metaFields);

        $checklistFields = array_map(function ($field) {
            return 'checklists.' . $field;
        }, $checklistFields);

        $checklistTotal = DB::table('checklists')
            ->select($checklistFields)
            ->count();

        $checklists = DB::table('checklists')
            ->select($checklistFields)
            ->limit($pageLimit)
            ->offset($pageOffset)
            ->get()
            ->toArray();

        if ($isIncludeItems){
            $checklistIds = array_map(function ($row) {
                return $row->id;
            }, $checklists);
            $items = DB::table('items')
                ->whereIn('checklist_id', $checklistIds)
                ->select('checklist_id', 'description')
                ->get();

            $checklists = array_map(function($checklist) use ($items) {
                $checklistItems = $items->where('checklist_id', $checklist->id)
                    ->pluck('description')
                    ->all();
                $checklist->items = $checklistItems;
                return $checklist;
            }, $checklists);
        }

        $checklists = array_map(function($checklist) use ($request) {
            $id = $checklist->id;
            unset($checklist->id);
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

    public function create (Request $request) {
        $requestObject = json_decode($request->getContent(), true);

        $rules = [
            'object_domain' => 'required|max:100',
            'object_id' => 'required|max:50',
            'description' => 'required|max:300',
            'due' => 'date|nullable',
            'urgency' => 'numeric',
            'items' => 'required|array',
            'items.*' => 'max:300',
            'task_id' => 'numeric|nullable'
        ];
        $attributes = @$requestObject['data']['attributes'];

        if (empty($attributes)){
            return response()->json([
                'status' => 400,
                'error' => 'Bad Request']);
        }

        $validator = Validator::make($attributes, $rules);

        if ($validator->passes()) {

            $createdChecklist = [
                'object_domain' => $attributes['object_domain'],
                'object_id' => $attributes['object_id'],
                'description' => $attributes['description'],
                'due' => $attributes['due'] ?
                    (new Carbon($attributes['due'])) : null,
                'urgency' => $attributes['urgency'] ?? null,
                'created_at' => Carbon::now(),
                'updated_at' => null,
                'created_by' => Auth::user()->id,
                'updated_by' => null
            ];

            $insertId = DB::table('checklists')
                ->insertGetId($createdChecklist);

            $returnChecklist  = $createdChecklist;
            $returnChecklist['due'] = (new Carbon($attributes['due']))->toIso8601String();
            $returnChecklist['created_at'] = (new Carbon($attributes['due']))->toIso8601String();
            $returnChecklist['task_id'] = $attributes['task_id'] ?? null;
            $returnChecklist['is_completed'] = $attributes['is_completed'] ?? null;
            $returnChecklist['completed_at'] = $attributes['completed_at'] ?? null;

            $checklistItems = $attributes['items'];

            $checklistItems = array_map(function ($item) use ($insertId, $attributes) {
                return [
                    'checklist_id' => $insertId,
                    'description' => $item,
                    'due' => $attributes['due'] ?
                        (new Carbon($attributes['due'])) : null,
                    'urgency' => $attributes['urgency'] ?? null,
                    'task_id' => $attributes['task_id'],
                    'created_at' => Carbon::now(),
                    'created_by' => Auth::user()->id,
                ];
            }, $checklistItems);

            DB::table('items')
                ->insert($checklistItems);

            return response()->json([
                'data' => [
                    'type' => 'checklists',
                    'id' => $insertId,
                    'attributes' => $returnChecklist,
                    'links'=> [
                        'self' => $request->url() . '/' . $insertId
                    ]
                ]
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'error' => 'Bad Request',
                'message' => $validator->errors()->all()], 400);
        }
    }
}
