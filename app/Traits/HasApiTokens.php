<?php

namespace App\Traits;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

trait HasApiTokens
{
    /**
     * Get the access tokens that belong to model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function tokens(): MorphMany
    {
        return $this->morphMany(PersonalAccessToken::class, 'tokenable');
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @param  \DateTimeInterface|null  $expiresAt
     * @return \App\Models\PersonalAccessToken
     */
    public function createToken(string $name, array $abilities = ['*'], $expiresAt = null)
    {
        $token = Str::random(64);

        $accessToken = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $token),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
        ]);

        return (object) [
            'accessToken' => $accessToken,
            'plainTextToken' => $token,
        ];
    }

    /**
     * Get the current access token being used by the user.
     *
     * @return \App\Models\PersonalAccessToken|null
     */
    public function currentAccessToken()
    {
        return $this->accessToken ?? null;
    }

    /**
     * Set the current access token for the user.
     *
     * @param  \App\Models\PersonalAccessToken  $accessToken
     * @return $this
     */
    public function withAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}

