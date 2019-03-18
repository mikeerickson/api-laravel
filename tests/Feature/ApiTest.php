<?php

namespace Tests\Feature;

use App\Models\Widget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class ApiTest
 * @package Tests\Feature
 * @group api
 */
class ApiTest extends TestCase
{
    use refreshDatabase;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
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
        factory(Widget::class, 5)->create();

        $response = $this->get('/api/v1/widgets');

        $this->assertSame("widgets", ($response->json()["endpoint"]));

        $this->assertResultHasKey($response, "data");

        $data = $this->getData($response);

        $response->assertStatus(200);
        $this->assertSame(5, count($data));
    }

    /**
     * @param $response
     * @return mixed
     */
    public function getData($response)
    {
        return json_decode($response->getContent())->data;
    }

    /** @test */
    public function should_visit_widget_endpoint_and_return_requested_record()
    {

        $id = factory(Widget::class)->create()->id;

        $response = $this->get("/api/v1/widgets/{$id}");
        $data = $this->getData($response);

        $this->assertSame($id, $data->id);

        $response->assertStatus(200);
    }

}
