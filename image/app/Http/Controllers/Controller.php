<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public $baseDir;
    
    public function __construct()
    {
        $this->baseDir = base_path() . '/public/' . config('constants.upload_dir');
    }
    
    function json($data=[], $error_code=0) {
    
        $rlt = array(
            'error_code' =>  $error_code,
            'data' => $data
        );
    
        return response()->json($rlt);
    }
}
