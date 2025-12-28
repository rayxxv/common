<?php

namespace Microservices;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Microservices\User;

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

    /**
     * Mengambil data user dari Service 8001 secara aman.
     */
    public function getUser(): ?User
    {
        try {
            $response = $this->request()->get("{$this->endpoint}/user");

            if ($response->successful()) {
                return new User($response->json());
            }

            // Jika response tidak sukses (misal: 401 Unauthorized dari Service 8001)
            Log::warning("UserService: Gagal mengambil data user. Status: " . $response->status());
            return null;

        } catch (\Exception $e) {
            // Jika terjadi kesalahan koneksi (misal: Port 8001 tidak aktif atau URL salah)
            Log::error("UserService: Error koneksi ke {$this->endpoint}. Pesan: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Method ini yang akan dipanggil di Controller.
     */
    public function allows($ability, $arguments)
    {
        $user = $this->getUser();

        // JIKA USER NULL (Gagal ambil dari service lain),
        // kita coba gunakan user yang sedang terotentikasi di guard lokal.
        if (!$user) {
            $user = auth()->user();
        }

        // Jika setelah fallback tetap null, berarti user memang tidak login
        if (!$user) {
            return false;
        }

        /**
         * PENTING: Gunakan check() alih-alih authorize().
         * check() mengembalikan true/false.
         * authorize() langsung melempar 403 Forbidden jika gagal.
         */
        return Gate::forUser($user)->check($ability, $arguments);
    }

    // --- Method lainnya tetap sama atau sesuaikan dengan try-catch serupa ---

    public function isAdmin()
    {
        $response = $this->request()->get("{$this->endpoint}/admin");
        return $response->successful();
    }

    public function isInfluencer()
    {
        $response = $this->request()->get("{$this->endpoint}/influencer");
        return $response->successful();
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
