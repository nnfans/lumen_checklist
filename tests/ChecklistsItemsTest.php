<?php

use App\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ChecklistsTest extends TestCase
{
    use DatabaseMigrations;

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

}
