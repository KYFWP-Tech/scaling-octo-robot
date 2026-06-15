<?php

namespace App\Traits;

use App\Models\Verification;
use App\Notifications\AdminInvitationNotification;
use App\Notifications\ResetPassword;

trait VerifyUser
{
    private function generateVerifier(): Verification
    {
        $verifyUser = Verification::create([
            'code' => mt_rand(100000, 999999),
            'email' => $this->email,
            'expires_at' => now()->addMinutes((int)config('auth.verification_timeout'))
        ]);

        return $verifyUser;
    }

    public function sendVerificationEmail()
    {
        $verifyUser = $this->generateVerifier();

        $this->notify(new AdminInvitationNotification($verifyUser));
        // Temporary Fix
        return $verifyUser;
    }

    public function sendPasswordResetEmail()
    {
        $verifyUser = $this->generateVerifier();

        $verifyUser->user->notify(new ResetPassword($verifyUser));

        // Temporary Fix
        return $verifyUser;
    }

    public function verify(string $password)
    {
        $this->password = $password;
        $this->email_verified_at = now();
        $this->save();

        return $this;
    }
}
