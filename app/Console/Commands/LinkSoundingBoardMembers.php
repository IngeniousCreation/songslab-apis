<?php

namespace App\Console\Commands;

use App\Models\SoundingBoardMember;
use App\Models\User;
use Illuminate\Console\Command;

class LinkSoundingBoardMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soundingboard:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link sounding board members to user accounts based on email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Linking sounding board members to user accounts...');

        // Get all sounding board members without a linked user_id
        $members = SoundingBoardMember::whereNull('user_id')
            ->whereNotNull('email')
            ->get();

        $linkedCount = 0;

        foreach ($members as $member) {
            // Find user with matching email
            $user = User::where('email', $member->email)->first();

            if ($user) {
                $member->user_id = $user->id;
                $member->save();
                $linkedCount++;
                $this->info("Linked member #{$member->id} ({$member->email}) to user #{$user->id} ({$user->name})");
            }
        }

        $this->info("Successfully linked {$linkedCount} sounding board members to user accounts.");

        return 0;
    }
}

