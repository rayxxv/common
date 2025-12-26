<?php

namespace Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;

class UserService
{
    private $endpoint;
    private $user = null; // Cache internal agar tidak request berulang
    private $loaded = false; // Flag status loading

    public function __construct()
    {
        $this->endpoint = rtrim(env('USER_ENDPOINT'), '/');
    }

    /**
     * Pre-build headers agar tidak dihitung ulang setiap saat.
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => request()->header('Authorization'),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Lazy Loader: Hanya mengambil data saat benar-benar dibutuhkan.
     */
    public function getUser(): ?User
    {
        if ($this->loaded) return $this->user;

        try {
            // Gunakan timeout rendah (2 detik) agar tidak menghambat user
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(2)
                ->get("{$this->endpoint}/user");

            if ($response->successful()) {
                $this->user = new User($response->json());
            }
        } catch (\Exception $e) {
            // Silently fail untuk kecepatan, atau log jika perlu
        }

        $this->loaded = true;
        return $this->user;
    }

    /**
     * Versi Ringan: Mengganti authorize() dengan check()
     * untuk mencegah Exception handling yang berat.
     */
    public function allows($ability, $arguments): bool
    {
        $user = $this->getUser();
        if (!$user) return false;

        return Gate::forUser($user)->check($ability, $arguments);
    }

    /**
     * Optimasi Admin/Influencer Check:
     * Tidak perlu request API baru, cukup gunakan data yang sudah di-load.
     */
    public function isAdmin(): bool
    {
        return $this->getUser()?->isAdmin() ?? false;
    }

    public function isInfluencer(): bool
    {
        return $this->getUser()?->isInfluencer() ?? false;
    }

    // --- Helper CRUD singkat ---
    private function quickRequest($method, $url, $data = [])
    {
        return Http::withHeaders($this->getHeaders())->$method("{$this->endpoint}/$url", $data);
    }

    public function all($page = -1) { return $this->quickRequest('get', 'users', ['page' => $page])->json(); }
    public function delete($id) { return $this->quickRequest('delete', "users/$id")->successful(); }
}