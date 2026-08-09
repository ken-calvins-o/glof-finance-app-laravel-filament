<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Filament\Pages\Help;
use App\Models\User;
use App\Support\HelpTopics;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The help centre is documentation, and documentation rots.
 *
 * A manual that sends a reader to a screen that has been renamed or removed is
 * worse than no manual — they conclude the app is broken, or that they are. So
 * these tests walk every topic and check the things that quietly go wrong as
 * the app changes around it: dead links, screenshots that were deleted, topics
 * shown to someone who cannot open the screen they point at, and jargon
 * creeping back into text written for people who do not work in accounts.
 */
class HelpCentreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    private function member(): User
    {
        return User::factory()->create(['role' => RoleEnum::Member]);
    }

    private function treasurer(): User
    {
        return User::factory()->create(['role' => RoleEnum::Administrator]);
    }

    /**
     * @return array<int, array> every topic, from every section
     */
    private function everyTopic(): array
    {
        return collect(HelpTopics::all())
            ->flatMap(fn (array $section) => $section['topics'])
            ->all();
    }

    public function test_every_link_points_at_a_page_that_exists(): void
    {
        $this->actingAs($this->treasurer());

        foreach ($this->everyTopic() as $topic) {
            if (! isset($topic['link'])) {
                continue;
            }

            $url = ($topic['link']['url'])();

            $this->assertIsString($url, "The link on “{$topic['title']}” did not resolve to a URL.");

            $this->get($url)->assertSuccessful();
        }
    }

    /**
     * Sending a member to a screen they are not allowed to open is worse than
     * not mentioning it: they read it as something being wrong with their
     * account rather than as a job that is not theirs.
     */
    public function test_no_topic_shown_to_a_member_links_somewhere_they_cannot_go(): void
    {
        $member = $this->member();
        $this->actingAs($member);

        $topics = HelpTopics::sections($member)
            ->flatMap(fn (array $section) => $section['topics']);

        $this->assertNotEmpty($topics, 'A member should be able to read some of the manual.');

        foreach ($topics as $topic) {
            if (! isset($topic['link'])) {
                continue;
            }

            $this->get(($topic['link']['url'])())->assertSuccessful();
        }
    }

    public function test_a_member_is_not_shown_treasurer_only_topics(): void
    {
        $shown = HelpTopics::sections($this->member())
            ->flatMap(fn (array $section) => $section['topics'])
            ->pluck('audience');

        $this->assertNotEmpty($shown);
        $this->assertEmpty(
            $shown->filter(fn (string $audience) => $audience === HelpTopics::TREASURER),
            'A treasurer-only topic reached a member.',
        );
    }

    public function test_a_treasurer_is_shown_the_whole_manual(): void
    {
        $shown = HelpTopics::sections($this->treasurer())
            ->flatMap(fn (array $section) => $section['topics'])
            ->count();

        $this->assertSame(count($this->everyTopic()), $shown);
    }

    /**
     * Every screenshot a topic names has to be on disk. Deleting one and
     * leaving the reference behind is the easiest way to end up with a broken
     * image in the middle of an instruction.
     */
    public function test_every_screenshot_referred_to_has_been_captured(): void
    {
        $missing = [];

        foreach ($this->everyTopic() as $topic) {
            if (! isset($topic['image'])) {
                continue;
            }

            if (! file_exists(public_path('images/help/'.$topic['image']))) {
                $missing[] = "{$topic['title']} → {$topic['image']}";
            }
        }

        $this->assertSame([], $missing, 'Screenshots referred to but not present.');
    }

    public function test_topic_and_section_ids_are_unique_and_url_safe(): void
    {
        $ids = collect(HelpTopics::all())
            ->flatMap(fn (array $section) => array_merge(
                ['section-'.$section['id']],
                array_column($section['topics'], 'id'),
            ));

        $this->assertSame(
            $ids->count(),
            $ids->unique()->count(),
            'Two topics share an id, so linking to one would land on the other.',
        );

        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $id);
        }
    }

    /**
     * The one hard rule of this manual: it is written for people who did not
     * build the app. These are the words the codebase uses for the same things,
     * and each has a plain replacement already in use elsewhere in the text.
     */
    public function test_the_manual_avoids_the_words_the_code_uses(): void
    {
        $jargon = [
            'receivable' => 'money in / collection',
            'payable' => 'money out / payment',
            'eloquent' => '—',
            'migration' => '—',
            'widget' => 'box / card',
            'CRUD' => '—',
            'boolean' => '—',
            'null' => 'blank',
        ];

        $offences = [];

        foreach ($this->everyTopic() as $topic) {
            $text = Str::lower(implode(' ', array_merge(
                [$topic['title'], $topic['summary'] ?? ''],
                $topic['steps'] ?? [],
                $topic['notes'] ?? [],
            )));

            foreach ($jargon as $word => $instead) {
                if (str_contains($text, Str::lower($word))) {
                    $offences[] = "“{$topic['title']}” says “{$word}” — say {$instead}";
                }
            }
        }

        $this->assertSame([], $offences);
    }

    /**
     * A topic that describes a job should end with a way to go and do it.
     * Anything with numbered steps is a job.
     */
    public function test_every_task_ends_with_a_link_to_the_screen(): void
    {
        $withoutLink = [];

        foreach ($this->everyTopic() as $topic) {
            $isTask = collect($topic['steps'] ?? [])
                ->contains(fn (string $step) => Str::contains($step, 'Open **'));

            if ($isTask && ! isset($topic['link'])) {
                $withoutLink[] = $topic['title'];
            }
        }

        $this->assertSame([], $withoutLink, 'These describe a job but offer no way to start it.');
    }

    public function test_a_member_can_open_the_help_page(): void
    {
        $this->actingAs($this->member())
            ->get(Help::getUrl())
            ->assertSuccessful()
            ->assertSee('Finding your way around')
            // The member's manual must not mention jobs that are not theirs.
            ->assertDontSee('Issue a loan');
    }

    public function test_a_treasurer_can_open_the_help_page(): void
    {
        $this->actingAs($this->treasurer())
            ->get(Help::getUrl())
            ->assertSuccessful()
            ->assertSee('Record money a member has paid')
            ->assertSee('Give a member a loan');
    }

    /**
     * The emphasis in the source text is the only markup allowed through, and
     * anything else a writer types has to come out as characters on the page.
     */
    public function test_emphasis_renders_but_html_in_the_text_does_not(): void
    {
        $page = new Help;

        $render = function (string $text) use ($page) {
            $present = (fn (array $topic) => $this->present($topic))->call($page, [
                'id' => 'x',
                'title' => 'x',
                'summary' => $text,
            ]);

            return (string) $present['summary'];
        };

        $this->assertStringContainsString('<strong', $render('Press **Save**.'));
        $this->assertStringContainsString('Save</strong>', $render('Press **Save**.'));

        $withHtml = $render('Type <script>alert(1)</script> here.');
        $this->assertStringNotContainsString('<script>', $withHtml);
        $this->assertStringContainsString('&lt;script&gt;', $withHtml);
    }
}
