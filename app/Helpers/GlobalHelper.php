<?php

function errorJson($errorCode = 500, $details = null) {
    $httpErrorMessage = [
        '400' => 'Bad Request',
        '401' => 'Not Authorized',
        '404' => 'Not Found',
        '500' => 'Server Error'
    ];
    
    $content = [
        'status' => $errorCode,
        'message' => $httpErrorMessage[$errorCode]
    ];

    if ($details){
        $content['details'] = $details;
    }

    return response()->json($content, $errorCode);
}