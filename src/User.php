<?php

namespace Microservices;

class User
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $is_influencer;

    public function __construct(array $json)
    {
        // Mass assignment yang cepat
        foreach ($json as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        // Casting satu kali saja
        $this->is_influencer = (int) ($this->is_influencer ?? 0);
    }

    public function isAdmin(): bool { return $this->is_influencer === 0; }
    public function isInfluencer(): bool { return $this->is_influencer === 1; }
}