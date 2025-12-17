<?php

namespace Microservices;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException; // 1. Wajib import ini

class AdminScope
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handle(Request $request, Closure $next)
    {
        if($this->userService->isAdmin()){
            return $next($request);
        }

        throw new AuthenticationException();
    }
}
