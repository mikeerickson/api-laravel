<?php

namespace App\Http\Controllers;

use App\Services\ApiService;
use Illuminate\Http\Request;

/**
 * Class ApiController
 * @package App\Http\Controllers\API
 */
class ApiController extends Controller
{
    /**
     * @var ApiService
     */
    protected $api;
    /**
     * @var string
     */
    protected $endpoint;
    /**
     * @var int
     */
    protected $limit;
    /**
     * @var string
     */
    protected $query;
    /**
     * @var array|string
     */
    protected $queryString;
    /**
     * @var
     */
    protected $statusCode;
    /**
     * @var array|mixed|string
     */
    protected $token;
    /**
     * @var array|mixed|string
     */
    protected $verbose;

    /**
     * ApiController constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->api = new ApiService($request);

        $this->queryString = $request->query();
        $this->requestUri  = $request->getRequestUri();
        $this->endpoint    = $this->api->getEndpoint($request);

        $this->query       = isset($this->queryString['q']) ? $this->queryString['q'] : '';
        $this->limit       = isset($this->queryString['limit']) ? $this->queryString['limit'] : $this->api->trialLimit();
        $this->token       = $this->api->getToken($request);
        $this->verbose     = $this->api->getVerbose($request);

        if ($this->api->isTrialToken($this->token)) {
            if ($this->limit >= $this->api->trialLimit()) {
                $this->limit = $this->api->trialLimit();
            }
        }

        if (is_null($this->limit)) {
            $this->limit = 10;
        }
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function index()
    {
        $response = $this->api->get()->getOriginalContent();

        return $this->respondWithSuccess($response);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $response = $this->api->post($request);

        return $this->respondWithSuccess($response, 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function create(Request $request)
    {
        return $this->store($request);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function show($id)
    {
        $response = $this->api->get($id)->getOriginalContent();

        $data     = collect($response['data']);
        if ($this->verbose) {
            return $this->respond($response, $response['status_code']);
        } else {
            return $this->respondWithSuccess($data, 200);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function update($id, Request $request)
    {
        $response = $this->api->put($id, $request)
            ->getOriginalContent();

        return $this->respond($response, 201);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function edit($id, Request $request)
    {
        return $this->update($id, $request);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function destroy($id)
    {
        $response = $this->api->delete($id);

        return $this->respond($response, 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete($id)
    {
        return $this->destroy($id);
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * @param $data
     * @param int $status_code
     * @param array $headers
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respond($data, $status_code = 200, $headers = [])
    {
        if (!$this->verbose) {
            if (gettype($data) === 'array') {
                $data = $data['data'];
            }
        }

        return response($data, $status_code);
    }

    /**
     * @param $data
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondWithSuccess($data)
    {
        return $this->setStatusCode(200)->respond($data, 200);
    }

    /**
     * @param string $message
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondWithError($message = 'An Error Occurred')
    {
        $this->setStatusCode(403);
        $data = [
            'status'      => 'fail',
            'message'     => $message,
            'api_request' => $this->requestUri
        ];

        return response($data, $this->getStatusCode());
    }

    /**
     * @param string $message
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondNotFound($message = 'Endpoint Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }

    /**
     * @param string $message
     * @param int $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondDeleted($message = 'Endpoint Deleted Successfully', $id = -1)
    {
        return $this->setStatusCode(200)->respond([
            'id'          => (int)$id,
            'message'     => $message,
            'status_code' => $this->getStatusCode(),
            'api_request' => $this->requestUri
        ]);
    }

    /**
     * @param string $message
     * @param null $id
     * @param array $updatedData
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondUpdated($message = 'Endpoint Updated Successfully', $id = null, $updatedData = [])
    {
        $data = [
            'status'      => 'success',
            'message'     => $message,
            'id'          => $id,
            'api_request' => $this->requestUri
        ];

        if (sizeof($updatedData) > 0) {
            $data['data'] = $updatedData;
        }

        return response($data, $this->getStatusCode());
    }

    /**
     * @param string $message
     * @param int $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondCreated($message = 'Endpoint Created Successfully', $id = 0)
    {
        return $this->setStatusCode(201)->respond([
            'message'     => $message,
            'id'          => $id,
            'status_code' => $this->getStatusCode(),
            'api_request' => $this->requestUri
        ]);
    }

    /**
     * @param $sql
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function respondInvalidQuery($sql)
    {
        $data = [
            'status'      => 'fail',
            'api_request' => $this->requestUri,
            'message'     => $sql
        ];

        return response($data, 403);
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        if (isset($this->queryString['debug'])) {
            return $this->queryString['debug'] === 'true';
        }

        return false;
    }

    /**
     * @param int $num_rows
     * @return array
     */
    public function addDebugInfo($num_rows = 0)
    {
        $debug = [
            'api_token' => $this->token,
            'db_source' => env('DB_CONNECTION'),
            'db_name'   => (env('DB_CONNECTION') === 'sqlite') ? env('DB_NAME') : env('DB_DATABASE'),
            'num_rows'  => $num_rows
        ];

        if ($this->token === 'c3be77b4-c9f1-3109-8729-e6704c93ef41') {
            $debug['trial'] = "Trial access limited to {$this->limit} rows";
        }

        return $debug;
    }
}
