<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\APIToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ApiService
{
    protected $endpoint;
    protected $queryString;
    protected $query;
    protected $requestedUri;
    protected $request;
    protected $limit;
    protected $offset;
    protected $token;
    protected $trialLimit;
    protected $model;
    protected $verbose;

    public function __construct(Request $request)
    {
        $this->queryString  = $request->query();
        $this->request      = $request;
        $this->trialLimit   = env('API_TRIAL_LIMIT') or 10;
        if (is_null($this->trialLimit)) {
            abort(406, 'Please set API_TRIAL_LIMIT environment variable, currently null');
        }

        $this->query        = isset($this->queryString['q']) ? $this->queryString['q'] : '';
        $this->limit        = isset($this->queryString['limit']) ? $this->queryString['limit'] : $this->trialLimit;
        $this->offset       = isset($this->queryString['offset']) ? $this->queryString['offset'] : 0;

        $this->requestedUri = $request->getRequestUri();
        $this->endpoint     = $this->getEndpoint();

        $this->token        = $this->getToken();
        $this->verbose      = $this->getVerbose();
        $this->limit        = $this->getQueryLimit();
    }

    public function getEndpoint()
    {
        $endpoint = '';

        $parts = explode('/', $this->request->getPathInfo());
        if (sizeof($parts) >= 3) {
            $endpoint = $parts[3];
        }

        return $endpoint;
    }

    public function get($id = null)
    {
        $errors = [];

        if ($id === null) {
            $data = $this->buildQuery(
                $this->endpoint,
                $this->query,
                $this->offset,
                $this->limit
            );
            if (array_has($data, 'sql')) {
                $errors = $data;
                $data   = [];
            }
        } else {
            try {
                $model = str_singular('App\\Models\\' . ucwords($this->endpoint));
                if ($this->hasModel($model) && ($this->hasPlayerID($this->endpoint))) {
                    $data = $model::with('player')
                        ->where('id', '=', (int) $id)
                        ->orWhere('playerID', '=', $id)
                        ->get();
                } else {
                    if ($this->hasPlayerID($this->endpoint)) {
                        $data = DB::table($this->endpoint)
                            ->where('id', '=', $id)
                            ->orWhere('playerID', '=', $id)
                            ->get();
                    } else {
                        $data = DB::table($this->endpoint)
                            ->where('id', '=', $id)
                            ->get();
                    }
                }
            } catch (QueryException $e) {
                $errors = [
                    'message' => 'Internal MySQL Error Occurred',
                    'sql'     => $e->getMessage(),
                ];
                $data = null;
            }
        }

        $response = [
            'status'       => ($data) ? 'success' : 'fail',
            'status_code'  => ($data) ? 200 : 400,
            'api_request'  => $this->request->method() . ' ' . $this->requestedUri,
            'offset'       => (int) $this->offset,
            'limit'        => (int) $this->limit,
            'num_rows'     => ($data) ? sizeof($data) : 0,
            'token'        => $this->token,
            'token_status' => $this->tokenStatus($this->token)
        ];

        if (sizeof($errors) > 0) {
            $response['message'] = $errors['message'];
            $response['sql']     = $errors['sql'];
            $response['status']  = 'error';
        }

        if ($data) {
            if ($id === null) {
                $response['data'] = $data;
            } else {
                if ($response['num_rows'] > 0) {
                    $response['data'] = $data[0];
                } else {
                    $response['data'] = [];
                }
            }
        }

        $response = array_merge($response, $errors);

        return response($response, $response['status_code']);
    }

    public function put($id, $request)
    {
        if (!$this->isValidToken($this->token)) {
            $response = [
                'status_code' => 403,
                'status'      => 'fail',
                'message'     => 'Invalid Token, ' . ucwords(str_singular($this->endpoint)) . ' Not Updated',
            ];

            return response($response, $response['status_code']);
        }

        $record   = DB::table($this->endpoint)->find($id);
        $id       = (isset($record->id)) ? $record->id : 0;
        $playerID = (isset($record->playerID)) ? $record->playerID : '0';
        $data     = null;

        if (($id > 0) || ($playerID !== '')) {
            $data = $request->all();
            if (sizeof($data) === 0) {
                $response = [
                    'status_code' => 403,
                    'status'      => 'fail',
                    'message'     => 'Invalid Data, ' . ucwords(str_singular($this->endpoint)) . ' Not Updated',
                ];

                return response($response, $response['status_code']);
            }
            unset($data['token']); // remove token in case it was supplied as part of form post

            if ($this->hasPlayerID($this->endpoint)) {
                $result = DB::table($this->endpoint)
                    ->where('id', $id)
                    ->orWhere('playerID', $playerID)
                    ->update($data);
            } else {
                $result = DB::table($this->endpoint)
                    ->where('id', $id)
                    ->update($data);
            }
        }

        $response = [
            'status'      => ($data) ? 'success' : 'fail',
            'status_code' => ($data) ? 201 : 400,
            'api_request' => $this->requestedUri,
            'id'          => $id,
            'message'     => ($data)
                ? ucwords($this->endpoint) . " `id` $id Updated Successfully"
                : ucwords($this->endpoint) . 'Endpoint Not Found'
        ];

        if ($response['status_code'] === 201) {
            $response['data'] = $data;
        }

        return response($response, $response['status_code']);
    }

    public function post(Request $request)
    {
        if (!$this->isValidToken($this->token)) {
            $response = [
                'status_code' => 403,
                'status'      => 'fail',
                'message'     => 'Invalid Token, ' . ucwords(str_singular($this->endpoint)) . ' Not Created',
            ];

            return $response;
        }

        try {
            $data  = $request->all();

            // remove token in case it was supplied as part of form post
            unset($data['token'], $data['API_Token']);
            $id = DB::table($this->endpoint)
                ->insertGetId($data);
        } catch (QueryException $e) {
            die($e->getMessage());
        }

        if ($id > 0) {
            $response = [
                'status_code' => 201,
                'status'      => 'success',
                'message'     => ucwords(str_singular($this->endpoint)) . ' Created Successfully',
                'id'          => $id,
            ];
        } else {
            $response = [
                'status_code' => 400,
                'status'      => 'fail',
                'message'     => 'An error occurred creating ' . ucwords(str_singular($this->endpoint))
            ];
        }

        return $response;
    }

    public function delete($id)
    {
        if (!$this->isValidToken($this->token)) {
            $response = [
                'status_code' => 403,
                'status'      => 'fail',
                'message'     => 'Invalid Token, ' . ucwords(str_singular($this->endpoint)) . ' Not Deleted',
            ];

            return $response;
        }

        $result = DB::table($this->endpoint)
            ->where('id', '=', $id)
            ->delete();

        $response = [
            'status'      => ($result) ? 'success' : 'fail',
            'status_code' => ($result) ? 200 : 400,
            'api_request' => $this->requestedUri,
            'id'          => $id,
            'message'     => ($result)
                ? ucwords(str_singular($this->endpoint)) . " `id` $id Deleted Successfully"
                : ucwords(str_singular($this->endpoint)) . " Endpoint `id` $id Not Found"
        ];

        return $response;
    }

    public function buildQuery($endpoint = null, $q = null, $offset = 0, $limit = 3)
    {
        $whereClause  = [];
        $keys         = [];
        $values       = [];
        $errors       = [];
        $result       = [];

        if ($q === 'schema') {
            if (!$this->isValidToken($this->token)) {
                return [
                    'message' => 'You must have valid token to access Schema information'
                ];
            }

            return $this->getSchema($this->endpoint);
        }

        // TODO: refactor this to use regex so users can supply delimiters
        // =, >, <, >=, <=, <>, #
        if ($q !== '') {
            foreach (explode(',', $q) as $param) {
                list($keys[], $values[]) = explode(':', $param);
            }
        }

        // build where clause
        for ($i = 0; $i < sizeof($keys); $i++) {
            $whereClause[] = [$keys[$i], '=', $values[$i]];
        }

        $model = str_singular('App\\Models\\' . ucwords($this->endpoint));

        if ($this->hasModel($model) && ($this->hasPlayerID($this->endpoint))) {
            $result = $model::with('player')
                ->where($whereClause)
                ->skip($offset)
                ->limit($limit)
                ->get();
        } else {
            try {
                $result = DB::table($endpoint)
                    ->where($whereClause)
                    ->skip($offset)
                    ->limit($limit)
                    ->get();
            } catch (QueryException $e) {
                $errors = [
                    'status'  => 'SQL Error',
                    'message' => 'Internal SQL Error',
                    'sql'     => $e->getMessage(),
                ];
            }
        }

        if (sizeof($errors) > 0) {
            $result = array_merge($errors);
        }

        return $result;
    }

    public function getToken()
    {
        $token = $this->request->header('API-Token')
            ? $this->request->header('API-Token')
            : $this->request->query('token');

        if (is_null($token)) {
            $token = $this->request->header('X-API-Token');
        }

        return $token;
    }

    public function getVerbose()
    {
        $value = $this->request->header('API-Verbose')
            ? $this->request->header('API-Verbose')
            : $this->request->query('verbose');

        return $value === 'true' ? true : false;
    }

    public function isTrialToken($token)
    {
        return $token === env('API_TRIAL_TOKEN');
    }

    public function tokenStatus($token)
    {
        if ($this->isTrialToken(($token))) {
            $limit = env('API_TRIAL_LIMIT');

            return "Trial (row limit: ${limit})";
        }

        if ($this->isValidToken($token) !== false) {
            return $this->getTokenExpirationDate($token);
        }

        $limit = env('API_DEMO_LIMIT');

        return "Demo (row limit: ${limit})";
    }

    public function isValidToken($token)
    {
        if (is_null($token)) {
            return false;
        } else {
            $token = APIToken::where('token', $token)->first();

            return $token['expires'] > Carbon::now();
        }

        return false;
    }

    public function getTokenExpirationDate($token)
    {
        $token        = APIToken::where('token', $token)->first();
        $tokenExpired = $token['expires'] > Carbon::now();
        $tokenLimit   = (int)env('API_QUERY_LIMIT');

        return $tokenExpired ? 'Expires ' . $token['expires'] . " (row limit: ${tokenLimit})" : 'Expired ' . $token['expires'];
    }

    public function trialLimit()
    {
        return $this->trialLimit;
    }

    public function getQueryLimit()
    {
        $limit = 5;
        if ($this->isTrialToken($this->token)) {
            if ($limit > $this->trialLimit) {
                $limit = $this->trialLimit;
            }
        } else {
            // this will be true in `demo` mode
            if (is_null($this->token)) {
                $limit = isset($this->queryString['limit']) ? $this->queryString['limit'] : (int) env('API_DEMO_LIMIT');
                if ($limit > (int) env('API_DEMO_LIMIT')) {
                    $limit = (int) env('API_DEMO_LIMIT');
                }
            } else {
                if (is_null(env('API_QUERY_LIMIT'))) {
                    $limit = isset($this->queryString['limit']) ? $this->queryString['limit'] : 50;
                } else {
                    $limit = isset($this->queryString['limit']) ? $this->queryString['limit'] : (int)env('API_QUERY_LIMIT');
                    if ($limit > (int)env('API_QUERY_LIMIT')) {
                        $limit = (int)env('API_QUERY_LIMIT');
                    }
                }
            }
        }

        return $limit;
    }

    public function hasPlayerID($endpoint)
    {
        $noPlayerID = [
            'homegames',
            'parks',
            'players',
            'schools',
            'seriespost',
            'teams',
            'teamsfranchises',
            'teamshalf'
        ];

        return !in_array($endpoint, $noPlayerID);
    }

    public function hasModel($model)
    {
        return class_exists($model);
    }

    public function getSchema($tablename)
    {
        $result = DB::select("DESCRIBE {$this->endpoint}");
        $model  = 'App\\Models\\' . str_singular(ucwords($this->endpoint));
        if ($this->hasModel($model)) {
            $hidden   = ((new $model)->getHidden());
            $filtered = [];
            foreach ($result as &$value) {
                if (!in_array($value->Field, $hidden)) {
                    $filtered[] = $value;
                }
            }

            return $filtered;
        }

        return $result;
    }

    public function getVerboseInfo($data, $status_code = 200)
    {
        $verbose_info = [
            'num_rows'             => $this->getNumRows($data),
            'offset'               => -1,
            'limit'                => -1,
            'token'                => $this->token,
            'token_status'         => $this->tokenStatus($this->token),
            'status_code'          => $status_code,
            'status'               => ($status_code >= 400) ? 'fail' : 'success',
            'message'              => 'message',
            'request'              => $this->endpoint,
            'data'                 => $data,
            'rate_limit'           => -1,
            'rate_limit_remaining' => -1
        ];

        $table_info = [
            'total'         => $this->getNumRows($data),
            'per_page'      => 15,
            'current_page'  => 6,
            'last_page'     => 14,
            'next_page_url' => 'https://vuetable.ratiw.net/api/users?page=7',
            'prev_page_url' => 'https://vuetable.ratiw.net/api/users?page=5',
            'from'          => 76,
            'to'            => 90,
        ];

        return $verbose_info;
    }

    public function getNumRows($data)
    {
        return -1;
    }
}
