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
    public function test_create_without_some_data_fail($user) {
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
    public function test_create_some_required_field_null_fail($user) {   
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
        $someNull['data']['attributes']['object_domain'] = null;
        $this->json('POST', '/checklists', $someNull);
        $this->assertEquals(400, $this->response->getStatusCode());

        $someNull = $fullPayload;
        $someNull['data']['attributes']['object_domain'] = null;
        $this->json('POST', '/checklists', $someNull);
        $this->assertEquals(400, $this->response->getStatusCode());
    }

}
