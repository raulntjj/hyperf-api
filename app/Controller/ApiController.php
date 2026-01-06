<?php

declare(strict_types=1);

namespace App\Controller;

use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

#[Controller(prefix:"/api")]
class ApiController
{
    #[GetMapping(path:"teste")]
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return $response->json([
            'message' => 'Hello Hyperf!',
        ]);
    }
}
