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
            ['key' => 'genre', 'label' => 'What genre do you associate with this song', 'order' => 3],
            ['key' => 'placement', 'label' => 'Where might this song play, sporting events, TV show closing credits', 'order' => 4],
            ['key' => 'playlist_artists', 'label' => 'What other artists would be one playlist with this song', 'order' => 5],
            ['key' => 'musicianship', 'label' => 'Overall musicianship; are the vocals and the instrumental performances effectively delivering the song or do either need particular attention', 'order' => 6],
            ['key' => 'vocal_harmonies', 'label' => 'Vocal harmonies; are they strong as they are or how might they be approached more effectively', 'order' => 7],
            ['key' => 'mood', 'label' => 'The Mood of the song, how does the mood seem dense to you is it consistent', 'order' => 8],
            ['key' => 'mix', 'label' => 'The mix; how would you adjust this mix', 'order' => 9],
            ['key' => 'song_structure', 'label' => 'The song structure; verses, choruses, bridges, and so forth, are there changes you\'d like to hear in the song\'s structure', 'order' => 10],
            ['key' => 'song_sections', 'label' => 'Are there certain sections of the song working better for you than others', 'order' => 11],
            ['key' => 'instrumentation_choices', 'label' => 'Instrumentation choices and other possibilities for instrumentation', 'order' => 12],
            ['key' => 'arrangement', 'label' => 'Arrangement; is each instrument playing what it should where it should', 'order' => 13],
            ['key' => 'overall_sound', 'label' => 'Other suggestions about the overall sound or chord progressions among other things they are hearing or how they would approach it', 'order' => 14],
            ['key' => 'tempo', 'label' => 'Tempo; does the tempo feel right or would you adjust it', 'order' => 15],
            ['key' => 'key', 'label' => 'Key; does the key feel right or would you adjust it', 'order' => 16],
            ['key' => 'production', 'label' => 'Production; how would you adjust the production', 'order' => 17],
            ['key' => 'commercial_potential', 'label' => 'Do you hear commercial potential?', 'order' => 18],
            ['key' => 'overall_impressions', 'label' => 'What are your overall impressions?', 'order' => 19],
            ['key' => 'context_comparison', 'label' => 'What do you notice about this song in context with other songs I\'ve shared with you', 'order' => 20],
            ['key' => 'song_strengths', 'label' => 'What are the song\'s strengths and what would you like to hear more of', 'order' => 21],
            ['key' => 'song_shortcomings', 'label' => 'What shortcomings do you identify and what should I give attention to', 'order' => 22],
        ];

        foreach ($topics as $topic) {
            DB::table('feedback_topics')->updateOrInsert(
                ['key' => $topic['key']],
                [
                    'label' => $topic['label'],
                    'order' => $topic['order'],
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        }
    }
}

