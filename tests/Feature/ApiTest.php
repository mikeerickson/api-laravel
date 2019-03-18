<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Class ApiTest
 * @package Tests\Feature
 * @group api
 */
class ApiTest extends TestCase
{
//    use refreshDatabase;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    /** @test */
    public function should_perform_simple_test()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function should_visit_widget_endpoint()
    {
        $response = $this->get('/api/v1/widgets');

        $this->assertSame("widgets", ($response->json()["endpoint"]));

        $this->assertResultHasKey($response, "data");

        $response->assertStatus(200);
    }

    /** @test */
    public function should_return_requested_record()
    {
        $id = 1;
        $response = $this->get("/api/v1/widgets/{$id}");
        $data = $this->getData($response);

        $this->assertSame($id, $data->id);

        $response->assertStatus(200);
    }

    /**
     * @param $response
     * @return mixed
     */
    public function getData($response)
    {
        return json_decode($response->getContent())->data;
    }


}
