<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteUserAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected User $user
    ) {}

    public function handle(): void
    {
        $this->user?->refresh();

        DB::transaction(function () {
            $userType = User::class;
            /**
             * Delete user record from other tables here
             */
            $this->user->profile->delete();
            $this->user->delete();
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('DeleteUserAccountJob failed', [
            'user_id' => $this->user->id,
            'message' => $exception->getMessage(),
        ]);
    }
}
