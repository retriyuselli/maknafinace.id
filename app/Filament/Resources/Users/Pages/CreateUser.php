<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);

        $this->generateTargetsForAccountManager($user);

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function generateTargetsForAccountManager($user): void
    {
        try {
            $user->refresh();
            $user->load('roles');

            if (! $user->hasRole('Account Manager')) {
                Notification::make()
                    ->title('User Created')
                    ->body('User created successfully.')
                    ->success()
                    ->send();

                return;
            }

            // Hanya generate target untuk user baru ini — bukan semua Account Manager
            Artisan::call('targets:generate', [
                '--auto-12-months' => true,
                '--year' => date('Y'),
                '--user-id' => $user->id,
            ]);

            Notification::make()
                ->title('Account Manager Created')
                ->body('User created successfully and targets have been generated for this user.')
                ->success()
                ->send();
        } catch (Exception $e) {
            Log::warning('Failed to auto-generate targets for new user: '.$e->getMessage(), [
                'user_id' => $user->id ?? null,
            ]);

            Notification::make()
                ->title('User Created')
                ->body('User created successfully. Targets can be generated manually if needed.')
                ->warning()
                ->send();
        }
    }
}
