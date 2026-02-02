<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeedbackTopicsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topics = [
            ['key' => 'lyrics', 'label' => 'My lyrics; are there any you like in particular and/or some that pull your attention out of the experience', 'order' => 1],
            ['key' => 'melodies', 'label' => 'Melodies; where is the melodic structure strong and where could it be improved', 'order' => 2],
            ['key' => 'musicianship', 'label' => 'Overall musicianship; are the vocals and the instrumental performances effectively delivering the song or do either need particular attention', 'order' => 3],
            ['key' => 'vocal_harmonies', 'label' => 'Vocal harmonies; are they strong as they are or how might they be approached more effectively', 'order' => 4],
            ['key' => 'song_structure', 'label' => 'The song structure; verses, choruses, bridges, and so forth, are there changes you\'d like to hear in the song\'s structure', 'order' => 5],
            ['key' => 'instrumentation', 'label' => 'Instrumentation; are there instruments you\'d like to hear more or less of, or instruments you\'d like to hear added or removed', 'order' => 6],
            ['key' => 'tempo', 'label' => 'Tempo; does the tempo feel right or would you adjust it', 'order' => 7],
            ['key' => 'key', 'label' => 'Key; does the key feel right or would you adjust it', 'order' => 8],
            ['key' => 'production', 'label' => 'Production; how would you adjust the production', 'order' => 9],
            ['key' => 'mix', 'label' => 'The mix; how would you adjust this mix', 'order' => 10],
            ['key' => 'commercial_potential', 'label' => 'Do you hear commercial potential?', 'order' => 11],
            ['key' => 'overall_impressions', 'label' => 'What are your overall impressions?', 'order' => 12],
            ['key' => 'context_comparison', 'label' => 'What do you notice about this song in context with other songs I\'ve shared with you', 'order' => 13],
            ['key' => 'general_comments', 'label' => 'General comments you can offer me in response to my song', 'order' => 14],
        ];

        foreach ($topics as $topic) {
            DB::table('feedback_topics')->insert([
                'key' => $topic['key'],
                'label' => $topic['label'],
                'order' => $topic['order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

