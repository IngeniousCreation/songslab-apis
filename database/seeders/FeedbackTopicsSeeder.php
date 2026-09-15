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
            ['key' => 'general_comments', 'label' => 'Comment?', 'order' => 0],
            ['key' => 'lyrics', 'label' => 'My lyrics; are there any you like in particular and/or some that pull your attention out of the experience?', 'order' => 1],
            ['key' => 'melodies', 'label' => 'Melodies; where is the melodic structure strong and where could it be improved?', 'order' => 2],
            ['key' => 'genre', 'label' => 'What genre do you associate with this song?', 'order' => 3],
            ['key' => 'placement', 'label' => 'Where might this song play, sporting events, TV show closing credits?', 'order' => 4],
            ['key' => 'playlist_artists', 'label' => 'What other artists would be on a playlist with this song?', 'order' => 5],
            ['key' => 'musicianship', 'label' => 'Overall musicianship; are the instrumental performances effective. What instruments and sections might you modify', 'order' => 6],
            // Key stays 'vocal_harmonies' so existing song selections keep resolving;
            // only the wording changed (harmonies -> delivery).
            ['key' => 'vocal_harmonies', 'label' => 'Vocal delivery; are the vocals strong? Do they fit the song well? How might the vocals be approached more effectively?', 'order' => 7],
            ['key' => 'mood', 'label' => 'What do you think of the mood of the song?', 'order' => 8],
            ['key' => 'mix', 'label' => 'The mix; how would you adjust this mix?', 'order' => 9],
            ['key' => 'song_structure', 'label' => 'The song structure; verses, choruses, bridges, and so forth, are there changes you\'d like to hear in the song\'s structure?', 'order' => 10],
            ['key' => 'song_sections', 'label' => 'Are there certain sections of the song working better for you than others?', 'order' => 11],
            ['key' => 'instrumentation_choices', 'label' => 'Instrumentation choices and other possibilities for instrumentation?', 'order' => 12],
            ['key' => 'arrangement', 'label' => 'Arrangement; is each instrument playing what it should where it should?', 'order' => 13],
            ['key' => 'overall_sound', 'label' => 'Do you have other suggestions related to the overall sound? How might you approach presenting this song differently?', 'order' => 14],
            ['key' => 'tempo', 'label' => 'Tempo; does the tempo feel right or would you adjust it?', 'order' => 15],
            ['key' => 'key', 'label' => 'Key; does the key feel right or would you adjust it?', 'order' => 16],
            ['key' => 'production', 'label' => 'Production; how would you adjust the production?', 'order' => 17],
            ['key' => 'commercial_potential', 'label' => 'Do you hear commercial potential?', 'order' => 18],
            ['key' => 'overall_impressions', 'label' => 'What are your overall impressions?', 'order' => 19],
            ['key' => 'context_comparison', 'label' => 'What do you notice about this song in context with other songs I\'ve shared with you?', 'order' => 20],
            ['key' => 'song_strengths', 'label' => 'What are the song\'s strengths and what would you like to hear more of?', 'order' => 21],
            ['key' => 'song_shortcomings', 'label' => 'What shortcomings do you identify and what should I give attention to?', 'order' => 22],

            // Added per Justin, 8/27/26. Appended rather than interleaved so existing
            // topics keep their order values; within a category the modal renders in
            // order, so these appear after the original options.
            ['key' => 'lyrical_clarity', 'label' => 'Assess the lyrical clarity', 'order' => 23],
            ['key' => 'vocal_conviction', 'label' => 'What do you think of the vocal conviction?', 'order' => 24],
            ['key' => 'emotional_arc', 'label' => 'Assess the song\'s emotional arc.', 'order' => 25],
            ['key' => 'hooks', 'label' => 'Assess the strength of the song\'s hooks.', 'order' => 26],
            ['key' => 'groove_timing', 'label' => 'What do you think of the song\'s groove, timing, and/or dynamics?', 'order' => 27],
            ['key' => 'density', 'label' => 'What do you think of the song\'s density?', 'order' => 28],
            ['key' => 'pacing', 'label' => 'What do you think of the song\'s pacing?', 'order' => 29],
            ['key' => 'artistic_identity', 'label' => 'Does this song reveal a recognizable artistic identity?', 'order' => 30],
            ['key' => 'production_sonic_identity', 'label' => 'Share your thoughts on the production\'s sonic identity, frequency balance, depth, and/or transitions.', 'order' => 31],
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

