<?php

use App\User;
use App\Checklist;
use App\Item;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;


class ChecklistsTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        Model::unguard();
    }

    public function test_auth_api_token() {
        $user = new User();

        $user->id = 1;

        $this->actingAs($user);
        $this->json('GET', '/');
        $this->assertEquals(200, $this->response->getStatusCode());
        return $user;
    }

    /**
     * @depends test_auth_api_token
     */
    public function test_create_checklist_without_some_data_fail($user) {
        $this->actingAs($user);

        $exData = [];
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

        $exData['data'] = null;
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

        $exData['data']['attributes'] = null;
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

        $exData['data']['attributes']['object_domain'] = "Test Fail";
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

        $exData['data']['attributes']['object_id'] = 1;
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

        $exData['data']['attributes']['description'] = "Test fail checklist";
        unset($exData['data']['attributes']['description']);
        $this->json('POST', '/checklists', $exData);
        $this->assertEquals(400, $this->response->getStatusCode());

    }

    /**
     * @depends test_auth_api_token
     */
    public function test_create_checklist_with_some_required_field_null_fail($user) {
        $this->actingAs($user);

        $fullPayload = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'Test Fail',
                    'object_id' => 1,
                    'description' => 'Test fail checklist'
                ]
            ]
        ];

        $someNull = $fullPayload;
        $someNull['data']['attributes']['object_domain'] = null;
        $this->json('POST', '/checklists', $someNull);
        $this->assertEquals(400, $this->response->getStatusCode());

        $someNull = $fullPayload;
        $someNull['data']['attributes']['object_id'] = null;
        $this->json('POST', '/checklists', $someNull);
        $this->assertEquals(400, $this->response->getStatusCode());

        $someNull = $fullPayload;
        $someNull['data']['attributes']['description'] = null;
        $this->json('POST', '/checklists', $someNull);
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    /**
     * @depends test_auth_api_token
     */
    public function test_create_checklist_without_item_success($user) {
        $this->actingAs($user);

        $payload = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'Test Success',
                    'object_id' => 1,
                    'description' => 'Success 1'
                ]
            ]
        ];

        $expStructure = [
            'data' => [
                'type',
                'id',
                'attributes',
                'links'
            ]
        ];

        $this->json('POST', '/checklists', $payload);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeInDatabase('checklists', $payload['data']['attributes']);

        $payload['data']['attributes']['description'] = "Sedap isi 2";
        $payload['data']['attributes']['due'] = "2019-06-30T07:50:14+00:00";
        $payload['data']['attributes']['urgency'] = 1;

        $this->json('POST', '/checklists', $payload);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeInDatabase('checklists', $payload['data']['attributes']);
    }

    /**
     * @depends test_auth_api_token
     */
    public function test_create_checklist_with_items_success($user) {
        $this->actingAs($user);

        $items = [
            'Success 1.1',
            'Success 1.2',
            'Success 1.3'
        ];

        $payload = [
            'data' => [
                'attributes' => [
                    'object_domain' => 'Test Success Item',
                    'object_id' => 1,
                    'description' => 'Success 1',
                    'items' => $items
                ]
            ]
        ];

        $expStructure = [
            'data' => [
                'type',
                'id',
                'attributes',
                'links'
            ]
        ];

        // Without due and urgency
        $this->json('POST', '/checklists', $payload);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $expData = $payload['data']['attributes'];
        unset($expData['items']);
        $this->seeInDatabase('checklists', $expData);
        foreach ($items as $item) {
            $this->seeInDatabase('items', ['description' => $item]);
        }

        $items = [
            'Success 2.1',
            'Success 2.2',
            'Success 2.3'
        ];

        // With due and urgency
        $payload['data']['attributes']['description'] = "Sedap isi 2";
        $payload['data']['attributes']['due'] = "2019-06-30T07:50:14+00:00";
        $payload['data']['attributes']['urgency'] = 1;
        $payload['data']['attributes']['items'] = $items;
        $this->json('POST', '/checklists', $payload);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $expData = $payload['data']['attributes'];
        unset($expData['items']);
        $this->seeInDatabase('checklists', $expData);
        foreach ($items as $item) {
            $this->seeInDatabase('items', ['description' => $item]);
        }

    }

    /**
     * @depends test_auth_api_token
     */
    public function test_list_checklists_without_items_success($user) {
        $this->actingAs($user);

        $rootUrl = env('APP_URL', 'http://localhost');

        $checklistDecorator = function($checklist) use ($rootUrl) {
            $id = $checklist['id'];
            unset($checklist['id']);
            return [
                'type' => 'checklists',
                'id' => $id,
                'attributes' => $checklist,
                'links' => [
                    'self' => $rootUrl .'/checklists/' . $id
                ]
            ];
        };

        factory('App\Checklist', 50)->create();

        $payload = [
            'page[offset]' => 0,
            'page[limit]' => 10
        ];

        $expStructure = [
            'meta' => [
                'count',
                'total'
            ],
            'links' => [
                'first',
                'last',
                'next',
                'prev'
            ],
            'data' => [[
                'type',
                'id',
                'attributes' => [
                    'object_domain',
                    'object_id',
                    'description',
                    'urgency',
                    'task_id',
                    'completed_at',
                    'is_completed',
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at'

                ]
            ]]
        ];

        // Basic request list

        $route = route('checklists.list', $payload);

        $checklistTotal = Checklist::count();
        $checklists = array_map($checklistDecorator, Checklist::limit(10)->offset(0)->get()->toArray());
        
        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => count($checklists),
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 10, 0),
            'data' => $checklists
        ]);


        // Change payload page offset

        $payload['page[offset]'] = 20;
        $payload['page[limit]'] = 10;

        $route = route('checklists.list', $payload);

        $checklists = array_map($checklistDecorator, Checklist::limit(10)->offset(20)->get()->toArray());

        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => $checklistTotal > 10 ? 10 : $checklistTotal,
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 10, 20),
            'data' => $checklists
        ]);


        // Try sort parameter

        $payload['sort'] = 'object_id';

        $route = route('checklists.list', $payload);

        $checklists = array_map($checklistDecorator, Checklist::limit(10)->offset(20)->orderBy('object_id')->get()->toArray());

        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => $checklistTotal > 10 ? 10 : $checklistTotal,
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 10, 20),
            'data' => $checklists
        ]);
     }


    /**
     * @depends test_auth_api_token
     */
    public function test_list_checklists_with_items_success($user) {
        $this->actingAs($user);

        $rootUrl = env('APP_URL', 'http://localhost');

        $checklistDecorator = function($checklist) use ($rootUrl) {
            $id = $checklist['id'];
            unset($checklist['id']);
            return [
                'type' => 'checklists',
                'id' => $id,
                'attributes' => $checklist,
                'links' => [
                    'self' => $rootUrl .'/checklists/' . $id
                ]
            ];
        };

        factory('App\Checklist', 50)
            ->create()
            ->each(function($checklist) {
                $checklist->items()->createMany(
                    factory('App\Item', 2)->make()->toArray()
                );
            });

        $payload = [
            'page[offset]' => 0,
            'page[limit]' => 5,
            'include' => 'items'
        ];

        $expStructure = [
            'meta' => [
                'count',
                'total'
            ],
            'links' => [
                'first',
                'last',
                'next',
                'prev'
            ],
            'data' => [[
                'type',
                'id',
                'attributes' => [
                    'object_domain',
                    'object_id',
                    'description',
                    'urgency',
                    'task_id',
                    'completed_at',
                    'is_completed',
                    'items' => [[
                        'id',
                        'description',
                        'due',
                        'urgency',
                        'assignee_id',
                        'task_id',
                        'is_completed',
                        'completed_at'
                    ]],
                    'created_by',
                    'updated_by',
                    'created_at',
                    'updated_at'

                ]
            ]]
        ];

        // Basic request list

        $route = route('checklists.list', $payload);

        $checklistTotal = Checklist::count();
        $checklists = array_map($checklistDecorator, Checklist::with('items')->limit(5)->offset(0)->get()->toArray());

        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => count($checklists),
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 5, 0),
            'data' => $checklists
        ]);


        // Change payload page offset

        $payload['page[offset]'] = 20;

        $route = route('checklists.list', $payload);

        $checklists = array_map($checklistDecorator, Checklist::with('items')->limit(5)->offset(20)->get()->toArray());

        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => count($checklists),
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 5, 20),
            'data' => $checklists
        ]);


        // Try sort parameter

        $payload['sort'] = 'object_id';

        $route = route('checklists.list', $payload);

        $checklists = array_map($checklistDecorator, Checklist::with('items')->limit(5)->offset(20)->orderBy('object_id')->get()->toArray());

        $this->json('GET', $route);
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure($expStructure);
        $this->seeJsonEquals([
            'meta' => [
                'count' => count($checklists),
                'total' => $checklistTotal
            ],
            'links' => ResponseHelper::generatePaginationLinks( $rootUrl . '/checklists', $checklistTotal, 5, 20),
            'data' => $checklists
        ]);
    }
     
}
