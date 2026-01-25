<?php

namespace App\Traits;

trait ApiResponses
{
    protected function ok($message, $data = [])
    {
        return $this->success($message, $data, 200);
    }

  

    protected function success(string $message, $data)
    {
        $response = [
            'message' => $message,
            'status' => 'success',
        ];

        if ($data != null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    protected function error($message, $status = 400)
    {
        return response()->json([
            'message' => $message,
            'status' => 'error',
        ], $status);
    }
}
