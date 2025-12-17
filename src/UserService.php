<?php

namespace Microservices;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;
class UserService
{
    private $endpoint;

    public function __construct()
    {
        $this->endpoint = env('USER_ENDPOINT');
    }

    public function headers()
    {
        return [
            'Authorization' => request()->header('Authorization'),
            'Accept' => 'application/json',
        ];
    }

    public function request()
    {
        return Http::withHeaders($this->headers());
    }
    public function getUser(): ?User
    {
        $response = $this->request()->get("{$this->endpoint}/user");
        $json = $response->json();

        return new User($json);
    }
    public function isAdmin()
    {
        return $this->request()->get("{$this->endpoint}/admin")->successful();

    }
    public function isInfluencer()
    {
        return $this->request()->get("{$this->endpoint}/influencer")->successful();
    }

    public function allows($ability, $arguments)
    {
        return Gate::forUser($this->getUser())->authorize($ability, $arguments);
    }

    public function all($page = -1)
    {
        $response = $this->request()->get("{$this->endpoint}/users", ['page' => $page]);
        return $response->json();
    }
    public function get($id): User
    {
        $json = $this->request()->get("{$this->endpoint}/users/{$id}")->json();

        return new User($json);
    }

    public function create($data)
    {
        $json = $this->request()->post("{$this->endpoint}/users", $data)->json();

        return new User($json);
    }

    public function update($id, $data): User
    {
        $json = $this->request()->put("{$this->endpoint}/users/{$id}", $data)->json();

        return new User($json);
    }

    public function delete($id)
    {
        return $this->request()->delete("{$this->endpoint}/users/{$id}")->successful();
    }

}
